@extends('layouts.admin')

@section('title', 'Investment Logs')

@section('content')
<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900">Active Plans</h1>
            <p class="text-slate-500 font-medium">Monitor active and historical investment cycles</p>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-emerald-50 rounded-full blur-2xl group-hover:bg-emerald-100 transition-colors"></div>
            <div class="relative text-left">
                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-wallet text-base"></i>
                </div>
                <p class="text-[14px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Invested</p>
                <p class="text-2xl font-black text-slate-900">₦{{ number_format($investments->sum('amount'), 2) }}</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-blue-50 rounded-full blur-2xl group-hover:bg-blue-100 transition-colors"></div>
            <div class="relative text-left">
                <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-chart-line text-base"></i>
                </div>
                <p class="text-[14px] font-black text-slate-400 uppercase tracking-widest mb-1">Accrued Profit</p>
<p class="text-2xl font-black text-emerald-600">₦{{ number_format($investments->sum('profit'), 2) }}</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-amber-50 rounded-full blur-2xl group-hover:bg-amber-100 transition-colors"></div>
            <div class="relative text-left">
                <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-play text-base"></i>
                </div>
                <p class="text-[14px] font-black text-slate-400 uppercase tracking-widest mb-1">Active Plans</p>
                <p class="text-2xl font-black text-slate-900">{{ $investments->where('status', 'active')->count() }}</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-slate-50 rounded-full blur-2xl group-hover:bg-slate-100 transition-colors"></div>
            <div class="relative text-left">
                <div class="w-10 h-10 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-list text-base"></i>
                </div>
                <p class="text-[14px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Logs</p>
                <p class="text-2xl font-black text-slate-900">{{ $investments->count() }}</p>
            </div>
        </div>
    </div>

    <!-- Investments Table -->
    <div class="bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="table table-lg">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 py-5 px-8">Investor</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Plan</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 text-center">Amount</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 text-center">Profit</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Timeline</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Status</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 text-right px-8">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($investments as $investment)
                    <tr class="hover:bg-slate-50/50 transition-all duration-200 group">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="bg-slate-100 text-slate-500 rounded-xl w-10 h-10 overflow-hidden flex items-center justify-center font-black text-sm border border-slate-200">
                                    @if($investment->user->avatar_url)
                                        <img src="{{ $investment->user->avatar_url }}" alt="{{ $investment->user->name }}" class="object-cover w-full h-full" />
                                    @else
                                        {{ substr($investment->user->name ?? 'U', 0, 1) }}
                                    @endif
                                </div>
                                <div>
                                    <div class="font-black text-slate-900 text-base">{{ $investment->user->name }}</div>
                                    <div class="text-[14px] text-slate-400 font-bold uppercase tracking-tighter">{{ $investment->user->email }}</div>
                                </div>
                            </div>
                        </td>

                        <td>
                            <span class="badge bg-blue-50 text-blue-600 border-none font-black text-[13px] uppercase tracking-widest px-2 py-1 h-auto rounded-md">
                                {{ $investment->plan->name }}
                            </span>
                        </td>

                        <td class="text-center font-black text-base text-slate-900">
₦{{ number_format($investment->amount, 2) }}
                        </td>

                        <td class="text-center">
+₦{{ number_format($investment->profit, 2) }}
                        </td>

                        <td>
                            <div class="font-bold text-slate-900 text-sm">{{ date('M d, Y', strtotime($investment->end_date)) }}</div>
                            <div class="text-[14px] text-slate-400 font-bold uppercase tracking-widest">Expires</div>
                        </td>

                        <td>
                            @php
                                $statusClasses = [
                                    'active' => 'bg-emerald-50 text-emerald-600',
                                    'completed' => 'bg-slate-100 text-slate-600',
                                    'cancelled' => 'bg-rose-50 text-rose-600'
                                ][$investment->status] ?? 'bg-slate-50 text-slate-500';
                            @endphp
                            <span class="badge {{ $statusClasses }} border-none font-black uppercase text-[13px] tracking-widest px-2 py-1 h-auto rounded-md">
                                {{ $investment->status }}
                            </span>
                        </td>

                        <td class="text-right px-8">
                            @if($investment->status === 'active')
                            <div class="flex justify-end gap-2">
                                <button class="btn btn-square btn-ghost btn-sm text-slate-400 hover:text-slate-900" onclick="extend_modal_{{ $investment->id }}.showModal()" title="Extend">
                                    <i class="fas fa-calendar-plus"></i>
                                </button>
                                <form action="{{ route('admin.investments.cancel', $investment->id) }}" method="POST" onsubmit="return confirm('Refund ₦{{ number_format($investment->amount * 0.9, 2) }} (90%) to user and cancel this investment?')">
                                    @csrf
                                    <button class="btn btn-square btn-ghost btn-sm text-rose-300 hover:text-rose-600" title="Cancel">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>

                            <!-- Extend Modal -->
                            <dialog id="extend_modal_{{ $investment->id }}" class="modal backdrop-blur-sm">
                                <div class="modal-box rounded-[2rem] p-8 border-none shadow-2xl">
                                    <h3 class="font-black text-2xl text-slate-900 mb-2">Extend Investment</h3>
                                    <p class="text-base font-medium text-slate-500 mb-8">Modifying maturity date for <strong>{{ $investment->user->name }}</strong></p>
                                    
                                    <form action="{{ route('admin.investments.extend', $investment->id) }}" method="POST" class="space-y-6">
                                        @csrf
                                        <div class="form-control">
                                            <label class="label"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">New End Date</span></label>
                                            <input type="date" name="end_date" class="input border-slate-200 bg-slate-50 rounded-xl font-bold focus:bg-white focus:border-primary transition-all" value="{{ date('Y-m-d', strtotime($investment->end_date)) }}" required>
                                        </div>
                                        <div class="flex flex-col gap-3 pt-2">
                                            <button type="submit" class="btn btn-primary text-white rounded-xl h-14 font-black uppercase tracking-widest">Update Maturity</button>
                                            <button type="button" class="btn btn-ghost rounded-xl h-12 font-bold text-slate-400" onclick="this.closest('dialog').close()">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </dialog>
                            @else
                            <span class="text-[13px] font-black text-slate-300 uppercase tracking-widest">Archived</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-20">
                            <div class="w-20 h-20 bg-slate-50 rounded-3xl flex items-center justify-center text-slate-200 text-3xl mx-auto mb-4">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <p class="text-slate-400 font-bold text-base uppercase tracking-widest">No records found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
