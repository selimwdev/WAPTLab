@extends('layouts.app')

@section('content')
<style>
  /* 🎨 تحسين التصميم العام */
  body {
    background: #f7f9fb;
  }

  .dashboard-header {
    background: linear-gradient(135deg, #1f4f82, #2a72b5);
    color: #fff;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  }

  .dashboard-header h3 {
    font-weight: 600;
    margin: 0;
  }

  /* 🔹 تنسيق أزرار الـ Dashboard */
  .btn-custom {
    border-radius: 10px;
    margin: 6px;
    padding: 0.45rem 0.9rem;
    font-weight: 500;
    transition: all 0.25s ease-in-out;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  }

  .btn-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
  }

  .btn-custom.active {
    background-color: rgba(255,255,255,0.2);
    border-color: #fff;
  }

  .dashboard-header .d-flex.flex-wrap {
    gap: 10px;
  }

  /* ✅ توزيع منطقي بين الجانبين */
  .header-left,
  .header-right {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
  }

  .header-left {
    flex-grow: 1;
  }

  #searchBox {
    border-radius: 10px;
    padding: 0.6rem 1rem;
    border: 1px solid #ced4da;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
  }

  .table {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
  }

  thead th {
    background-color: #e9f1f8;
    color: #1f4f82;
    font-weight: 600;
    border-bottom: 2px solid #c9ddec;
  }

  tbody tr:hover {
    background-color: #f4f9ff;
  }

  .btn-success.download-btn {
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .btn-success.download-btn i {
    font-size: 14px;
  }

  .status-pill {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
  }

  .status-pill.active { background: #c8f7c5; color: #2a7c2a; }
  .status-pill.inactive { background: #ffe1e1; color: #a12b2b; }

  .btn-group .dropdown-toggle {
    border-radius: 10px;
  }

  .dropdown-menu {
    border-radius: 10px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.15);
  }

  .dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    font-weight: 500;
  }

  .dropdown-item i {
    font-size: 1rem;
  }

  .dropdown-item:hover {
    background-color: #f2f7ff;
    color: #1f4f82;
  }

  /* ✉️ تصميم المودال */
  .modal-content {
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
  }

  .modal-header {
    background: linear-gradient(135deg, #2a72b5, #1f4f82);
    color: #fff;
    border-bottom: none;
    border-top-left-radius: 15px;
    border-top-right-radius: 15px;
  }

  .modal-footer {
    border-top: none;
  }

  .form-control {
    border-radius: 10px;
  }
</style>

<div class="container mt-4">
  <div class="dashboard-header mb-4 d-flex justify-content-between align-items-center flex-wrap">
    
    <!-- ✅ الجزء الأيسر: العنوان + HR/SUPPORT -->
    <div class="header-left">
      <h3 class="me-3"><i class="bi bi-speedometer2 me-2"></i> Dashboard — {{ strtoupper($db) }}</h3>
      <a href="{{ route('dashboard', ['db' => 'hr']) }}" 
         class="btn btn-sm btn-outline-light btn-custom {{ $db=='hr'?'active':'' }}">
        <i class="bi bi-person-badge"></i> HR
      </a>
      <a href="{{ route('dashboard', ['db' => 'support']) }}" 
         class="btn btn-sm btn-outline-light btn-custom {{ $db=='support'?'active':'' }}">
        <i class="bi bi-headset"></i> Support
      </a>
    </div>

    <!-- ✅ الجزء الأيمن: العمليات -->
    <div class="header-right">
      <button id="downloadAllBtn" class="btn btn-sm btn-warning text-dark btn-custom">
        <i class="bi bi-download"></i> Download All CSV
      </button>

      <button id="shareResultsBtn" class="btn btn-sm btn-danger btn-custom" data-bs-toggle="modal" data-bs-target="#shareModal">
        <i class="bi bi-envelope"></i> Share Results 
      </button>

      <div class="btn-group">
        <button type="button" class="btn btn-sm btn-primary dropdown-toggle btn-custom" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-menu-button-wide"></i> Actions
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <a class="dropdown-item" href="#" id="exportHtmlPdfBtn">
              <i class="bi bi-file-earmark-pdf text-danger"></i> Show Table
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="#" id="exportErpBtn">
              <i class="bi bi-diagram-3 text-success"></i> Export ERP
            </a>
          </li>
        </ul>
      </div>
    </div>

  </div>

  <input id="searchBox" class="form-control mb-3 shadow-sm" placeholder="🔍 Search records...">

  <div class="table-responsive">
    <table class="table table-striped align-middle mb-0">
      <thead id="thead"></thead>
      <tbody id="tbody"></tbody>
    </table>
  </div>
</div>

<!-- ✉️ Modal for sharing results -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="shareModalLabel"><i class="bi bi-envelope-paper"></i> Share Results via Email</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="shareForm">
          <div class="mb-3">
            <label class="form-label">Recipient Email</label>
            <input type="email" class="form-control" id="emailTo" placeholder="example@mail.com" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Subject</label>
            <input type="text" class="form-control" id="emailSubject" placeholder="CRM Results — {{ strtoupper($db) }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" id="emailDescription" rows="3" placeholder="Write a short message..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger" id="confirmShare">Send Email</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  const db = "{{ $db }}";
  const apiUrl = `/api/dashboard/data?db=${db}`;
  const tbody = document.getElementById('tbody');
  const thead = document.getElementById('thead');

  const res = await fetch(apiUrl);
  const rows = await res.json();

  if (!rows || !rows.length) {
    tbody.innerHTML = '<tr><td colspan="100%" class="text-center text-muted p-4">No data found</td></tr>';
    return;
  }

  const attrs = Object.keys(rows[0]);
  thead.innerHTML = `<tr>${attrs.map(a => `<th>${a}</th>`).join('')}<th>Actions</th></tr>`;

  const render = (data) => {
    tbody.innerHTML = data.map(r => `
      <tr>
        ${attrs.map(a => `<td>${String(r[a] ?? '')}</td>`).join('')}
        <td>
          <button class="btn btn-sm btn-success download-btn" data-row='${JSON.stringify(r).replace(/'/g,"\\'")}'><i class="bi bi-save"></i> Save</button>
        </td>
      </tr>
    `).join('');
  };
  render(rows);

  document.getElementById('searchBox').addEventListener('input', (e) => {
    const q = e.target.value.toLowerCase();
    if (!q) return render(rows);
    const filtered = rows.filter(r => Object.values(r).some(v => String(v).toLowerCase().includes(q)));
    render(filtered);
  });

  document.addEventListener('click', async (e) => {
    if (!e.target.closest('.download-btn')) return;
    const btn = e.target.closest('.download-btn');
    let rowData;
    try { rowData = JSON.parse(btn.dataset.row); } catch { return alert('Invalid row data'); }

    try {
      const saveRes = await fetch('{{ route("v1.crm.saveRow") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ db, row_data: JSON.stringify(rowData) })
      });
      if (!saveRes.ok) throw new Error();
      const { id } = await saveRes.json();
      window.location.href = `/v1/crm/download/${id}`;
    } catch {
      alert('Failed to download row');
    }
  });

  document.getElementById('downloadAllBtn').addEventListener('click', async () => {
    try {
      const res = await fetch('{{ route("crm.save-csv") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ db, rows })
      });
      const { path } = await res.json();
      window.location.href = `/crm/view?path=${encodeURIComponent(path)}`;
    } catch { alert('Failed to save CSV'); }
  });

  document.getElementById('confirmShare').addEventListener('click', async () => {
    const to = document.getElementById('emailTo').value.trim();
    const subject = document.getElementById('emailSubject').value.trim();
    const description = document.getElementById('emailDescription').value.trim();
    if (!to || !subject) return alert('Please fill in all required fields');

    try {
      const saveRes = await fetch('{{ route("crm.save-csv") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ db, rows })
      });
      const { path } = await saveRes.json();

      const sendRes = await fetch('{{ route("training.send-results-email") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ to, subject, description, path })
      });

      if (sendRes.ok) {
        alert('✅ Email sent successfully!');
        bootstrap.Modal.getInstance(document.getElementById('shareModal')).hide();
      } else {
        alert('❌ Failed to send email.');
      }
    } catch {
      alert('❌ Error occurred while sharing results.');
    }
  });

  document.getElementById('exportHtmlPdfBtn').addEventListener('click', async () => {
    try {
      const exportRes = await fetch('{{ route("crm.export") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ db, rows })
      });
      const result = await exportRes.json();
      window.open(result.url, '_blank');
    } catch {
      alert('Failed to export HTML/PDF');
    }
  });

  document.getElementById('exportErpBtn').addEventListener('click', async () => {
    try {
      const xsltEncoded = `
&lt;?xml version="1.0"?&gt;
&lt;xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"&gt;
  &lt;xsl:output method="xml" indent="yes"/&gt;
  &lt;xsl:template match="/"&gt;
    &lt;ERPData&gt;
      &lt;Clients&gt;
        &lt;xsl:for-each select="customers/customer"&gt;
          &lt;Client&gt;
            &lt;ID&gt;&lt;xsl:value-of select="id"/&gt;&lt;/ID&gt;
            &lt;Name&gt;&lt;xsl:value-of select="name"/&gt;&lt;/Name&gt;
            &lt;Email&gt;&lt;xsl:value-of select="email"/&gt;&lt;/Email&gt;
          &lt;/Client&gt;
        &lt;/xsl:for-each&gt;
      &lt;/Clients&gt;
    &lt;/ERPData&gt;
  &lt;/xsl:template&gt;
&lt;/xsl:stylesheet&gt;
`;

// تفكيك الترميز (decode) وحفظ الناتج في متغير xslt
function decodeHtmlEntities(encoded) {
  const textarea = document.createElement('textarea');
  textarea.innerHTML = encoded;
  return textarea.value;
}

const xslt = decodeHtmlEntities(xsltEncoded);


      const res = await fetch('{{ route("crm.export.erp") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ db, rows, xslt })
      });

      if (!res.ok) throw new Error();
      const blob = await res.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'erp_export.xml';
      a.click();
      window.URL.revokeObjectURL(url);
    } catch {
      alert('Failed to export ERP (XSLT)');
    }
  });
});
</script>
@endsection
