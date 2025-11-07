<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CsvImportController extends Controller
{
    public function showForm()
    {
        return view('csv_upload');
    }

    public function upload(Request $request)
    {
        // ✅ تحقق من الملف و filetype (0=CSV, 1=XML, 2=XSLT)
        $request->validate([
            'csv' => 'required|file', // اسم الحقل احتفظت بيه كما عندك
            'filetype' => 'nullable|in:0,1,2',
        ]);

        $filetype = $request->input('filetype', 0); // CSV افتراضي

        // ✅ احفظ الملف الأصلي مؤقتًا (كما في كودك الأصلي)
        $path = $request->file('csv')->storeAs('uploads', time() . '.csv');
        $filePath = storage_path('app/' . $path);

        if (!file_exists($filePath)) {
            return back()->with('error', '❌ الملف غير موجود بعد الرفع.');
        }

        // --- إذا المستخدم اختار XML أو XSLT، نحول الملف إلى CSV مؤقت ---
        $tempCsvPath = null;
        if ($filetype == 1 || $filetype == 2) {
            // السماح بتحميل DTD و External entities (لمختبر/CTF فقط)
            // ملاحظة: libxml_disable_entity_loader قد يكون محذوفاً في بعض نسخ PHP الحديثة،
            // لكن نضع السطر لضمان سلوك التحميل في البيئات التي تدعمه.
            @libxml_disable_entity_loader(false);
            libxml_use_internal_errors(true);

            $rows = [];
            $headers = [];

            try {
                if ($filetype == 1) {
                    // XML: نحمل الملف مع تمكين حل الـ ENTITY و DTD
                    $xmlContent = file_get_contents($filePath);
                    $dom = new \DOMDocument();
                    // تمكين حل الـ entities و تحميل DTD خارجي: LIBXML_NOENT | LIBXML_DTDLOAD | LIBXML_DTDATTR
                    $dom->loadXML($xmlContent, LIBXML_NOENT | LIBXML_DTDLOAD | LIBXML_DTDATTR);

                    // نفترض ان كل <record> عنصر يمثل صف
                    foreach ($dom->getElementsByTagName('record') as $record) {
                        $row = [];
                        foreach ($record->childNodes as $child) {
                            if ($child->nodeType === XML_ELEMENT_NODE) {
                                if (!in_array($child->nodeName, $headers)) $headers[] = $child->nodeName;
                                $row[] = $child->nodeValue;
                            }
                        }
                        $rows[] = $row;
                    }
                } else {
                    // XSLT: نحاول تحميل الملف كـ XSLT ثم تطبيقه على مستند XML بسيط
                    // تحميل XSL مع تمكين حل الـ ENTITY و DTD أثناء التحميل ليتيح OOB / includes
                    $xslDoc = new \DOMDocument();
                    $xslDoc->load($filePath, LIBXML_NOENT | LIBXML_DTDLOAD | LIBXML_DTDATTR);

                    // لإجراء التحويل نحتاج لمصدر XML؛ ننشئ مصدر بسيط يمكن للXSL تغييره
                    $xmlSource = new \DOMDocument();
                    $xmlSource->loadXML('<root/>');

                    $proc = new \XSLTProcessor();
                    $proc->importStylesheet($xslDoc);

                    $transformed = $proc->transformToXML($xmlSource);
                    if ($transformed === false) {
                        // فشل التحويل — سنعطي خطأ واضح
                        throw new \Exception('XSLT transform failed or returned empty output.');
                    }

                    // الآن نحلل الناتج المحوّل كـ XML ونستخرج عناصر <record>
                    $transDom = new \DOMDocument();
                    $transDom->loadXML($transformed, LIBXML_NOENT | LIBXML_DTDLOAD | LIBXML_DTDATTR);

                    foreach ($transDom->getElementsByTagName('record') as $record) {
                        $row = [];
                        foreach ($record->childNodes as $child) {
                            if ($child->nodeType === XML_ELEMENT_NODE) {
                                if (!in_array($child->nodeName, $headers)) $headers[] = $child->nodeName;
                                $row[] = $child->nodeValue;
                            }
                        }
                        $rows[] = $row;
                    }
                }
            } catch (\Exception $e) {
                // أخطاء التحليل/التحميل
                return back()->with('error', 'خطأ أثناء تحليل XML/XSLT: ' . $e->getMessage());
            }

            // إذا حصلنا على صفوف، نكتبها في CSV مؤقت لإعادة استخدام منطق CSV الأصلي كما هو
            if (!empty($rows)) {
                // ببناء ملف csv مؤقت في نفس مجلد التخزين
                $tmpName = 'uploads/tmp_' . time() . '.csv';
                $tempCsvPath = storage_path('app/' . $tmpName);
                $fp = fopen($tempCsvPath, 'w');
                if ($fp === false) {
                    return back()->with('error', 'فشل في إنشاء الملف المؤقت.');
                }

                // نكتب رؤوس الأعمدة أولاً
                fputcsv($fp, $headers);

                // كتابة الصفوف
                foreach ($rows as $r) {
                    // نملأ الفارغات لتساوي عدد الأعمدة
                    $filled = [];
                    for ($i = 0; $i < count($headers); $i++) {
                        $filled[] = $r[$i] ?? '';
                    }
                    fputcsv($fp, $filled);
                }

                fclose($fp);

                $filePath = $tempCsvPath;
            } else {
                return back()->with('error', 'لم يتم العثور على سجلات (record) في الملف.');
            }
        }

        $user = Auth::user();
        $role = $user?->role ?? 'hr';
        $connection = $role === 'hr' ? 'mysql_hr' : 'mysql_support';

        $pdo = DB::connection($connection)->getPdo();
        $pdo->beginTransaction();

        try {
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                return back()->with('error', '❌ فشل في قراءة الملف.');
            }

            $rowCount = 0;
            $headers = [];

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                // أول صف = أسماء الأعمدة (attributes)
                if ($rowCount === 0) {
                    $headers = $row;
                    $rowCount++;
                    continue;
                }

                // إنشاء Entity جديدة
                $entityType = "{$role}_record";
                $namespace = "App\\Entities\\" . ucfirst($role);
                $stmt = $pdo->prepare("INSERT INTO entities (namespace, entity_type, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                $stmt->execute([$namespace, $entityType]);
                //$entityId = $pdo->lastInsertId();
                //$sql = "INSERT INTO entities (namespace, entity_type, created_at, updated_at) VALUES ($namespace , $entityType, NOW(), NOW())";
                //$pdo->query($sql); // خطر
                $entityId = $pdo->lastInsertId();
                foreach ($headers as $i => $attrName) {
                    $value = $row[$i] ?? '';

                    $stmt = $pdo->prepare("SELECT id FROM attributes WHERE name = ?");
                    $stmt->execute([$attrName]);
                    $attr = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if ($attr) {
                        $attrId = $attr['id'];
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO attributes (name, data_type, created_at, updated_at) VALUES (?, 'string', NOW(), NOW())");
                        $stmt->execute([$attrName]);
                        $attrId = $pdo->lastInsertId();
                    }

                    //$stmt = $pdo->prepare("INSERT INTO `values` (entity_id, attribute_id, value, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                    //$stmt->execute([$entityId, $attrId, $value]);
                    //$valuex = "'$value'";
                    $sql = "INSERT INTO `values` (entity_id, attribute_id, value, created_at, updated_at) VALUES ($entityId, $attrId, $value, NOW(), NOW())";
                    $pdo->query($sql);

                }

                $rowCount++;
            }

            fclose($handle);
            $pdo->commit();

            if ($tempCsvPath && file_exists($tempCsvPath)) {
                @unlink($tempCsvPath);
            }

            return back()->with('success', "{$connection} (" . ($rowCount - 1) . " ).");

        } catch (\Exception $e) {
            $pdo->rollBack();

            // حذف الملف المؤقت لو اتعمل
            if ($tempCsvPath && file_exists($tempCsvPath)) {
                @unlink($tempCsvPath);
            }

            return back()->with('error', ' خطأ أثناء الإدخال: ' . $e->getMessage());
        }
    }
}
