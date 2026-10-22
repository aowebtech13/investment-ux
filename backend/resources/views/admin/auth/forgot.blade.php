<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Admin Nexora</title>
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
            <div class="w-16 h-16 bg-slate-900 text-white rounded-2xl flex items-center justify-center mx-auto mb-8 shadow-xl shadow-slate-900/20">
                <i class="fas fa-key text-xl"></i>
            </div>
            <h2 class="text-3xl font-black tracking-tight text-slate-900 mb-2">Access Recovery</h2>
            <p class="text-slate-500 font-medium text-base">Enter your administrative email to receive a secure reset token.</p>
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

        <form action="{{ route('admin.password.forgot') }}" method="POST" class="space-y-8">
            @csrf
            <div class="form-control">
                <label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Admin Email</span></label>
                <input type="email" name="email" class="input border-slate-200 rounded-2xl bg-slate-50 font-bold focus:bg-white focus:border-slate-900 focus:ring-4 focus:ring-slate-900/5 transition-all h-14" placeholder="admin@nexora.com" required />
            </div>
            <button type="submit" class="btn bg-slate-900 hover:bg-slate-800 border-none w-full rounded-2xl h-14 text-white font-black uppercase tracking-widest text-sm shadow-xl shadow-slate-900/20">
                Generate Token
            </button>
        </form>

        <div class="mt-12 text-center border-t border-slate-100 pt-8">
            <a href="{{ route('admin.login') }}" class="text-[14px] font-black text-slate-400 hover:text-slate-900 uppercase tracking-widest transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back to Authentication
            </a>
        </div>
    </div>
</body>
</html>
