@extends('layouts.admin')

@section('title', 'Investment Batches')

@section('content')
<div class="alert alert-info">
    Investment batches/groups are removed from the admin panel.
</div>


<!-- Create Group Modal -->
<dialog id="create_group_modal" class="modal">
    <div class="modal-box rounded-2xl p-8 max-w-md border border-slate-200 shadow-2xl">
        <h3 class="font-bold text-xl text-slate-900">New Batch</h3>
        <p class="text-sm text-slate-500 mt-1">Initialize a new investment cycle grouping.</p>
        
        <form action="{{ route('admin.groups.create') }}" method="POST" class="mt-6 space-y-5">
            @csrf
            <div class="form-control">
                <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Select Investment Plan</span></label>
                <select name="plan_id" class="select select-bordered rounded-lg font-semibold text-base h-11" required>
                    <option value="" disabled selected>Choose a plan...</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2 justify-end pt-2">
                <button type="button" class="btn btn-sm btn-ghost font-bold text-sm" onclick="create_group_modal.close()">Cancel</button>
                <button type="submit" class="btn btn-sm bg-slate-900 hover:bg-slate-800 text-white border-none rounded-lg px-6 font-bold text-sm">Generate Batch</button>
            </div>
        </form>
    </div>
</dialog>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <!-- Ungrouped Queue -->
    <div class="lg:col-span-1">
        <div class="card h-full flex flex-col overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-bold text-base text-slate-900">Assignment Queue</h3>
                <p class="text-[14px] font-medium text-slate-400 uppercase mt-0.5">Pending batch distribution</p>
            </div>
            
            <form action="{{ route('admin.groups.assign') }}" method="POST" class="flex-1 flex flex-col">
                @csrf
                <div class="flex-1 overflow-y-auto max-h-[500px] p-4 space-y-2">
                    @forelse($ungroupedInvestments as $inv)
                        <label class="flex items-center gap-3 p-3 bg-white border border-slate-100 rounded-xl cursor-pointer hover:border-slate-300 transition-all">
                            <input type="checkbox" name="investment_ids[]" value="{{ $inv->id }}" class="checkbox checkbox-xs checkbox-primary rounded">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-sm text-slate-900 truncate">{{ $inv->user->name }}</p>
                                <p class="text-[13px] font-bold text-slate-400 uppercase tracking-tighter">${{ number_format($inv->amount, 2) }} • {{ $inv->plan->name }}</p>
                            </div>
                        </label>
                    @empty
                        <div class="text-center py-12 opacity-20">
                            <i class="fas fa-inbox text-3xl mb-2"></i>
                            <p class="text-[14px] font-bold uppercase">Queue Empty</p>
                        </div>
                    @endforelse
                </div>

                @if($ungroupedInvestments->count() > 0)
                <div class="p-4 bg-slate-50 border-t border-slate-100 space-y-3">
                    <select name="group_id" class="select select-bordered select-sm w-full rounded-lg font-semibold text-sm h-10" required>
                        <option value="" disabled selected>Target Batch...</option>
                        @foreach($groups->where('status', 'open') as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm bg-slate-900 hover:bg-slate-800 text-white border-none w-full rounded-lg font-bold text-sm h-10">Assign Selected</button>
                </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Active Groups -->
    <div class="lg:col-span-2 space-y-4">
        @forelse($groups as $group)
            <div class="card overflow-hidden">
                <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 {{ $group->status == 'matured' ? 'bg-slate-50/50' : '' }}">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-base border
                            {{ $group->status == 'open' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : ($group->status == 'matured' ? 'bg-slate-100 text-slate-400 border-slate-200' : 'bg-amber-50 text-amber-600 border-amber-100') }}">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-base text-slate-900">{{ $group->name }}</h3>
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-bold uppercase border
                                    {{ $group->status == 'open' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-slate-100 text-slate-400 border-slate-200' }}">
                                    {{ $group->status }}
                                </span>
                            </div>
                            <p class="text-[14px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">
                                {{ $group->investments_count }} Active Members • ID: {{ str_pad($group->id, 4, '0', STR_PAD_LEFT) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        @if($group->status != 'matured')
                            <button onclick="add_user_modal_{{ $group->id }}.showModal()" class="btn btn-xs bg-white border-slate-200 text-slate-600 hover:bg-slate-50 rounded-lg h-8 px-3 font-bold">
                                <i class="fas fa-user-plus mr-1.5 text-[13px]"></i> Add
                            </button>
                            @if($group->investments_count > 0)
                                <button onclick="mature_modal_{{ $group->id }}.showModal()" class="btn btn-xs bg-slate-900 border-none text-white hover:bg-slate-800 rounded-lg h-8 px-4 font-bold">
                                    Distribute & Mature
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                @if($group->status == 'matured')
                    <div class="px-5 pb-5">
                        <div class="grid grid-cols-3 gap-4 p-4 bg-white border border-slate-100 rounded-xl">
                            <div>
                                <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Total Profit</span>
                                <span class="text-base font-bold text-slate-900">${{ number_format($group->total_profit, 2) }}</span>
                            </div>
                            <div>
                                <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Company Fee</span>
                                <span class="text-base font-bold text-slate-600">${{ number_format($group->total_profit * 0.3, 2) }}</span>
                            </div>
                            <div>
                                <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Payout Pool</span>
                                <span class="text-base font-bold text-emerald-600">${{ number_format($group->total_profit * 0.7, 2) }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Maturity Modal -->
                <dialog id="mature_modal_{{ $group->id }}" class="modal">
                    <div class="modal-box rounded-2xl p-8 max-w-md border border-slate-200 shadow-2xl">
                        <h3 class="font-bold text-xl text-slate-900">Distribute Profits</h3>
                        <p class="text-sm text-slate-500 mt-1">Executing payout cycle for <span class="font-bold">{{ $group->name }}</span>.</p>
                        
                        <form action="{{ route('admin.groups.mature', $group->id) }}" method="POST" class="mt-6 space-y-5 text-center">
                            @csrf
                            <div class="form-control">
                                <label class="label justify-center"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Total Declared Profit ($)</span></label>
                                <input type="number" step="0.01" name="total_profit" class="input input-bordered text-center text-2xl font-bold h-16 rounded-xl bg-slate-50" placeholder="0.00" required>
                                <div class="mt-4 flex justify-between p-3 bg-slate-50 rounded-lg text-[13px] font-bold uppercase text-slate-400 border border-slate-100">
                                    <span>70/30 Split Rule</span>
                                    <span class="text-emerald-600 italic">Auto-calculated</span>
                                </div>
                            </div>

                            <div class="flex gap-2 justify-center pt-2">
                                <button type="button" class="btn btn-sm btn-ghost font-bold text-sm" onclick="mature_modal_{{ $group->id }}.close()">Cancel</button>
                                <button type="submit" class="btn btn-sm bg-slate-900 text-white border-none rounded-lg px-8 font-bold text-sm" onclick="return confirm('Execute permanent profit distribution?')">Confirm & Payout</button>
                            </div>
                        </form>
                    </div>
                </dialog>

                <!-- Add User Modal -->
                <dialog id="add_user_modal_{{ $group->id }}" class="modal">
                    <div class="modal-box rounded-2xl p-8 max-w-lg border border-slate-200 shadow-2xl">
                        <h3 class="font-bold text-xl text-slate-900">Manual Entry</h3>
                        <p class="text-sm text-slate-500 mt-1">Force injection of a user into <span class="font-bold">{{ $group->name }}</span>.</p>
                        
                        <form action="{{ route('admin.groups.add_user', $group->id) }}" method="POST" class="mt-6 space-y-5">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-control">
                                    <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Investor</span></label>
                                    <select name="user_id" class="select select-bordered select-sm rounded-lg font-semibold text-sm h-10" required>
                                        <option value="" disabled selected>Select user...</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->Nex_id }} — {{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Capital ($)</span></label>
                                    <input type="number" step="0.01" name="amount" class="input input-bordered input-sm rounded-lg font-bold text-sm h-10 bg-slate-50" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="flex gap-2 justify-end pt-2">
                                <button type="button" class="btn btn-sm btn-ghost font-bold text-sm" onclick="this.closest('dialog').close()">Cancel</button>
                                <button type="submit" class="btn btn-sm bg-slate-900 text-white border-none rounded-lg px-8 font-bold text-sm">Create Investment</button>
                            </div>
                        </form>
                    </div>
                </dialog>
            </div>
        @empty
            <div class="card p-12 text-center opacity-30">
                <i class="fas fa-layer-group text-4xl mb-3"></i>
                <p class="font-bold uppercase tracking-widest text-sm">No batches initialized</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
