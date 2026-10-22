@extends('layouts.admin')

@section('title', 'Payout Requests')

@section('content')
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-black tracking-tight text-slate-900">Withdrawals</h1>
        <p class="text-slate-500 font-medium">Review and process user payout requests</p>
    </div>
</div>

<div class="flex gap-2 mb-6">
    <a href="{{ route('admin.withdrawals', ['status' => 'pending']) }}" class="btn {{ $status === 'pending' ? 'btn-primary text-white' : 'btn-ghost bg-white border-slate-200' }} rounded-xl px-6 font-black text-sm uppercase tracking-widest">Pending</a>
    <a href="{{ route('admin.withdrawals', ['status' => 'approved']) }}" class="btn {{ $status === 'approved' ? 'bg-emerald-500 hover:bg-emerald-600 text-white border-none' : 'btn-ghost bg-white border-slate-200' }} rounded-xl px-6 font-black text-sm uppercase tracking-widest">Approved</a>
    <a href="{{ route('admin.withdrawals', ['status' => 'rejected']) }}" class="btn {{ $status === 'rejected' ? 'bg-rose-500 hover:bg-rose-600 text-white border-none' : 'btn-ghost bg-white border-slate-200' }} rounded-xl px-6 font-black text-sm uppercase tracking-widest">Cancelled</a>
</div>

