@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-6">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Dashboard</h1>
        <p class="text-slate-500 font-medium text-base mt-1">Platform overview and performance metrics</p>
    </div>
    <div class="flex gap-2">
        <button class="btn btn-sm bg-white border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg shadow-sm px-4 font-semibold">
            <i class="fas fa-download mr-1.5 text-slate-400 text-sm"></i> Export
        </button>
        <a href="{{ route('admin.transactions') }}" class="btn btn-sm bg-slate-900 hover:bg-slate-800 text-white border-none rounded-lg px-4 font-semibold">
            Audit Logs
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
    <!-- Total Users -->
    <div class="card p-6">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[13px] font-bold text-slate-400 uppercase tracking-wider">Total Investors</p>
                <h3 class="text-3xl font-bold text-slate-900 mt-1">{{ number_format($stats['total_users']) }}</h3>
                <div class="flex items-center gap-1.5 mt-2">
                    <span class="text-[14px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">
                        <i class="fas fa-arrow-up text-[8px]"></i> 12%
                    </span>
                    <span class="text-[14px] font-medium text-slate-400">vs last month</span>
                </div>
            </div>
            <div class="w-10 h-10 bg-slate-50 rounded-lg flex items-center justify-center text-slate-400 border border-slate-100">
                <i class="fas fa-users text-base"></i>
            </div>
        </div>
    </div>

    <!-- Active Investments -->
    <div class="card p-6">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[13px] font-bold text-slate-400 uppercase tracking-wider">Active Plans</p>
                <h3 class="text-3xl font-bold text-slate-900 mt-1">{{ number_format($stats['total_investments']) }}</h3>
                <div class="flex items-center gap-1.5 mt-2 text-[14px] font-bold text-emerald-600">
                    <i class="fas fa-check-circle"></i> SYSTEM HEALTHY
                </div>
            </div>
            <div class="w-10 h-10 bg-slate-50 rounded-lg flex items-center justify-center text-slate-400 border border-slate-100">
                <i class="fas fa-chart-line text-base"></i>
            </div>
        </div>
    </div>

    <!-- Total Invested -->
    <div class="card p-6 border-l-4 border-l-slate-900">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[13px] font-bold text-slate-400 uppercase tracking-wider">Assets Managed</p>
                <h3 class="text-3xl font-bold text-slate-900 mt-1">₦{{ number_format($stats['total_invested_amount'], 0) }}</h3>
                <p class="text-[14px] font-medium text-slate-400 mt-2 uppercase tracking-tighter">Aggregate user capital</p>
            </div>
            <div class="w-10 h-10 bg-slate-900 rounded-lg flex items-center justify-center text-white">
                <i class="fas fa-vault text-base"></i>
            </div>
        </div>
    </div>

    <!-- Pending Withdrawals -->
    <div class="card p-6 bg-slate-50/50 border-dashed">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[13px] font-bold text-slate-400 uppercase tracking-wider">Pending Payouts</p>
                <h3 class="text-3xl font-bold text-slate-900 mt-1">{{ number_format($stats['pending_withdrawals']) }}</h3>
                <div class="mt-2">
                    <a href="{{ route('admin.withdrawals') }}" class="text-[14px] font-bold text-slate-600 hover:text-slate-900 underline decoration-slate-300">
                        REVIEW REQUESTS →
                    </a>
                </div>
            </div>
            <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center text-amber-600 border border-amber-100">
                <i class="fas fa-clock text-base"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6 mt-6">
    <div class="lg:col-span-2">
        <div class="card p-6 h-full">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="font-bold text-slate-900">Performance Chart</h3>
                    <p class="text-sm text-slate-400 font-medium">Monthly capital flow analysis</p>
                </div>
                <div class="flex bg-slate-50 p-1 rounded-lg border border-slate-100">
                    <button class="px-3 py-1 text-[14px] font-bold bg-white text-slate-900 rounded shadow-sm border border-slate-100">Monthly</button>
                    <button class="px-3 py-1 text-[14px] font-bold text-slate-400">Yearly</button>
                </div>
            </div>
            <div class="w-full h-72 bg-slate-50/30 rounded-xl flex items-center justify-center border border-dashed border-slate-200">
                <div class="text-center">
                    <p class="text-slate-400 font-bold uppercase tracking-widest text-[14px]">Analytics Feed</p>
                    <p class="text-[13px] text-slate-300 mt-1 uppercase">Synchronizing with node...</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card p-6">
        <h3 class="font-bold text-slate-900 mb-6">Quick Actions</h3>
        <div class="space-y-3">
            <a href="{{ route('admin.users') }}" class="group flex items-center justify-between p-3.5 bg-slate-50 hover:bg-slate-100 rounded-xl transition-all border border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center text-slate-400 border border-slate-100 group-hover:text-slate-900">
                        <i class="fas fa-user-check text-sm"></i>
                    </div>
                    <span class="text-base font-semibold text-slate-700">Verify Investors</span>
                </div>
                <i class="fas fa-arrow-right text-[14px] text-slate-300 group-hover:text-slate-500"></i>
            </a>
            
            <a href="{{ route('admin.withdrawals') }}" class="group flex items-center justify-between p-3.5 bg-slate-50 hover:bg-slate-100 rounded-xl transition-all border border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center text-slate-400 border border-slate-100 group-hover:text-slate-900">
                        <i class="fas fa-wallet text-sm"></i>
                    </div>
                    <span class="text-base font-semibold text-slate-700">Approve Payouts</span>
                </div>
                <i class="fas fa-arrow-right text-[14px] text-slate-300 group-hover:text-slate-500"></i>
            </a>

            <a href="{{ route('admin.transactions') }}" class="group flex items-center justify-between p-3.5 bg-slate-50 hover:bg-slate-100 rounded-xl transition-all border border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center text-slate-400 border border-slate-100 group-hover:text-slate-900">
                        <i class="fas fa-file-invoice text-sm"></i>
                    </div>
                    <span class="text-base font-semibold text-slate-700">Audit Logs</span>
                </div>
                <i class="fas fa-arrow-right text-[14px] text-slate-300 group-hover:text-slate-500"></i>
            </a>
        </div>

        <div class="mt-8 pt-6 border-t border-slate-100">
            <div class="bg-slate-900 p-4 rounded-xl text-white relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-[13px] font-bold uppercase tracking-widest opacity-60">Security</p>
                    <h4 class="text-base font-bold mt-1">SSL Protection</h4>
                    <div class="flex items-center gap-2 mt-3 text-[14px] font-bold bg-white/10 px-2.5 py-1 rounded border border-white/10 w-fit">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>
                        ENCRYPTED
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
