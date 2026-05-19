@extends('layouts.admin')

@section('content')

{{-- CUSTOM STYLES --}}
<style>
    .stat-card {
        background: white;
        border: 1px solid #E5E7EB;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        border-radius: 12px;
        overflow: hidden;
        position: relative;
    }
    
    .dark .stat-card {
        background: rgba(15, 23, 42, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.05);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(10, 102, 194, 0.15);
    }
    
    .dark .stat-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(to bottom, #0A66C2, #10B981);
    }
    
    .content-card {
        background: white;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    
    .dark .content-card {
        background: rgba(15, 23, 42, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.05);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
    }
    
    .gradient-headline {
        background: linear-gradient(135deg, #0A66C2 0%, #10B981 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .badge-primary {
        background: linear-gradient(135deg, #0A66C2 0%, #10B981 100%);
        color: white;
        padding: 2px 10px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .badge-success {
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        color: white;
        padding: 2px 10px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .badge-info {
        background: linear-gradient(135deg, #3B82F6 0%, #0A66C2 100%);
        color: white;
        padding: 2px 10px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #0A66C2 0%, #10B981 100%);
        color: white;
        transition: all 0.3s ease;
        border-radius: 8px;
        font-weight: 500;
    }
    
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 25px rgba(10, 102, 194, 0.3);
    }
    
    .table-row-hover:hover {
        background: linear-gradient(to right, rgba(10, 102, 194, 0.02), rgba(16, 185, 129, 0.02));
    }
    
    .dark .table-row-hover:hover {
        background: linear-gradient(to right, rgba(10, 102, 194, 0.1), rgba(16, 185, 129, 0.1));
    }
    
    .icon-wrapper {
        background: linear-gradient(135deg, rgba(10, 102, 194, 0.1), rgba(16, 185, 129, 0.1));
        border-radius: 10px;
        padding: 8px;
    }
    
    .dark .icon-wrapper {
        background: linear-gradient(135deg, rgba(10, 102, 194, 0.2), rgba(16, 185, 129, 0.2));
    }
    
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
    }
    
    .dark .status-dot {
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.4);
    }
</style>

{{-- PAGE HEADER - UPDATED STYLE --}}
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white tracking-tight">
                Dashboard <span class="gradient-headline">Overview</span>
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">
                System summary & administrative metrics
            </p>
        </div>
        <div class="flex items-center space-x-4">
            <div class="text-xs px-3 py-1 rounded-full bg-gradient-to-r from-primary-50 to-accent-50 dark:from-primary-900/20 dark:to-accent-900/20 text-gray-600 dark:text-gray-300">
                <i class="fas fa-clock mr-1"></i>
                Last updated: <span class="font-medium">Just now</span>
            </div>
            <button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>
    <div class="h-1 w-20 bg-gradient-to-r from-primary-500 to-accent-500 rounded-full"></div>
</div>

{{-- STAT CARDS - UPDATED STYLE --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
    {{-- Card 1 --}}
    <div class="stat-card p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Users</div>
            <div class="icon-wrapper">
                <i class="fas fa-users text-primary-600 dark:text-primary-400 text-lg"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-800 dark:text-white mb-2">—</div>
        <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
            <i class="fas fa-minus mr-1"></i>
            <span>No change</span>
        </div>
    </div>

    {{-- Card 2 --}}
    <div class="stat-card p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-300">Administrators</div>
            <div class="icon-wrapper">
                <i class="fas fa-user-shield text-primary-600 dark:text-primary-400 text-lg"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-800 dark:text-white mb-2">—</div>
        <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
            <i class="fas fa-user-cog mr-1"></i>
            <span>System administrators</span>
        </div>
    </div>

    {{-- Card 3 --}}
    <div class="stat-card p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-300">Active Sessions</div>
            <div class="icon-wrapper">
                <i class="fas fa-sign-in-alt text-accent-600 dark:text-accent-400 text-lg"></i>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-800 dark:text-white mb-2">—</div>
        <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
            <i class="fas fa-circle text-green-500 mr-1" style="font-size: 6px;"></i>
            <span>Currently active</span>
        </div>
    </div>

    {{-- Card 4 --}}
    <div class="stat-card p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-300">System Status</div>
            <div class="icon-wrapper">
                <i class="fas fa-check-circle text-green-500 text-lg"></i>
            </div>
        </div>
        <div class="flex items-center mb-2">
            <div class="status-dot mr-2"></div>
            <div class="text-xl font-bold text-green-600 dark:text-green-400">Operational</div>
        </div>
        <div class="badge-success inline-block">
            All systems normal
        </div>
    </div>
</div>

{{-- MAIN GRID - UPDATED STYLE --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- LEFT: ACTIVITY LOG - UPDATED STYLE --}}
    <div class="lg:col-span-2">
        <div class="content-card">
            {{-- Card Header --}}
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                            Recent Activity
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Audit log of system activities</p>
                    </div>
                    <a href="#" class="text-sm font-medium gradient-text hover:opacity-80 transition-opacity duration-300">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr class="text-left text-sm font-medium text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-3 px-6 font-medium">User</th>
                            <th class="py-3 px-6 font-medium">Action</th>
                            <th class="py-3 px-6 font-medium">IP Address</th>
                            <th class="py-3 px-6 font-medium">Time</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-700 dark:text-gray-300">
                        <tr class="table-row-hover border-b border-gray-100 dark:border-gray-700">
                            <td class="py-3 px-6">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-primary-100 to-accent-100 dark:from-primary-900/30 dark:to-accent-900/30 flex items-center justify-center mr-3">
                                        <span class="text-sm font-medium gradient-text">A</span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-800 dark:text-white">Admin</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Administrator</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-6">
                                <span class="badge-primary">
                                    <i class="fas fa-sign-in-alt mr-1"></i> Login
                                </span>
                            </td>
                            <td class="py-3 px-6 font-mono text-gray-800 dark:text-gray-300">192.168.1.1</td>
                            <td class="py-3 px-6">
                                <div class="flex flex-col">
                                    <span class="text-gray-800 dark:text-gray-300">Just now</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">10:30:45 AM</span>
                                </div>
                            </td>
                        </tr>
                        {{-- Additional sample rows --}}
                        <tr class="table-row-hover border-b border-gray-100 dark:border-gray-700">
                            <td class="py-3 px-6">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-primary-100 to-accent-100 dark:from-primary-900/30 dark:to-accent-900/30 flex items-center justify-center mr-3">
                                        <span class="text-sm font-medium gradient-text">S</span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-800 dark:text-white">System</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Automated</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-6">
                                <span class="badge-info">
                                    <i class="fas fa-sync-alt mr-1"></i> Backup
                                </span>
                            </td>
                            <td class="py-3 px-6 font-mono text-gray-800 dark:text-gray-300">127.0.0.1</td>
                            <td class="py-3 px-6">
                                <div class="flex flex-col">
                                    <span class="text-gray-800 dark:text-gray-300">2 hours ago</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">08:30:00 AM</span>
                                </div>
                            </td>
                        </tr>
                        <tr class="table-row-hover">
                            <td class="py-3 px-6">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-primary-100 to-accent-100 dark:from-primary-900/30 dark:to-accent-900/30 flex items-center justify-center mr-3">
                                        <span class="text-sm font-medium gradient-text">M</span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-800 dark:text-white">Maintenance</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Scheduled</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-6">
                                <span class="badge-success">
                                    <i class="fas fa-tools mr-1"></i> Update
                                </span>
                            </td>
                            <td class="py-3 px-6 font-mono text-gray-800 dark:text-gray-300">192.168.1.100</td>
                            <td class="py-3 px-6">
                                <div class="flex flex-col">
                                    <span class="text-gray-800 dark:text-gray-300">Yesterday</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">14:30:00 PM</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Card Footer --}}
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                    <span>Showing 3 of 124 activities</span>
                    <div class="flex items-center space-x-2">
                        <button class="p-1 hover:text-gray-700 dark:hover:text-gray-300">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="p-1 hover:text-gray-700 dark:hover:text-gray-300">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT: SYSTEM INFO - UPDATED STYLE --}}
    <div>
        <div class="content-card">
            {{-- Card Header --}}
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                    System Information
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Technical details & environment</p>
            </div>

            {{-- Content --}}
            <div class="p-6">
                <div class="space-y-4">
                    <div class="pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            <i class="fas fa-cube mr-1"></i> Application
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-code text-primary-500 mr-2"></i>
                            <div class="text-sm font-medium text-gray-800 dark:text-white">Engineering Apps V.1.0</div>
                        </div>
                    </div>

                    <div class="pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            <i class="fas fa-server mr-1"></i> Environment
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="status-dot mr-2"></div>
                                <span class="text-sm font-medium text-green-600 dark:text-green-400">Local Development</span>
                            </div>
                            <span class="text-xs px-2 py-1 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded">Active</span>
                        </div>
                    </div>

                    <div class="pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            <i class="fas fa-tag mr-1"></i> Version
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-hashtag text-gray-400 dark:text-gray-500 mr-2"></i>
                                <div class="text-sm font-medium text-gray-800 dark:text-white">v1.0.0</div>
                            </div>
                            <span class="badge-primary">
                                <i class="fas fa-check mr-1"></i> Latest
                            </span>
                        </div>
                    </div>

                    <div class="pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            <i class="fas fa-code-branch mr-1"></i> Last Deploy
                        </div>
                        <div class="text-sm text-gray-800 dark:text-white mb-1">February 3, 2026</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <i class="far fa-clock mr-1"></i> 14:30:00 UTC
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            <i class="fas fa-shield-alt mr-1"></i> Security
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-lock text-green-500 mr-2"></i>
                                <div class="text-sm font-medium text-gray-800 dark:text-white">Secure</div>
                            </div>
                            <span class="text-xs px-2 py-1 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded">
                                <i class="fas fa-check mr-1"></i> Verified
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Action Button --}}
                <div class="mt-6 pt-5 border-t border-gray-200 dark:border-gray-700">
                    <button class="btn-primary w-full inline-flex items-center justify-center px-4 py-3 font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-cog mr-2"></i>
                        System Settings
                    </button>
                </div>
            </div>
        </div>

        {{-- QUICK ACTIONS CARD --}}
        <div class="content-card mt-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                    Quick Actions
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Frequently used system tools</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-3">
                    <a href="#" class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-200 text-center">
                        <i class="fas fa-user-plus text-primary-500 text-lg mb-2"></i>
                        <div class="text-sm font-medium text-gray-800 dark:text-white">Add User</div>
                    </a>
                    <a href="#" class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-200 text-center">
                        <i class="fas fa-chart-bar text-accent-500 text-lg mb-2"></i>
                        <div class="text-sm font-medium text-gray-800 dark:text-white">Reports</div>
                    </a>
                    <a href="#" class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-200 text-center">
                        <i class="fas fa-database text-primary-500 text-lg mb-2"></i>
                        <div class="text-sm font-medium text-gray-800 dark:text-white">Backup</div>
                    </a>
                    <a href="#" class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-200 text-center">
                        <i class="fas fa-history text-accent-500 text-lg mb-2"></i>
                        <div class="text-sm font-medium text-gray-800 dark:text-white">Logs</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- FOOTER SPACING --}}
<div class="mt-8"></div>

@endsection

@push('scripts')
<script>
    // Update time dynamically
    function updateTime() {
        const now = new Date();
        const options = { 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit',
            hour12: true 
        };
        const timeString = now.toLocaleTimeString('en-US', options);
        
        // Update all time elements
        document.querySelectorAll('.last-updated-time').forEach(el => {
            el.textContent = timeString;
        });
    }
    
    // Update every minute
    setInterval(updateTime, 60000);
    updateTime(); // Initial update
    
    // Card hover effects
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.stat-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    });
</script>
@endpush