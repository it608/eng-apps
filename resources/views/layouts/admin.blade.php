<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Engineering Apps V.1.0 - Admin')</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
      tailwind.config = {
        darkMode: 'class',
        theme: {
          extend: {
            colors: {
              primary: {
                DEFAULT: '#0A66C2',
                50: '#EFF6FF',
                100: '#DBEAFE',
                200: '#BFDBFE',
                300: '#93C5FD',
                400: '#60A5FA',
                500: '#3B82F6',
                600: '#2563EB',
                700: '#1D4ED8',
                800: '#1E40AF',
                900: '#1E3A8A',
              },
              accent: {
                DEFAULT: '#10B981',
                50: '#ECFDF5',
                100: '#D1FAE5',
                200: '#A7F3D0',
                300: '#6EE7b7',
                400: '#34D399',
                500: '#10B981',
                600: '#059669',
                700: '#047857',
                800: '#065F46',
                900: '#064E3B',
              }
            },
            fontFamily: {
              'sans': ['Inter', 'system-ui', 'sans-serif'],
            },
            zIndex: {
              'dropdown': 1000,
              'modal': 1050,
              'popover': 1060,
              'tooltip': 1070,
            }
          }
        }
      }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }
        
        .dark body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        
        .sidebar {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            z-index: 40;
        }
        
        .dark .sidebar {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        
        .topbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            z-index: 30;
        }
        
        .dark .topbar {
            background: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #0A66C2 0%, #10B981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .nav-link {
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-link:hover {
            background: linear-gradient(to right, rgba(10, 102, 194, 0.1), rgba(16, 185, 129, 0.1));
            transform: translateX(4px);
        }
        
        .nav-link.active {
            background: linear-gradient(to right, rgba(10, 102, 194, 0.2), rgba(16, 185, 129, 0.2));
            border-left: 3px solid;
            border-image: linear-gradient(to bottom, #0A66C2, #10B981) 1;
        }
        
        .dark .nav-link.active {
            background: linear-gradient(to right, rgba(10, 102, 194, 0.3), rgba(16, 185, 129, 0.3));
        }
        
        .sidebar-header {
            background: linear-gradient(135deg, rgba(10, 102, 194, 0.2), rgba(16, 185, 129, 0.2));
            backdrop-filter: blur(10px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0A66C2 0%, #10B981 100%);
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(10, 102, 194, 0.3);
        }
        
        .user-avatar {
            background: linear-gradient(135deg, rgba(10, 102, 194, 0.2), rgba(16, 185, 129, 0.2));
            backdrop-filter: blur(10px);
        }
        
        .logo-container {
            background: linear-gradient(135deg, rgba(10, 102, 194, 0.1), rgba(16, 185, 129, 0.1));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(10, 102, 194, 0.2);
        }
        
        .dark .logo-container {
            background: linear-gradient(135deg, rgba(10, 102, 194, 0.2), rgba(16, 185, 129, 0.2));
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* USER DROPDOWN FIX */
        .user-dropdown-container {
            position: relative;
            z-index: 1000 !important;
        }
        
        .user-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 8px;
            min-width: 220px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.08);
            z-index: 1000 !important;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .dark .user-dropdown {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }
        
        .user-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 3px;
        }
        
        .dark ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #0A66C2, #10B981);
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(to bottom, #0A66C2, #059669);
        }
        
        /* Animation for page transitions */
        .page-transition {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Ensure main content has lower z-index */
        main {
            position: relative;
            z-index: 10;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 64px;
            }
            
            .sidebar .sidebar-text {
                display: none;
            }
            
            .logo-container {
                width: 40px !important;
                height: 40px !important;
            }
            
            .user-dropdown {
                right: -50px;
                min-width: 180px;
            }
        }
    </style>
    
    @stack('styles')
</head>

<body class="h-screen flex overflow-hidden transition-colors duration-300">

    {{-- SIDEBAR --}}
    <aside class="sidebar w-64 text-white flex flex-col flex-shrink-0">
        {{-- Sidebar Header with Logo --}}
        <div class="sidebar-header h-20 flex items-center px-6 border-b border-gray-800">
            <div class="flex items-center space-x-3">
                <div class="logo-container w-12 h-12 rounded-xl flex items-center justify-center">
                    <div class="logo-fallback text-center">
                        <div class="text-lg font-bold gradient-text">EA</div>
                    </div>
                    <img src="{{ asset('resources/img/skb.png') }}" alt="Engineering Apps Logo" 
                         class="w-8 h-8 object-contain hidden" 
                         onload="document.querySelector('.logo-fallback').style.display = 'none'; this.classList.remove('hidden');"
                         onerror="document.querySelector('.logo-fallback').classList.remove('hidden');">
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white tracking-tight sidebar-text">ENGINEERING APPS</h1>
                    <p class="text-xs text-gray-400 mt-1 sidebar-text">Administration Panel</p>
                </div>
            </div>
        </div>

        {{-- Navigation Menu --}}
        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
            {{-- ================= DASHBOARD ================= --}}
            <a href="{{ auth()->user()->role === 'admin' ? '/admin' : '/dashboard' }}"
               class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
               {{ request()->is('admin') || request()->is('dashboard') ? 'active' : '' }}">
                <i class="fas fa-home w-5 text-center text-gray-300"></i>
                <span class="sidebar-text">Dashboard</span>
            </a>
        
            {{-- ================= USER MENU ================= --}}
            @if(auth()->user()->role === 'user')
                <a href="/master"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('master*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">Data Master</span>
                </a>   
                            
                <a href="/stock"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('stock*') ? 'active' : '' }}">
                    <i class="fas fa-receipt w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">Stock Sparepart</span>
                </a>            

                <a href="/workorder"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('workorder*') ? 'active' : '' }}">
                    <i class="fas fa-receipt w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">Work Order (WO)</span>
                </a>
                
                <a href="/transaksi"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('transaksi*') ? 'active' : '' }}">
                    <i class="fas fa-receipt w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">Transaksi</span>
                </a>                    
            @endif
            
            {{-- ================= APPROVAL MENU ================= --}}
            @if(auth()->user()->role === 'approval')
                <a href="/master"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('master*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">Data Master</span>
                </a>   
                                    
                <a href="/stock"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('stock*') ? 'active' : '' }}">
                    <i class="fas fa-receipt w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">Stock Sparepart</span>
                </a>            

                <a href="/workorder"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('workorder*') ? 'active' : '' }}">
                    <i class="fas fa-receipt w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">Work Order (WO)</span>
                </a>
                
                <a href="/transaksi"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('transaksi*') ? 'active' : '' }}">
                    <i class="fas fa-receipt w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">Transaksi</span>
                </a>                     
            @endif
        
            {{-- ================= ADMIN MENU ================= --}}
            @if(auth()->user()->role === 'admin')
                <a href="/admin/users"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('admin/users*') ? 'active' : '' }}">
                    <i class="fas fa-users-cog w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">User Management</span>
                </a>
        
                <a href="/admin/reports"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('admin/reports*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">Reports & Analytics</span>
                </a>
        
                <a href="/admin/settings"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('admin/settings*') ? 'active' : '' }}">
                    <i class="fas fa-cogs w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">System Settings</span>
                </a>
        
                <div class="px-4 pt-6 pb-2">
                    <div class="text-xs font-medium text-gray-400 uppercase tracking-wider sidebar-text">
                        Tools
                    </div>
                </div>
        
                <a href="/admin/logs"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('admin/logs*') ? 'active' : '' }}">
                    <i class="fas fa-history w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">Audit Logs</span>
                </a>
                
                
                <div class="px-4 pt-6 pb-2">
                    <div class="text-xs font-medium text-gray-400 uppercase tracking-wider sidebar-text">
                        Administrasi
                    </div>
                </div>
                
                <a href="/master"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('master*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">Data Master</span>
                </a>                      
                
                <a href="/transaksi"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('transaksi*') ? 'active' : '' }}">
                    <i class="fas fa-receipt w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">Transaksi</span>
                </a>
                
                <a href="/stock"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('stock*') ? 'active' : '' }}">
                    <i class="fas fa-receipt w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">Stock Sparepart</span>
                </a>
                
                <a href="/workorder"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('workorder*') ? 'active' : '' }}">
                    <i class="fas fa-receipt w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">Work Order (WO)</span>
                </a>
                
                <a href="/report"
                   class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
                   {{ request()->is('report*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line w-5 text-center text-gray-300"></i>
                    <span class="sidebar-text">Report</span>
                </a>
            @endif

            {{-- ================= WAREHOUSE 2 MENU (UNTUK SEMUA ROLE) ================= --}}
            <div class="px-4 pt-6 pb-2">
                <div class="text-xs font-medium text-gray-400 uppercase tracking-wider sidebar-text">
                    Warehouse 2
                </div>
            </div>

            <a href="/warehouse2/stock"
               class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
               {{ request()->is('warehouse2/stock*') ? 'active' : '' }}">
                <i class="fas fa-boxes w-5 text-center text-gray-300"></i>
                <span class="sidebar-text">Stock</span>
            </a>

            <a href="/warehouse2/receiving"
               class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
               {{ request()->is('warehouse2/receiving*') ? 'active' : '' }}">
                <i class="fas fa-truck-loading w-5 text-center text-gray-300"></i>
                <span class="sidebar-text">Terima Barang</span>
            </a>

            <a href="/warehouse2/issuing"
               class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg
               {{ request()->is('warehouse2/issuing*') ? 'active' : '' }}">
                <i class="fas fa-truck w-5 text-center text-gray-300"></i>
                <span class="sidebar-text">Keluar Barang</span>
            </a>
        </nav>

        {{-- Sidebar Footer --}}
        <div class="p-4 border-t border-gray-800">
            <div class="flex items-center space-x-3 mb-3">
                <div class="user-avatar w-8 h-8 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium gradient-text">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </span>
                </div>
                <div class="sidebar-text">
                    <div class="text-xs font-medium text-white">{{ auth()->user()->name }}</div>
                </div>
            </div>
            
            <div class="text-xs text-gray-400 mb-2 sidebar-text">
                <i class="fas fa-shield-alt mr-1"></i>
                Session: {{ substr(session()->getId(), 0, 8) }}...
            </div>
            
            <div class="text-xs text-gray-400 sidebar-text">
                Copyright {{ date('Y') }} Engineering Apps V.1.0
            </div>
        </div>
    </aside>

    {{-- MAIN AREA --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- TOPBAR --}}
        <header class="topbar h-16 px-6 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                {{-- Breadcrumb --}}
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    <a href="{{ auth()->user()->role === 'admin' ? '/admin' : '/dashboard' }}"
                       class="hover:text-primary-600 dark:hover:text-primary-400 font-medium">
                        {{ auth()->user()->role === 'admin' ? 'Admin' : 'User' }}
                    </a>

                    @php
                        $path = request()->path();
                        $path = preg_replace('#^(admin|dashboard)/?#', '', $path);
                        $label = ucwords(str_replace(['-', '/'], ' ', $path));
                    @endphp

                    @if($path)
                        <span class="mx-2">/</span>
                        <span class="font-medium text-gray-800 dark:text-gray-100">
                            {{ $label }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex items-center space-x-4">
                {{-- Quick Actions --}}
                <div class="flex items-center space-x-2">
                    <button class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300 transition-colors duration-200">
                        <i class="fas fa-bell"></i>
                    </button>
                    <button class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300 transition-colors duration-200">
                        <i class="fas fa-question-circle"></i>
                    </button>
                </div>
                
                {{-- Dark Mode Toggle --}}
                <button id="sidebarDarkModeToggle" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors duration-200">
                    <i id="sidebarDarkIcon" class="fas fa-moon"></i>
                </button>
                
                {{-- User Info --}}
                <div class="flex items-center space-x-3 pl-3 border-l border-gray-200 dark:border-gray-700 user-dropdown-container">
                    <div class="text-right hidden md:block">
                        <div class="text-sm font-medium text-gray-800 dark:text-white">{{ auth()->user()->name }}</div>
                    </div>
                    
                    <div class="relative">
                        <button class="user-avatar w-10 h-10 rounded-full flex items-center justify-center cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2" 
                                id="userMenuButton"
                                aria-label="User menu"
                                aria-expanded="false"
                                aria-haspopup="true">
                            <span class="text-sm font-medium gradient-text">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                        </button>
                        
                        {{-- User Dropdown --}}
                        <div class="user-dropdown" id="userDropdown" role="menu" aria-orientation="vertical" aria-labelledby="userMenuButton">
                            <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-800 dark:text-white">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
                                <p class="text-xs px-2 py-1 mt-1 inline-block rounded-full
                                    bg-gradient-to-r from-primary-50 to-accent-50
                                    dark:from-primary-900/30 dark:to-accent-900/30
                                    text-primary-600 dark:text-primary-300">
                                    {{ auth()->user()->role === 'admin' ? 'Administrator' : ucfirst(auth()->user()->role) }}
                                </p>
                            </div>
                            <div class="p-2">
                                <a href="/profile" class="flex items-center px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors duration-150" role="menuitem">
                                    <i class="fas fa-user-circle mr-2 text-gray-400 w-4 text-center"></i>
                                    My Profile
                                </a>
                                <a href="/settings" class="flex items-center px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors duration-150" role="menuitem">
                                    <i class="fas fa-cog mr-2 text-gray-400 w-4 text-center"></i>
                                    Settings
                                </a>
                                <a href="/help" class="flex items-center px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors duration-150" role="menuitem">
                                    <i class="fas fa-question-circle mr-2 text-gray-400 w-4 text-center"></i>
                                    Help & Support
                                </a>
                                <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                                <form action="/logout" method="POST">
                                    @csrf
                                    <button type="submit" class="flex items-center w-full px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors duration-150" role="menuitem">
                                        <i class="fas fa-sign-out-alt mr-2 text-red-500 w-4 text-center"></i>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- PAGE CONTENT --}}
        <main class="flex-1 overflow-y-auto p-6 page-transition">
            @yield('content')
        </main>

        {{-- FOOTER --}}
        <footer class="px-6 py-3 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900/50 text-xs text-gray-500 dark:text-gray-400">
            <div class="flex justify-between items-center">
                <div>
                    <i class="fas fa-shield-alt mr-1"></i>
                    Secured by PT. SEKARBUMI TBK - Engineering Apps V.1.0
                </div>
                <div>
                    <span class="mr-4">
                        <i class="fas fa-server mr-1"></i> Server: {{ gethostname() }}
                    </span>
                    <span>
                        <i class="far fa-clock mr-1"></i> <span id="currentTime">{{ date('H:i:s') }}</span>
                    </span>
                </div>
            </div>
        </footer>
    </div>

    {{-- SCRIPTS --}}
    <script>
        // Dark Mode Toggle
        const sidebarDarkModeToggle = document.getElementById('sidebarDarkModeToggle');
        const sidebarDarkIcon = document.getElementById('sidebarDarkIcon');
        const htmlElement = document.documentElement;
        
        const currentTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        
        if (currentTheme === 'dark') {
            sidebarDarkIcon.className = 'fas fa-sun';
        } else {
            sidebarDarkIcon.className = 'fas fa-moon';
        }
        
        sidebarDarkModeToggle.addEventListener('click', () => {
            htmlElement.classList.toggle('dark');
            
            if (htmlElement.classList.contains('dark')) {
                localStorage.setItem('theme', 'dark');
                sidebarDarkIcon.className = 'fas fa-sun';
            } else {
                localStorage.setItem('theme', 'light');
                sidebarDarkIcon.className = 'fas fa-moon';
            }
            
            const otherIcons = document.querySelectorAll('[id$="DarkIcon"]');
            otherIcons.forEach(icon => {
                if (icon !== sidebarDarkIcon) {
                    icon.className = htmlElement.classList.contains('dark') ? 'fas fa-sun' : 'fas fa-moon';
                }
            });
        });
        
        // User dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const userMenuButton = document.getElementById('userMenuButton');
            const userDropdown = document.getElementById('userDropdown');
            
            if (userMenuButton && userDropdown) {
                let isDropdownVisible = false;
                
                userMenuButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    isDropdownVisible = !isDropdownVisible;
                    
                    if (isDropdownVisible) {
                        userDropdown.classList.add('show');
                        userMenuButton.setAttribute('aria-expanded', 'true');
                        positionDropdown();
                    } else {
                        userDropdown.classList.remove('show');
                        userMenuButton.setAttribute('aria-expanded', 'false');
                    }
                });
                
                document.addEventListener('click', function(e) {
                    if (!userMenuButton.contains(e.target) && !userDropdown.contains(e.target)) {
                        userDropdown.classList.remove('show');
                        userMenuButton.setAttribute('aria-expanded', 'false');
                        isDropdownVisible = false;
                    }
                });
                
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && isDropdownVisible) {
                        userDropdown.classList.remove('show');
                        userMenuButton.setAttribute('aria-expanded', 'false');
                        isDropdownVisible = false;
                    }
                });
                
                userDropdown.addEventListener('mouseenter', function() {
                    isDropdownVisible = true;
                    userDropdown.classList.add('show');
                });
                
                userDropdown.addEventListener('mouseleave', function() {
                    isDropdownVisible = false;
                    userDropdown.classList.remove('show');
                    userMenuButton.setAttribute('aria-expanded', 'false');
                });
                
                function positionDropdown() {
                    const buttonRect = userMenuButton.getBoundingClientRect();
                    const dropdownRect = userDropdown.getBoundingClientRect();
                    const viewportHeight = window.innerHeight;
                    
                    if (buttonRect.bottom + dropdownRect.height > viewportHeight - 20) {
                        userDropdown.style.top = 'auto';
                        userDropdown.style.bottom = '100%';
                        userDropdown.style.marginTop = '0';
                        userDropdown.style.marginBottom = '8px';
                    } else {
                        userDropdown.style.top = '100%';
                        userDropdown.style.bottom = 'auto';
                        userDropdown.style.marginTop = '8px';
                        userDropdown.style.marginBottom = '0';
                    }
                }
                
                window.addEventListener('resize', function() {
                    if (isDropdownVisible) {
                        positionDropdown();
                    }
                });
            }
            
            function updateCurrentTime() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('id-ID', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    second: '2-digit'
                });
                
                const timeElement = document.getElementById('currentTime');
                if (timeElement) {
                    timeElement.textContent = timeString;
                }
            }
            
            setInterval(updateCurrentTime, 1000);
            updateCurrentTime();
            
            const currentPath = window.location.pathname;
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.href === window.location.href || 
                    (currentPath.includes('admin/users') && link.href.includes('/admin/users')) ||
                    (currentPath.includes('admin/settings') && link.href.includes('/admin/settings'))) {
                    link.classList.add('active');
                }
            });
        });
        
        function initTooltips() {
            const tooltipElements = document.querySelectorAll('[data-tooltip]');
            tooltipElements.forEach(el => {
                el.addEventListener('mouseenter', function() {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'fixed z-1070 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded shadow-lg';
                    tooltip.textContent = this.dataset.tooltip;
                    tooltip.style.top = (this.getBoundingClientRect().top - 30) + 'px';
                    tooltip.style.left = (this.getBoundingClientRect().left + this.offsetWidth / 2) + 'px';
                    tooltip.style.transform = 'translateX(-50%)';
                    tooltip.id = 'tooltip-' + Date.now();
                    
                    document.body.appendChild(tooltip);
                    
                    this.addEventListener('mouseleave', function() {
                        document.getElementById(tooltip.id)?.remove();
                    });
                });
            });
        }
        
        document.addEventListener('DOMContentLoaded', initTooltips);
    </script>
    
    @stack('scripts')
    
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>