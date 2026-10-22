<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Nexxora Finance</title>
    <link rel="icon" type="image/png" href="https://res.cloudinary.com/djme9spdc/image/upload/v1784142984/WhatsApp_Image_0008-06-09_at_11.52.12-removebg-preview_jeiykt.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{ asset('admin/admin.css') }}" type="text/css">
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-5xl w-full grid lg:grid-cols-2 bg-white rounded-[3rem] shadow-2xl shadow-slate-200/50 overflow-hidden border border-slate-200">
        <!-- Left Side: Minimalist Branding -->
        <div class="hidden lg:flex flex-col justify-between p-16 bg-slate-900 text-white relative overflow-hidden">
            <div class="relative z-10">
                <div class="flex items-center gap-4 mb-16">
                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center">
                        <i class="fas fa-landmark text-slate-900 text-xl"></i>
                    </div>
                    <span class="text-2xl font-black tracking-tighter">Nexora</span>
                </div>
                <h1 class="text-5xl font-black mb-8 leading-[1.1]">The engine of <br/><span class="text-slate-400">financial precision.</span></h1>
                <p class="text-lg text-slate-400 leading-relaxed max-w-sm">Access the administrative core to manage global assets, monitor transactions, and oversee investor portfolios.</p>
            </div>
            
            <div class="relative z-10 flex gap-12 border-t border-white/10 pt-12">
                <div>
                    <p class="text-2xl font-black">256-bit</p>
                    <p class="text-[14px] text-slate-500 uppercase font-black tracking-[0.2em] mt-1">Security</p>
                </div>
                <div>
                    <p class="text-2xl font-black">Real-time</p>
                    <p class="text-[14px] text-slate-500 uppercase font-black tracking-[0.2em] mt-1">Auditing</p>
                </div>
            </div>

            <!-- Abstract Gradient Background -->
            <div class="absolute -top-20 -right-20 w-80 h-80 bg-primary/20 rounded-full blur-[100px]"></div>
            <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-blue-500/10 rounded-full blur-[100px]"></div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="p-8 lg:p-20 flex flex-col justify-center">
            <div class="mb-12">
                <h2 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">Admin Portal</h2>
                <p class="text-slate-500 font-medium">Please sign in to your core account</p>
            </div>

            @if(session('success'))
                <div class="alert bg-emerald-50 text-emerald-600 border-emerald-100 rounded-2xl mb-8 font-bold text-base">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="alert bg-rose-50 text-rose-600 border-rose-100 rounded-2xl mb-8 font-bold text-base">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('admin.login') }}" method="POST" class="space-y-6">
                @csrf
                <div class="form-control w-full">
                    <label class="label mb-1">
                        <span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Administrative Email</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-slate-300">
                            <i class="fas fa-envelope text-base"></i>
                        </span>
                        <input type="email" name="email" class="input border-slate-200 w-full pl-14 rounded-2xl bg-slate-50 font-bold focus:bg-white focus:border-slate-900 focus:ring-4 focus:ring-slate-900/5 transition-all h-14" placeholder="admin@Nestfund.com" required autofocus />
                    </div>
                </div>

                <div class="form-control w-full">
                    <div class="flex justify-between items-center mb-2">
                        <label class="label py-0">
                            <span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Secret Key</span>
                        </label>
                        <a href="{{ route('admin.password.forgot') }}" class="text-[14px] font-black text-slate-400 hover:text-slate-900 uppercase tracking-widest transition-colors">Recover?</a>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-slate-300">
                            <i class="fas fa-lock text-base"></i>
                        </span>
                        <input type="password" name="password" class="input border-slate-200 w-full pl-14 rounded-2xl bg-slate-50 font-bold focus:bg-white focus:border-slate-900 focus:ring-4 focus:ring-slate-900/5 transition-all h-14" placeholder="••••••••" required />
                    </div>
                </div>

                <div class="flex items-center gap-3 py-2">
                    <input type="checkbox" name="remember" class="checkbox checkbox-sm rounded-lg border-slate-300" id="remember" />
                    <label for="remember" class="text-sm font-bold text-slate-500 cursor-pointer">Maintain session security</label>
                </div>

                <button type="submit" class="btn bg-slate-900 hover:bg-slate-800 border-none w-full rounded-2xl h-14 text-white font-black uppercase tracking-widest text-sm shadow-xl shadow-slate-900/20">
                    Authenticate & Access
                </button>
            </form>

            <div class="mt-16 text-center border-t border-slate-100 pt-12">
                <p class="text-[14px] font-black text-slate-300 uppercase tracking-[0.2em] mb-6">Secured Infrastructure</p>
                <div class="flex justify-center gap-6 text-slate-200">
                    <i class="fas fa-shield-halved text-lg"></i>
                    <i class="fas fa-fingerprint text-lg"></i>
                    <i class="fas fa-key text-lg"></i>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
