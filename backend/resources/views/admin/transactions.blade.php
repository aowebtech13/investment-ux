@extends('layouts.admin')

@section('title', 'Transaction History')

@section('content')
<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900">Transaction History</h1>
            <p class="text-slate-500 font-medium">Complete financial log of all platform activities</p>
        </div>
        <div class="flex gap-2">
            <div class="relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-base"></i>
                <input class="input border-slate-200 bg-white rounded-xl w-64 pl-11 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all font-medium text-base" placeholder="Search transactions..." />
            </div>
            <button class="btn btn-primary text-white rounded-xl px-6 font-black text-sm uppercase tracking-widest">Filter</button>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-emerald-50 rounded-full blur-2xl group-hover:bg-emerald-100 transition-colors"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <p class="text-[14px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Inflows</p>
                <p class="text-2xl font-black text-slate-900">${{ number_format($transactions->sum(function($t) { return $t->amount > 0 ? $t->amount : 0; }), 2) }}</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-rose-50 rounded-full blur-2xl group-hover:bg-rose-100 transition-colors"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <p class="text-[14px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Outflows</p>
                <p class="text-2xl font-black text-slate-900">${{ number_format(abs($transactions->sum(function($t) { return $t->amount < 0 ? $t->amount : 0; })), 2) }}</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-blue-50 rounded-full blur-2xl group-hover:bg-blue-100 transition-colors"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <p class="text-[14px] font-black text-slate-400 uppercase tracking-widest mb-1">Transactions</p>
                <p class="text-2xl font-black text-slate-900">{{ $transactions->total() }}</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-slate-50 rounded-full blur-2xl group-hover:bg-slate-100 transition-colors"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-chart-line"></i>
                </div>
                <p class="text-[14px] font-black text-slate-400 uppercase tracking-widest mb-1">Net Flow</p>
                <p class="text-2xl font-black text-slate-900">${{ number_format($transactions->sum('amount'), 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="table table-lg">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 py-5 px-8">User</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Type</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 text-right">Amount</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Description</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Status</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 text-right px-8">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($transactions as $transaction)
                    <tr class="hover:bg-slate-50/50 transition-all duration-200 group">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="bg-slate-100 text-slate-500 rounded-xl w-10 h-10 overflow-hidden flex items-center justify-center font-black text-sm">
                                    @if($transaction->user && $transaction->user->avatar_url)
                                        <img src="{{ $transaction->user->avatar_url }}" alt="{{ $transaction->user->name }}" class="object-cover w-full h-full" />
                                    @else
                                        {{ substr($transaction->user->name ?? 'U', 0, 1) }}
                                    @endif
                                </div>
                                <div>
                                    <div class="font-black text-slate-900 text-base">{{ $transaction->user->name ?? 'Deleted User' }}</div>
                                    <div class="text-[14px] text-slate-400 font-bold uppercase tracking-tighter">{{ $transaction->user->email ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @php
                                $badgeClass = 'bg-slate-100 text-slate-600';
                                $icon = 'fa-exchange-alt';
                                $typeName = str_replace('_', ' ', $transaction->type);
                                
                                if (strpos($transaction->type, 'deposit') !== false) { $badgeClass = 'bg-emerald-50 text-emerald-600'; $icon = 'fa-arrow-down'; }
                                if (strpos($transaction->type, 'withdrawal') !== false) { $badgeClass = 'bg-rose-50 text-rose-600'; $icon = 'fa-arrow-up'; }
                                if (strpos($transaction->type, 'investment') !== false) { $badgeClass = 'bg-blue-50 text-blue-600'; $icon = 'fa-chart-pie'; }
                                if (strpos($transaction->type, 'profit') !== false) { $badgeClass = 'bg-amber-50 text-amber-600'; $icon = 'fa-chart-line'; }
                                if (strpos($transaction->type, 'admin_') !== false) { $badgeClass = 'bg-slate-900 text-white'; $icon = 'fa-user-shield'; }
                            @endphp
                            <div class="flex flex-col gap-1 items-start">
                                <span class="badge {{ $badgeClass }} border-none font-black text-[13px] uppercase px-2 py-1 h-auto rounded-md tracking-widest">
                                    {{ $typeName }}
                                </span>
                                @if($transaction->receipt_url)
                                    <a href="{{ $transaction->receipt_url }}" target="_blank" class="text-[10px] text-primary font-bold uppercase tracking-widest hover:underline flex items-center gap-1">
                                        <i class="fas fa-eye text-[8px]"></i>
                                        Receipt
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td class="text-right">
                            <span class="font-black text-base {{ $transaction->amount >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $transaction->amount >= 0 ? '+' : '' }}${{ number_format(abs($transaction->amount), 2) }}
                            </span>
                        </td>
                        <td class="max-w-xs">
                            <p class="text-sm font-medium text-slate-500 truncate" title="{{ $transaction->description }}">
                                {{ $transaction->description }}
                            </p>
                        </td>
                        <td>
                            @if($transaction->status === 'completed' || $transaction->status === 'approved')
                                <div class="badge bg-emerald-50 text-emerald-600 border-none font-black text-[13px] uppercase px-2 py-1 h-auto rounded-md tracking-widest">
                                    Success
                                </div>
                            @elseif($transaction->status === 'pending')
                                <div class="badge bg-amber-50 text-amber-600 border-none font-black text-[13px] uppercase px-2 py-1 h-auto rounded-md tracking-widest animate-pulse">
                                    Pending
                                </div>
                            @else
                                <div class="badge bg-rose-50 text-rose-600 border-none font-black text-[13px] uppercase px-2 py-1 h-auto rounded-md tracking-widest">
                                    {{ $transaction->status }}
                                </div>
                            @endif
                        </td>
                        <td class="text-right px-8">
                            <div class="font-bold text-slate-900 text-sm">{{ $transaction->created_at->format('M d, Y') }}</div>
                            <div class="text-[14px] text-slate-400 font-bold uppercase">{{ $transaction->created_at->format('H:i A') }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-20">
                            <div class="w-20 h-20 bg-slate-50 rounded-3xl flex items-center justify-center text-slate-200 text-3xl mx-auto mb-4">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <p class="text-slate-400 font-bold text-base uppercase tracking-widest">No transactions found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center pb-8">
        {{ $transactions->links() }}
    </div>
</div>
@endsection
