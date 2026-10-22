<!DOCTYPE html>
<html lang="en" data-theme="nexora">
<head>
    <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Nexora Admin</title>
    <link rel="icon" type="image/png" href="https://res.cloudinary.com/djme9spdc/image/upload/v1784142984/WhatsApp_Image_0008-06-09_at_11.52.12-removebg-preview_jeiykt.png" />
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="{{ asset('admin/admin.css') }}" rel="stylesheet" type="text/css">
    @yield('styles')
</head>
<body class="bg-[#f8fafc] min-h-screen text-slate-900">
    <div class="drawer lg:drawer-open">
        <input id="admin-drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <!-- Navbar -->
            <div class="navbar bg-white sticky top-0 z-30 border-b border-slate-200 lg:hidden">
                <div class="flex-none">
                    <label for="admin-drawer" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-5 h-5 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </label>
                </div>
                <div class="flex-1 px-4">
                    <span class="text-xl font-bold text-slate-900">Nexora</span>
                </div>
            </div>

            <!-- Page Content -->
            <main class="p-6 lg:p-10 max-w-[1600px] mx-auto w-full space-y-6">
                @if(session('success'))
                    <div class="alert alert-success bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl">
                        <i class="fas fa-check-circle"></i>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-error bg-rose-50 border border-rose-200 text-rose-700 rounded-xl">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span class="font-medium">{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>

        <!-- Sidebar -->
        <div class="drawer-side z-50">
            <label for="admin-drawer" class="drawer-overlay"></label>
            <aside class="w-64 min-h-screen glass-sidebar flex flex-col">
                <div class="p-6 mb-2">
                    <div class="flex items-center gap-2">
                        <img src="https://res.cloudinary.com/djme9spdc/image/upload/v1784142984/WhatsApp_Image_0008-06-09_at_11.52.12-removebg-preview_jeiykt.png" alt="Nexora" class="h-10 w-auto">
                        <span class="font-bold text-lg tracking-tight">Nexora</span>
                    </div>
                </div>

                <ul class="menu px-0 w-full gap-0.5 flex-1 text-slate-600">
                    <li class="px-6 mb-2 mt-2">
                        <span class="text-[13px] font-semibold text-slate-400 uppercase tracking-wider p-0">Menu</span>
                    </li>
                    <li>
                        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }} py-3 px-6 flex items-center gap-3 transition-all">
                            <i class="fas fa-chart-pie w-5"></i> 
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users') ? 'active' : '' }} py-3 px-6 flex items-center gap-3 transition-all">
                            <i class="fas fa-users w-5"></i> 
                            <span>Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.plans') }}" class="{{ request()->routeIs('admin.plans') ? 'active' : '' }} py-3 px-6 flex items-center gap-3 transition-all">
                            <i class="fas fa-list-ul w-5"></i> 
                            <span>Investment Plans</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.products') }}" class="{{ request()->routeIs('admin.products') ? 'active' : '' }} py-3 px-6 flex items-center gap-3 transition-all">
                            <i class="fas fa-box-open w-5"></i> 
                            <span>Products</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.investments') }}" class="{{ request()->routeIs('admin.investments') ? 'active' : '' }} py-3 px-6 flex items-center gap-3 transition-all">
                            <i class="fas fa-briefcase w-5"></i> 
                            <span>Active Plans</span>
                        </a>
                    </li>

                 
                    <li>
                        <a href="{{ route('admin.withdrawals') }}" class="{{ request()->routeIs('admin.withdrawals') ? 'active' : '' }} py-3 px-6 flex items-center gap-3 transition-all">
                            <i class="fas fa-arrow-up-from-bracket w-5"></i> 
                            <span>Withdrawals</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.deposits') }}" class="{{ request()->routeIs('admin.deposits') ? 'active' : '' }} py-3 px-6 flex items-center gap-3 transition-all">
                            <i class="fas fa-arrow-down-to-bracket w-5"></i> 
                            <span>Deposits</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.transactions') }}" class="{{ request()->routeIs('admin.transactions') ? 'active' : '' }} py-3 px-6 flex items-center gap-3 transition-all">
                            <i class="fas fa-history w-5"></i> 
                            <span>Audit Logs</span>
                        </a>
                    </li>
                </ul>

                <div class="p-4 border-t border-slate-100">
                    <div class="flex items-center gap-3 px-2 py-2">
                        <div class="avatar">
                            <div class="rounded-lg w-8 h-8 overflow-hidden bg-slate-100 flex items-center justify-center border border-slate-200">
                                @if(Auth::user()->avatar_url)
                                    <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="object-cover w-full h-full" />
                                @else
                                    <span class="font-bold text-xs text-slate-500">{{ substr(Auth::user()->name ?? 'A', 0, 1) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex-1 overflow-hidden">
                            <p class="font-semibold text-sm text-slate-900 truncate">{{ Auth::user()->name }}</p>
                            <p class="text-[12px] text-slate-400 truncate">Administrator</p>
                        </div>
                    </div>
                    <form action="{{ route('admin.logout') }}" method="POST" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-ghost hover:bg-rose-50 hover:text-rose-600 text-slate-500 w-full rounded-lg border-none justify-start px-2 font-medium">
                            <i class="fas fa-sign-out-alt text-xs mr-2"></i> Logout
                        </button>
                    </form>
                </div>
            </aside>
        </div>
    </div>
    @yield('scripts')
</body>
</html>