<div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="table table-lg">
            <thead>
                <tr class="bg-slate-50/50 border-b border-slate-100">
                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 py-5 px-8">User</th>
                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Amount</th>
                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Method</th>
                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Bank Details</th>
                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Status</th>
                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Date</th>
                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 text-right px-8">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($withdrawals as $withdrawal)
                <tr class="hover:bg-slate-50/50 transition-colors duration-200">
                    <td class="px-8 py-5">
                        <div class="flex items-center gap-4">
                            <div class="avatar">
                                <div class="bg-slate-100 text-slate-500 rounded-xl w-10 h-10 overflow-hidden flex items-center justify-center font-black text-sm">
                                    @if($withdrawal->user->avatar_url)
                                        <img src="{{ $withdrawal->user->avatar_url }}" alt="{{ $withdrawal->user->name }}" class="object-cover w-full h-full" />
                                    @else
                                        {{ substr($withdrawal->user->name ?? 'U', 0, 1) }}
                                    @endif
                                </div>
                            </div>
                            <div>
                                <div class="font-black text-slate-900 text-base">{{ $withdrawal->user->name }}</div>
                                <div class="text-[14px] text-slate-400 font-bold truncate max-w-[150px] uppercase tracking-tighter">{{ $withdrawal->user->Nex_id }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
<div class="font-black text-rose-600 text-base">-₦{{ number_format($withdrawal->amount, 2) }}</div>
                    </td>
                    <td>
                        <span class="badge bg-slate-100 text-slate-600 border-none font-black text-[13px] uppercase tracking-widest px-2 py-1 h-auto rounded-md">{{ $withdrawal->method }}</span>
                    </td>
                    <td class="max-w-[300px]">
                        @if($withdrawal->bank_name || $withdrawal->bank_account_holder)
<div class="space-y-1">
                                <div class="font-medium text-slate-900">{{ $withdrawal->bank_name ?: 'N/A' }}</div>
                                <div class="text-sm text-slate-600">Account #: {{ '**** **** **** ' . substr($withdrawal->bank_account_number ?? '', -4) }}</div>
                                <div class="text-xs text-slate-500 uppercase">{{ $withdrawal->account_type ?: 'N/A' }}</div>
                            </div>
                        @else
                            <span class="text-sm text-slate-400">No bank details</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $statusClasses = [
                                'approved' => 'bg-emerald-50 text-emerald-600',
                                'pending' => 'bg-amber-50 text-amber-600',
                                'rejected' => 'bg-rose-50 text-rose-600'
                            ][$withdrawal->status] ?? 'bg-slate-50 text-slate-600';
                        @endphp
<span class="badge {{ $statusClasses }} border-none font-black uppercase text-[13px] tracking-widest px-2 py-1 h-auto rounded-md">{{ $withdrawal->status }}</span>
                        @if($withdrawal->status === 'rejected' && $withdrawal->rejection_reason)
                            <div class="mt-2 max-w-[200px] text-xs text-rose-600 font-medium leading-snug">{{ $withdrawal->rejection_reason }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="font-bold text-slate-500 text-sm">{{ $withdrawal->created_at->format('M d, Y') }}</div>
                    </td>
                    <td class="text-right px-8">
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn bg-white border-slate-200 hover:bg-slate-50 text-slate-600 btn-sm rounded-lg px-4 font-black text-[14px] uppercase tracking-widest shadow-sm" onclick="details_modal_{{ $withdrawal->id }}.showModal()">View</button>
                            @if($withdrawal->status === 'pending')
                                <form action="{{ route('admin.withdrawals.update', $withdrawal->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="approved">
                                    <button class="btn bg-emerald-500 hover:bg-emerald-600 text-white border-none btn-sm rounded-lg px-4 font-black text-[14px] uppercase tracking-widest">Approve</button>
                                </form>
                                <button class="btn bg-white border-slate-200 hover:bg-slate-50 text-slate-600 btn-sm rounded-lg px-4 font-black text-[14px] uppercase tracking-widest shadow-sm" onclick="reject_modal_{{ $withdrawal->id }}.showModal()">Reject</button>
                            @endif
                        </div>

                        <!-- Reject Modal -->
                        <dialog id="reject_modal_{{ $withdrawal->id }}" class="modal backdrop-blur-sm">
                            <div class="modal-box rounded-[2rem] p-8 text-left border-none shadow-2xl">
                                <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-2xl flex items-center justify-center text-2xl mb-6">
                                    <i class="fas fa-ban"></i>
                                </div>
                                <h3 class="font-black text-2xl mb-2 text-slate-900">Reject Withdrawal</h3>
                                <p class="text-slate-500 mb-8 font-medium text-base">Please provide a reason for rejecting this payout request. The user will be notified.</p>
                                <form action="{{ route('admin.withdrawals.update', $withdrawal->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="rejected">
                                    <div class="form-control mb-8">
                                        <textarea name="rejection_reason" class="textarea border-slate-200 bg-slate-50 rounded-2xl h-32 font-medium focus:border-rose-500 focus:bg-white focus:ring-4 focus:ring-rose-500/10 transition-all" placeholder="e.g. Invalid wallet address or insufficient verification..." required></textarea>
                                    </div>
                                    <div class="flex flex-col gap-3">
                                        <button type="submit" class="btn bg-rose-500 hover:bg-rose-600 border-none rounded-xl h-14 text-white font-black uppercase tracking-widest">Confirm Rejection</button>
                                        <button type="button" class="btn btn-ghost rounded-xl h-12 font-bold text-slate-400" onclick="this.closest('dialog').close()">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </dialog>

                        <!-- View Details Modal -->
                        <dialog id="details_modal_{{ $withdrawal->id }}" class="modal backdrop-blur-sm">
                            <div class="modal-box rounded-[2rem] p-8 text-left border-none shadow-2xl max-w-2xl">
                                <div class="flex items-start justify-between mb-6">
                                    <div>
                                        <h3 class="font-black text-2xl mb-1 text-slate-900">Withdrawal Details</h3>
                                        <p class="text-slate-500 font-medium text-sm">Request #{{ $withdrawal->id }} · {{ $withdrawal->created_at->format('M d, Y h:i A') }}</p>
                                    </div>
                                    @php
                                        $stClasses = [
                                            'approved' => 'bg-emerald-50 text-emerald-600',
                                            'pending' => 'bg-amber-50 text-amber-600',
                                            'rejected' => 'bg-rose-50 text-rose-600'
                                        ][$withdrawal->status] ?? 'bg-slate-50 text-slate-600';
                                    @endphp
                                    <span class="badge {{ $stClasses }} border-none font-black uppercase text-[13px] tracking-widest px-3 py-1 h-auto rounded-md">{{ $withdrawal->status }}</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                                    <div class="bg-slate-50 rounded-2xl p-4">
                                        <div class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Amount</div>
                                        <div class="font-black text-rose-600 text-xl">-₦{{ number_format($withdrawal->amount, 2) }}</div>
                                    </div>
                                    <div class="bg-slate-50 rounded-2xl p-4">
                                        <div class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Method</div>
                                        <div class="font-black text-slate-900 text-lg capitalize">{{ str_replace('_', ' ', $withdrawal->method ?? 'N/A') }}</div>
                                    </div>
                                </div>

                                <div class="bg-slate-50 rounded-2xl p-4 space-y-3 mb-6">
                                    <div class="text-xs font-bold uppercase tracking-widest text-slate-400">Bank / Account Details</div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                        <div><span class="text-slate-400 font-medium">Bank:</span> <span class="font-bold text-slate-900">{{ $withdrawal->bank_name ?? 'N/A' }}</span></div>
                                        <div><span class="text-slate-400 font-medium">Account Holder:</span> <span class="font-bold text-slate-900">{{ $withdrawal->bank_account_holder ?? 'N/A' }}</span></div>
                                        <div><span class="text-slate-400 font-medium">Account #:</span> <span class="font-bold text-slate-900">{{ $withdrawal->bank_account_number ?? 'N/A' }}</span></div>
<div><span class="text-slate-400 font-medium">Account Type:</span> <span class="font-bold text-slate-900 capitalize">{{ $withdrawal->account_type ?: 'N/A' }}</span></div>
                                        @if($withdrawal->details)
                                            <div><span class="text-slate-400 font-medium">Details:</span> <span class="font-bold text-slate-900">{{ $withdrawal->details }}</span></div>
                                        @endif
                                    </div>
                                </div>

                                <div class="bg-slate-50 rounded-2xl p-4 space-y-2 mb-6">
                                    <div class="text-xs font-bold uppercase tracking-widest text-slate-400">User</div>
                                    <div class="text-sm"><span class="font-bold text-slate-900">{{ $withdrawal->user->name ?? 'N/A' }}</span> <span class="text-slate-400 ml-1">({{ $withdrawal->user->Nex_id ?? '—' }})</span></div>
                                    <div class="text-sm text-slate-600">{{ $withdrawal->user->email ?? '' }}</div>
                                </div>

                                @if($withdrawal->status === 'rejected' && $withdrawal->rejection_reason)
                                <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 mb-6">
                                    <div class="text-xs font-bold uppercase tracking-widest text-rose-500 mb-1">Rejection Reason</div>
                                    <p class="text-slate-700 font-medium">{{ $withdrawal->rejection_reason }}</p>
                                </div>
                                @endif

                                <div class="flex justify-end">
                                    <button type="button" class="btn btn-ghost rounded-xl h-12 font-bold text-slate-400" onclick="this.closest('dialog').close()">Close</button>
                                </div>
                            </div>
                        </dialog>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-8 flex justify-center">
    {{ $withdrawals->appends(['status' => $status])->links() }}
</div>
@endsection
