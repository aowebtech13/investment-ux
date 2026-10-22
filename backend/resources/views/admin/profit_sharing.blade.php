@extends('layouts.admin')

@section('title', 'Profit Distribution')

@section('content')
<div class="alert alert-info">
    Profit distribution for investment batches/groups has been removed from the admin panel.
</div>

{{-- Investment batches/groups removed from admin; leaving view in place but not rendering --}}
@forelse([] as $group)

        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden transition-all hover:shadow-md">
            <div class="p-8 md:p-10">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-2xl
                            {{ $group->status == 'open' ? 'bg-primary/10 text-primary' : 'bg-emerald-50 text-emerald-600' }}">
                            <i class="fas {{ $group->status == 'open' ? 'fa-layer-group' : 'fa-check-circle' }}"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h3 class="font-black text-2xl text-slate-900">{{ $group->name }}</h3>
                                <span class="badge border-none font-black text-[13px] px-2 py-1 h-auto rounded-md uppercase tracking-widest
                                    {{ $group->status == 'open' ? 'bg-blue-50 text-blue-500' : 'bg-emerald-50 text-emerald-600' }}">
                                    {{ $group->status }}
                                </span>
                            </div>
                            <p class="text-[14px] text-slate-400 font-black uppercase tracking-widest">
                                #BCH-{{ str_pad($group->id, 5, '0', STR_PAD_LEFT) }} • 
                                {{ $group->investments_count }} Investors • 
                                {{ $group->created_at->format('M d, Y') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        @if($group->status == 'open' && $group->investments_count > 0)
                            <button onclick="mature_modal_{{ $group->id }}.showModal()" class="btn btn-primary text-white rounded-xl px-8 h-12 font-black uppercase tracking-widest text-sm">
                                <i class="fas fa-sack-dollar mr-2"></i> Distribute Profit
                            </button>
                        @elseif($group->status == 'matured')
                            <div class="flex items-center gap-4 p-2 px-4 bg-emerald-50 rounded-xl border border-emerald-100">
                                <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center text-base">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div>
                                    <span class="block text-[8px] font-black text-emerald-600 uppercase tracking-widest">Profit Distributed</span>
                                    <span class="text-base font-black text-slate-900">${{ number_format($group->total_profit, 2) }}</span>
                                </div>
                            </div>
                        @endif
                        
                        <button class="btn btn-circle btn-sm bg-slate-50 border-slate-100 hover:bg-white text-slate-400" onclick="document.getElementById('members_{{ $group->id }}').classList.toggle('hidden')">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>

                <!-- Expandable Members Section -->
                <div id="members_{{ $group->id }}" class="hidden mt-10 pt-10 border-t border-slate-100 animate-in fade-in slide-in-from-top-4 duration-300">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="font-black text-lg text-slate-900">Batch Members</h4>
                        <span class="text-[14px] font-black text-slate-400 uppercase tracking-widest">{{ $group->investments_count }}/20 Capacity</span>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="table table-lg">
                            <thead>
                                <tr class="bg-slate-50/50 border-b border-slate-100">
                                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 py-4 px-8">Investor</th>
                                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Investment</th>
                                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Plan</th>
                                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Profit Split</th>
                                    <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 text-right px-8">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($group->investments as $inv)
                                    <tr class="hover:bg-slate-50/50 transition-all">
                                        <td class="px-8 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center font-black text-slate-400 text-sm">
                                                    {{ substr($inv->user->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <p class="font-black text-slate-900 text-sm">{{ $inv->user->name }}</p>
                                                    <p class="text-[13px] font-bold text-slate-400 uppercase tracking-tighter">{{ $inv->user->email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="font-black text-slate-900 text-base">${{ number_format($inv->amount, 2) }}</span>
                                        </td>
                                        <td>
                                            <div class="badge bg-blue-50 text-blue-600 border-none font-black text-[8px] px-2 py-1 h-auto rounded uppercase tracking-widest">
                                                {{ $inv->plan->name }}
                                            </div>
                                        </td>
                                        <td>
                                            @if($group->status == 'matured')
                                                <span class="font-black text-emerald-600 text-base">+${{ number_format($inv->profit, 2) }}</span>
                                            @else
                                                <span class="text-[14px] font-bold text-slate-400 italic">Pending maturity</span>
                                            @endif
                                        </td>
                                        <td class="text-right px-8">
                                            <span class="badge border-none font-black text-[8px] px-2 py-1 h-auto rounded uppercase tracking-widest
                                                {{ $inv->status == 'active' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }}">
                                                {{ $inv->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Maturity Calculation Modal -->
            <dialog id="mature_modal_{{ $group->id }}" class="modal backdrop-blur-sm">
                <div class="modal-box rounded-[2.5rem] p-10 max-w-xl border-none shadow-2xl overflow-visible relative">
                    <div class="text-center mb-10">
                        <div class="w-20 h-20 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-6">
                            <i class="fas fa-vault"></i>
                        </div>
                        <h3 class="font-black text-3xl text-slate-900 mb-2">Declare Maturity</h3>
                        <p class="text-slate-500 font-medium">Distributing profits for <span class="text-primary font-bold">{{ $group->name }}</span></p>
                    </div>
                    
                    <form action="{{ route('admin.groups.mature', $group->id) }}" method="POST" class="space-y-8">
                        @csrf
                        <div class="form-control">
                            <label class="label justify-center mb-4">
                                <span class="label-text font-black text-slate-400 uppercase text-[14px] tracking-[0.2em]">Total Group Profit (100%)</span>
                            </label>
                            <div class="relative group">
                                <span class="absolute left-8 top-1/2 -translate-y-1/2 font-black text-slate-300 text-2xl group-focus-within:text-emerald-500 transition-colors">$</span>
                                <input type="number" step="0.01" name="total_profit" id="profit_input_{{ $group->id }}" 
                                    class="input border-slate-200 w-full pl-16 rounded-2xl font-black text-3xl h-20 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all text-center" 
                                    placeholder="0.00" required oninput="calculateSplit({{ $group->id }}, {{ $group->investments_count }})">
                            </div>
                        </div>

                        <!-- Real-time Calculator -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-6 bg-slate-900 rounded-2xl text-white relative overflow-hidden">
                                <span class="block text-[8px] font-black text-white/40 uppercase tracking-[0.2em] mb-4">Company (30%)</span>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-lg font-black text-emerald-400">$</span>
                                    <span class="text-xl font-black" id="company_share_{{ $group->id }}">0.00</span>
                                </div>
                            </div>
                            <div class="p-6 bg-primary rounded-2xl text-white relative overflow-hidden">
                                <span class="block text-[8px] font-black text-white/40 uppercase tracking-[0.2em] mb-4">Users (70%)</span>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-lg font-black text-white">$</span>
                                    <span class="text-xl font-black" id="users_share_{{ $group->id }}">0.00</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 bg-emerald-50 rounded-2xl border border-emerald-100 text-center">
                            <span class="block text-[8px] font-black text-emerald-600 uppercase tracking-[0.2em] mb-2">Each Investor Gets</span>
                            <div class="flex items-center justify-center gap-2">
                                <span class="text-2xl font-black text-slate-900">$</span>
                                <span class="text-4xl font-black text-slate-900" id="per_user_{{ $group->id }}">0.00</span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 pt-4">
                            <button type="submit" class="btn btn-primary w-full rounded-xl h-14 font-black text-base uppercase tracking-widest text-white shadow-lg shadow-primary/20" 
                                onclick="return confirm('Execute profit distribution? This action is irreversible.')">
                                Confirm & Distribute
                            </button>
                            <button type="button" class="btn btn-ghost w-full rounded-xl h-12 font-bold text-slate-400" onclick="mature_modal_{{ $group->id }}.close()">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </dialog>
        </div>
    @empty
        <div class="bg-white rounded-[3rem] border border-slate-200 border-dashed p-20 text-center">
            <div class="w-20 h-20 bg-slate-50 text-slate-200 rounded-3xl flex items-center justify-center text-3xl mx-auto mb-6">
                <i class="fas fa-layer-group"></i>
            </div>
            <h3 class="font-black text-xl text-slate-900 mb-2">No Active Groups</h3>
            <p class="text-slate-500 font-medium text-base">Create investment batches to start distributing profits.</p>
        </div>
    @endforelse
</div>
@endsection

@section('scripts')
<script>
    function calculateSplit(groupId, memberCount) {
        const input = document.getElementById('profit_input_' + groupId);
        const companyEl = document.getElementById('company_share_' + groupId);
        const usersEl = document.getElementById('users_share_' + groupId);
        const perUserEl = document.getElementById('per_user_' + groupId);
        
        const total = parseFloat(input.value) || 0;
        
        const companyShare = total * 0.30;
        const usersShare = total * 0.70;
        const perUser = memberCount > 0 ? usersShare / memberCount : 0;
        
        companyEl.innerText = companyShare.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        usersEl.innerText = usersShare.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        perUserEl.innerText = perUser.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
</script>
@endsection
