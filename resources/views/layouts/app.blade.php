<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>WAPTLab v1.0 - CRM</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <style>
        :root {
            --color-bg: #f6f8fa;
            --color-surface: #ffffff;
            --color-text: #1a1d25;
            --color-primary: #0d6efd;
            --color-hover: #f0f2f5;
            --color-border: #dcdfe6;
        }

        body {
            background-color: var(--color-bg);
            color: var(--color-text);
            font-family: 'Inter', sans-serif;
            font-size: 15px;
        }

        /* Navbar */
        .navbar {
            background-color: var(--color-surface) !important;
            border-bottom: 1px solid var(--color-border);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }

        .navbar-brand svg path {
            fill: var(--color-primary);
        }

        .navbar-brand span {
            font-weight: 700;
            letter-spacing: 0.3px;
            color: var(--color-text);
        }

        .nav-link {
            color: var(--color-text) !important;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--color-primary) !important;
        }

        .nav-item .bi {
            font-size: 1rem;
            margin-right: 6px;
            opacity: 0.85;
        }

        .dropdown-menu {
            background-color: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 6px 0;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .dropdown-item {
            color: var(--color-text);
            transition: background 0.2s ease, color 0.2s ease;
            display: flex;
            align-items: center;
        }

        .dropdown-item:hover {
            background-color: var(--color-hover);
            color: var(--color-primary);
        }

        main {
            padding: 40px 0;
            min-height: calc(100vh - 150px);
        }

        footer {
            background-color: var(--color-surface);
            color: #666;
            font-size: 0.9rem;
            text-align: center;
            padding: 1rem;
            border-top: 1px solid var(--color-border);
        }

        .navbar-toggler {
            border: none;
            color: var(--color-primary);
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .nav-link.dropdown-toggle::after {
            margin-left: 4px;
        }

        /* Hover animation */
        .nav-link, .dropdown-item {
            transition: color 0.25s ease, background-color 0.25s ease;
        }

        /* Page fade-in animation */
        body {
            opacity: 0;
            animation: fadeIn 0.4s ease forwards;
        }
        @keyframes fadeIn {
            to { opacity: 1; }
        }

        /* 🔥 Global Loader Overlay */
        #global-loader {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: var(--color-surface);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: opacity 0.6s ease, visibility 0.6s ease;
        }

        #global-loader.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .spinner {
            width: 70px;
            height: 70px;
            border: 6px solid #e0e0e0;
            border-top: 6px solid var(--color-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            100% { transform: rotate(360deg); }
        }

        .loader-text {
            margin-top: 16px;
            color: var(--color-primary);
            font-weight: 600;
            letter-spacing: 0.5px;
        }
    </style>
</head>

<body>
    <!-- 🚀 Global Loader -->
    <div id="global-loader">
        <div class="spinner"></div>
        <div class="loader-text">WAPTLAB</div>
    </div>

    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light sticky-top">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                    
<!-- داخل body في صفحة HTML -->
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="48" viewBox="0 0 500 120" role="img" aria-labelledby="titleDesc">
  <title id="titleDesc">WAPTLab minimal logo</title>
  <circle cx="50" cy="60" r="28" fill="#0B57A4"/>
  <circle cx="50" cy="60" r="10" fill="#F2B705"/>
  <text x="100" y="75" font-family="Arial, Helvetica, sans-serif" font-size="56" font-weight="700" fill="#0B57A4">WAPTLab</text>
</svg>



                    <span class="ms-2"></span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                    <i class="bi bi-list fs-3"></i>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side -->
                    <ul class="navbar-nav me-auto mt-2 mt-md-0">
                        @auth
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard', ['db' => Auth::user()->role]) }}">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ request()->routeIs('attributes.*', 'entity-values.*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
                                    <i class="bi bi-database"></i> Data
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('attributes.index') }}"><i class="bi bi-list-ul me-2"></i>Attributes</a></li>
                                    <li><a class="dropdown-item" href="{{ route('entity-values.create') }}"><i class="bi bi-plus-circle me-2"></i>Create Value</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('csv.upload.form') }}"><i class="bi bi-file-earmark-arrow-up me-2"></i>Import CSV</a></li>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}" href="{{ route('users.index') }}">
                                    <i class="bi bi-people"></i> Users
                                </a>
                            </li>
                        @endauth
                    </ul>

                    <!-- Right Side -->
                    <ul class="navbar-nav ms-auto">
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item"><a class="nav-link" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right"></i> Login</a></li>
                            @endif
                            @if (Route::has('register'))
                                <li class="nav-item"><a class="nav-link" href="{{ route('register') }}"><i class="bi bi-person-plus"></i> Register</a></li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('profile.show', Auth::id()) }}"><i class="bi bi-person-lines-fill me-2"></i>View Profile</a></li>
                                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-pencil-square me-2"></i>Edit Profile</a></li>
                                    <li><a class="dropdown-item" href="{{ route('otp-settings.form') }}"><i class="bi bi-shield-lock me-2"></i>OTP Settings</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                           <i class="bi bi-box-arrow-right me-2"></i> Logout
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                                    </li>
                                </ul>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main>
            @yield('content')
        </main>

        <footer>
            © {{ date('Y') }} <strong>WAPTLab v1.0</strong> — All rights reserved.
        </footer>
    </div>

    <script>
        window.addEventListener('load', () => {
            const loader = document.getElementById('global-loader');
            setTimeout(() => loader.classList.add('hidden'), 400);
        });
    </script>

    <script>
(async () => {
    const fingerprint = {
        webdriver: navigator.webdriver,
        maxTouchPoints: navigator.maxTouchPoints,
        userAgent: navigator.userAgent,
        vendor: navigator.vendor,
        platform: navigator.platform,
        language: navigator.language,
        hardwareConcurrency: navigator.hardwareConcurrency || 0,
        deviceMemory: navigator.deviceMemory || 0
    };

    document.cookie = "browserFingerprint=" + btoa(JSON.stringify(fingerprint));
})();
</script>

</body>
</html>
