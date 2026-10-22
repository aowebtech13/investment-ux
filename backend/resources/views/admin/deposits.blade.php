@extends('layouts.admin')

@section('title', 'Deposit Logs')

@section('content')
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-black tracking-tight text-slate-900">Deposits</h1>
        <p class="text-slate-500 font-medium">Monitor and manage user deposits</p>
    </div>
</div>

<div class="flex gap-2 mb-6">
    <a href="{{ route('admin.deposits', ['status' => 'pending']) }}" class="btn {{ $status === 'pending' ? 'btn-primary text-white' : 'btn-ghost bg-white border-slate-200' }} rounded-xl px-6 font-black text-sm uppercase tracking-widest">Pending</a>
    <a href="{{ route('admin.deposits', ['status' => 'completed']) }}" class="btn {{ $status === 'completed' ? 'bg-emerald-500 hover:bg-emerald-600 text-white border-none' : 'btn-ghost bg-white border-slate-200' }} rounded-xl px-6 font-black text-sm uppercase tracking-widest">Approved</a>
    <a href="{{ route('admin.deposits', ['status' => 'cancelled']) }}" class="btn {{ $status === 'cancelled' ? 'bg-rose-500 hover:bg-rose-600 text-white border-none' : 'btn-ghost bg-white border-slate-200' }} rounded-xl px-6 font-black text-sm uppercase tracking-widest">Cancelled</a>
</div>

<div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="table table-lg">
            <thead>
                <tr class="bg-slate-50/50 border-b border-slate-100">
                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 py-5 px-8">User</th>
                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Amount</th>
                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Method</th>
                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Status</th>
                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Date</th>
                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 text-right px-8">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($deposits as $deposit)
                <tr class="hover:bg-slate-50/50 transition-colors duration-200">
                    <td class="px-8 py-5">
                        <div class="flex items-center gap-4">
                            <div class="avatar">
                                <div class="bg-slate-100 text-slate-500 rounded-xl w-10 h-10 overflow-hidden flex items-center justify-center font-black text-sm">
                                    @if($deposit->user->avatar_url)
                                        <img src="{{ $deposit->user->avatar_url }}" alt="{{ $deposit->user->name }}" class="object-cover w-full h-full" />
                                    @else
                                        {{ substr($deposit->user->name ?? 'U', 0, 1) }}
                                    @endif
                                </div>
                            </div>
                            <div>
                                <div class="font-black text-slate-900 text-base">{{ $deposit->user->name }}</div>
                                <div class="text-[14px] text-slate-400 font-bold truncate max-w-[150px] uppercase tracking-tighter">{{ $deposit->description }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="font-black text-emerald-600 text-base">+${{ number_format($deposit->amount, 2) }}</div>
                    </td>
                    <td>
                        <div class="flex flex-col gap-1">
                            <span class="badge bg-slate-100 text-slate-600 border-none font-black text-[13px] uppercase tracking-widest px-2 py-1 h-auto rounded-md">{{ $deposit->method ?? 'N/A' }}</span>
                            @if($deposit->receipt_url)
                                <a href="{{ $deposit->receipt_url }}" target="_blank" class="text-xs text-primary font-bold uppercase tracking-widest hover:underline flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    View Receipt
                                </a>
                            @endif
                        </div>
                    </td>
                    <td>
                        @php
                            $statusClasses = [
                                'completed' => 'bg-emerald-50 text-emerald-600',
                                'pending' => 'bg-amber-50 text-amber-600',
                                'cancelled' => 'bg-rose-50 text-rose-600'
                            ][$deposit->status] ?? 'bg-slate-50 text-slate-600';
                        @endphp
                        <span class="badge {{ $statusClasses }} border-none font-black uppercase text-[13px] tracking-widest px-2 py-1 h-auto rounded-md">{{ $deposit->status }}</span>
                    </td>
                    <td>
                        <div class="font-bold text-slate-500 text-sm">{{ $deposit->created_at->format('M d, Y') }}</div>
                    </td>
                    <td class="text-right px-8">
                        @if($deposit->status === 'pending')
                        <div class="flex justify-end gap-2">
                            <form action="{{ route('admin.deposits.update', $deposit->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="completed">
                                <button class="btn bg-emerald-500 hover:bg-emerald-600 text-white border-none btn-sm rounded-lg px-4 font-black text-[14px] uppercase tracking-widest">Approve</button>
                            </form>
                            <form action="{{ route('admin.deposits.update', $deposit->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="cancelled">
                                <button class="btn bg-white border-slate-200 hover:bg-slate-50 text-slate-600 btn-sm rounded-lg px-4 font-black text-[14px] uppercase tracking-widest shadow-sm">Cancel</button>
                            </form>
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-8 flex justify-center">
    {{ $deposits->appends(['status' => $status])->links() }}
</div>
@endsection
