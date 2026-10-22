<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - Admin Nexora</title>
    <link rel="icon" type="image/png" href="https://res.cloudinary.com/djme9spdc/image/upload/v1784142984/WhatsApp_Image_0008-06-09_at_11.52.12-removebg-preview_jeiykt.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{ asset('admin/admin.css') }}" type="text/css">
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-[3rem] shadow-2xl shadow-slate-200/50 p-10 lg:p-12 border border-slate-200">
        <div class="text-center mb-10">
            <div class="flex items-center justify-center mx-auto mb-8">
                <img src="https://res.cloudinary.com/djme9spdc/image/upload/v1784142984/WhatsApp_Image_0008-06-09_at_11.52.12-removebg-preview_jeiykt.png" alt="Nexora" class="h-16 w-auto">
            </div>
            <h2 class="text-3xl font-black tracking-tight text-slate-900 mb-2">Finalize Recovery</h2>
            <p class="text-slate-500 font-medium text-base">Update your credentials to regain access.</p>
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

        <form action="{{ route('admin.password.reset') }}" method="POST" class="space-y-6">
            @csrf
            <div class="form-control">
                <label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Confirmed Email</span></label>
                <input type="email" name="email" class="input border-slate-200 rounded-2xl bg-slate-50 font-bold focus:bg-white focus:border-slate-900 transition-all h-14" value="{{ old('email') }}" placeholder="admin@Nexora" required />
            </div>
            
            <div class="form-control text-center">
                <label class="label mb-2 justify-center"><span class="label-text font-black text-slate-900 text-sm uppercase tracking-[0.2em]">6-Digit Token</span></label>
                <input type="text" name="token" class="input border-slate-200 rounded-2xl bg-slate-900 text-white text-center text-3xl font-black tracking-[1rem] h-20 focus:ring-4 focus:ring-slate-900/10 transition-all" maxlength="6" placeholder="000000" required />
            </div>

            <div class="grid grid-cols-1 gap-4 mt-4">
                <div class="form-control">
                    <label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">New Password</span></label>
                    <input type="password" name="password" class="input border-slate-200 rounded-2xl bg-slate-50 h-14 font-bold focus:bg-white focus:border-slate-900 transition-all" placeholder="••••••••" required />
                </div>

                <div class="form-control">
                    <label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Confirm New Password</span></label>
                    <input type="password" name="password_confirmation" class="input border-slate-200 rounded-2xl bg-slate-50 h-14 font-bold focus:bg-white focus:border-slate-900 transition-all" placeholder="••••••••" required />
                </div>
            </div>

            <button type="submit" class="btn bg-slate-900 hover:bg-slate-800 border-none w-full rounded-2xl h-14 text-white font-black uppercase tracking-widest text-sm shadow-xl shadow-slate-900/20 mt-6">
                Update Security Credentials
            </button>
        </form>
    </div>
</body>
</html>
