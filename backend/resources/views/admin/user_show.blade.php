@extends('layouts.admin')

@section('title', 'Investor Profile: ' . $user->name)

@section('content')
<div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.users') }}" class="w-10 h-10 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-900 transition-colors shadow-sm">
            <i class="fas fa-arrow-left text-base"></i>
        </a>
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900">Investor Profile</h1>
            <p class="text-slate-500 font-medium">Detailed financial overview for {{ $user->name }}</p>
        </div>
    </div>
    <div class="flex gap-3">
        <button onclick="window.print()" class="btn bg-white border-slate-200 rounded-xl font-black text-sm uppercase tracking-widest text-slate-600 hover:bg-slate-50 shadow-sm px-6 h-12">
            <i class="fas fa-print mr-2 text-slate-400"></i> Export
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- User Overview Card -->
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm p-8">
            <div class="flex flex-col items-center text-center">
                <div class="mb-6 relative">
                    <div class="w-32 h-32 rounded-[2rem] bg-slate-100 flex items-center justify-center overflow-hidden border-4 border-white shadow-xl">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="object-cover w-full h-full" />
                        @else
                            <div class="bg-slate-900 text-white w-full h-full flex items-center justify-center">
                                <span class="text-4xl font-black uppercase">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="absolute -bottom-2 -right-2">
                        <span class="badge bg-emerald-500 text-white border-none font-black text-[8px] px-2 py-1 h-auto rounded-md uppercase tracking-widest shadow-lg">Active</span>
                    </div>
                </div>
                
                <div class="mb-2">
                    <span class="text-[13px] font-black bg-slate-100 text-slate-500 px-3 py-1 rounded-md uppercase tracking-widest border border-slate-200">{{ $user->Nex_id }}</span>
                </div>
                <h2 class="text-2xl font-black text-slate-900 mb-1">{{ $user->name }}</h2>
                <p class="text-slate-400 font-bold text-sm mb-2">{{ $user->email }}</p>
                @if($user->phone)
                    <p class="text-primary font-black text-xs uppercase tracking-widest mb-8">
                        <i class="fas fa-phone-alt mr-1"></i> {{ $user->phone }}
                    </p>
                @else
                    <div class="mb-8"></div>
                @endif
                
<div class="grid grid-cols-2 gap-3 w-full">
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                        <span class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Joined</span>
                        <span class="text-slate-900 font-black text-sm uppercase">{{ $user->created_at->format('M Y') }}</span>
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                        <span class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Referrals</span>
                        <span class="text-slate-900 font-black text-sm uppercase">{{ $user->referrals->count() }}</span>
                    </div>
                </div>
            </div>

            <div class="h-px bg-slate-100 my-8"></div>

            <div class="space-y-4">
                <span class="text-[13px] font-black text-slate-400 uppercase tracking-widest block mb-4">Financial Assets</span>
                
                <div class="flex items-center justify-between p-4 bg-slate-900 rounded-2xl text-white shadow-xl shadow-slate-900/10">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center text-sm">
                            <i class="fas fa-wallet text-emerald-400"></i>
                        </div>
                        <span class="font-bold text-sm uppercase tracking-wider text-slate-400">Balance</span>
                    </div>
                    <span class="font-black text-lg">${{ number_format($user->balance, 2) }}</span>
                </div>

                <div class="flex items-center justify-between p-4 bg-white rounded-2xl border border-slate-200">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span class="font-bold text-sm uppercase tracking-wider text-slate-400">Profit</span>
                    </div>
                    <span class="font-black text-emerald-600 text-lg">${{ number_format($user->total_profit, 2) }}</span>
                </div>

                <div class="flex items-center justify-between p-4 bg-white rounded-2xl border border-slate-200">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                            <i class="fas fa-shield-halved"></i>
                        </div>
                        <span class="font-bold text-sm uppercase tracking-wider text-slate-400">Invested</span>
                    </div>
                    <span class="font-black text-blue-600 text-lg">${{ number_format($user->total_invested, 2) }}</span>
                </div>
            </div>
            
            <div class="mt-8">
                <span class="text-[13px] font-black text-slate-400 uppercase tracking-widest block mb-4">Payout Configuration</span>
                <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] font-black text-amber-600 uppercase tracking-widest">Next Payout</span>
                        @if($user->withdrawal_date)
                            <span class="text-sm font-black text-slate-900">{{ date('M d, Y', strtotime($user->withdrawal_date)) }}</span>
                        @else
                            <span class="text-[14px] font-black text-amber-400 uppercase tracking-widest italic">Not Set</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: History -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Transaction Ledger -->
        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-8 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div>
                    <h3 class="font-black text-xl text-slate-900">Financial Ledger</h3>
                    <p class="text-[14px] text-slate-400 font-black uppercase tracking-widest mt-1">Audit log of all movements</p>
                </div>
                <div class="badge bg-slate-900 text-white border-none font-black text-[13px] px-3 py-1 h-auto rounded-md uppercase tracking-widest">
                    {{ $user->transactions->count() }} Entries
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-lg">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 py-5 px-8">Activity</th>
                            <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Amount</th>
                            <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Date</th>
                            <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 text-right px-8">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($user->transactions as $transaction)
                        <tr class="hover:bg-slate-50/50 transition-all group">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm shadow-sm
                                        {{ in_array($transaction->type, ['deposit', 'admin_credit', 'profit', 'cancellation_refund']) ? 'bg-emerald-50 text-emerald-500' : 
                                           (in_array($transaction->type, ['withdrawal_approved', 'investment', 'admin_debit']) ? 'bg-rose-50 text-rose-500' : 'bg-blue-50 text-blue-500') }}">
                                        @if(str_contains($transaction->type, 'deposit'))
                                            <i class="fas fa-arrow-down-to-line"></i>
                                        @elseif(str_contains($transaction->type, 'withdraw'))
                                            <i class="fas fa-arrow-up-from-line"></i>
                                        @elseif($transaction->type == 'investment')
                                            <i class="fas fa-chart-pie"></i>
                                        @elseif($transaction->type == 'profit')
                                            <i class="fas fa-chart-line"></i>
                                        @else
                                            <i class="fas fa-exchange-alt"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-900 text-sm uppercase tracking-wider">{{ str_replace('_', ' ', $transaction->type) }}</div>
                                        <div class="text-[13px] text-slate-400 font-bold max-w-[150px] truncate">{{ $transaction->description }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="font-black text-base {{ $transaction->amount >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $transaction->amount >= 0 ? '+' : '' }}${{ number_format(abs($transaction->amount), 2) }}
                                </span>
                            </td>
                            <td>
                                <div class="font-bold text-slate-900 text-sm">{{ $transaction->created_at->format('M d, Y') }}</div>
                                <div class="text-[14px] text-slate-400 font-bold uppercase">{{ $transaction->created_at->format('H:i A') }}</div>
                            </td>
                            <td class="text-right px-8">
                                @php
                                    $statusClasses = [
                                        'completed' => 'bg-emerald-50 text-emerald-600',
                                        'approved' => 'bg-emerald-50 text-emerald-600',
                                        'pending' => 'bg-amber-50 text-amber-600',
                                        'failed' => 'bg-rose-50 text-rose-600'
                                    ][$transaction->status] ?? 'bg-slate-50 text-slate-500';
                                @endphp
                                <span class="badge {{ $statusClasses }} border-none font-black text-[8px] px-2 py-1 h-auto rounded uppercase tracking-widest">
                                    {{ $transaction->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-20 text-center">
                                <p class="font-black uppercase tracking-widest text-[14px] text-slate-300">No transaction ledger found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

<!-- Account Plan Management -->
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h3 class="font-black text-xl text-slate-900">Account Plan</h3>
                        <p class="text-[14px] text-slate-400 font-black uppercase tracking-widest mt-1">Manage {{ $user->name }}'s subscription level</p>
                    </div>
                    <div class="badge {{ $user->hasPlan() ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-50 text-slate-500' }} border-none font-black text-[13px] px-3 py-1 h-auto rounded-md uppercase tracking-widest">
                        <i class="fas fa-layer-group mr-1"></i> {{ $user->level_label }}
                    </div>
                </div>
                <div class="p-8">
                    <form action="{{ route('admin.users.level', $user->id) }}" method="POST" class="space-y-6">
                        @csrf
                        <div class="form-control">
                            <label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Select Plan</span></label>
                            <select name="level" class="select border-slate-200 bg-slate-50 rounded-xl font-bold focus:bg-white focus:border-primary transition-all w-full" required>
                                @foreach(\App\Models\User::LEVELS as $key => $label)
                                    <option value="{{ $key }}" {{ $user->level === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if(!$user->hasPlan())
                            <div class="p-4 rounded-2xl border border-amber-100 bg-amber-50 text-sm font-medium text-amber-700">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                This user is on the Free Plan and cannot earn free points (e.g. daily login bonus). Upgrade them to enable earning.
                            </div>
                        @endif
                        <button type="submit" class="btn btn-primary text-white rounded-xl h-14 font-black uppercase tracking-widest w-full">Update Plan</button>
                    </form>
                </div>
            </div>

@if($user->investments->count() > 0)
        <!-- Asset Portfolio -->
        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-8 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-black text-xl text-slate-900">Investment Portfolio</h3>
                <p class="text-[14px] text-slate-400 font-black uppercase tracking-widest mt-1">Capital committed to active plans</p>
            </div>
            <div class="overflow-x-auto">
                <table class="table table-lg">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 py-5 px-8">Plan</th>
                            <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Capital</th>
                            <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 text-right px-8">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($user->investments as $investment)
                        <tr class="group hover:bg-slate-50/50 transition-all">
                            <td class="px-8 py-5">
                                <span class="font-black text-slate-900 text-sm uppercase tracking-wider">{{ $investment->plan->name }}</span>
                            </td>
                            <td class="font-black text-slate-900 text-base">${{ number_format($investment->amount, 2) }}</td>
                            <td class="text-right px-8">
                                <span class="badge {{ $investment->status == 'active' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }} border-none font-black text-[8px] px-2 py-1 h-auto rounded uppercase tracking-widest">
                                    {{ $investment->status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Referred Users -->
        <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-8 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div>
                    <h3 class="font-black text-xl text-slate-900">Referred Users</h3>
                    <p class="text-[14px] text-slate-400 font-black uppercase tracking-widest mt-1">Users who joined through this investor's referral</p>
                </div>
                <div class="badge bg-slate-900 text-white border-none font-black text-[13px] px-3 py-1 h-auto rounded-md uppercase tracking-widest">
                    {{ $user->referrals->count() }} Referrals
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="table table-lg">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 py-5 px-8">Investor</th>
                            <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Nex ID</th>
                            <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Joined</th>
                            <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Level</th>
                            <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 text-right px-8">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($user->referrals as $referral)
                        <tr class="group hover:bg-slate-50/50 transition-all">
                            <td class="px-8 py-5">
                                <a href="{{ route('admin.users.show', $referral->id) }}" class="flex items-center gap-4 group">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center overflow-hidden">
                                        @if($referral->avatar_url)
                                            <img src="{{ $referral->avatar_url }}" alt="{{ $referral->name }}" class="object-cover w-full h-full" />
                                        @else
                                            <span class="bg-slate-900 text-white w-full h-full flex items-center justify-center text-sm font-black uppercase">{{ substr($referral->name, 0, 1) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-900 text-sm uppercase tracking-wider">{{ $referral->name }}</div>
                                        <div class="text-[13px] text-slate-400 font-bold">{{ $referral->email }}</div>
                                    </div>
                                </a>
                            </td>
                            <td>
                                <span class="text-[13px] font-black bg-slate-100 text-slate-500 px-3 py-1 rounded-md uppercase tracking-widest border border-slate-200">{{ $referral->Nex_id }}</span>
                            </td>
                            <td>
                                <div class="font-bold text-slate-900 text-sm">{{ $referral->created_at->format('M d, Y') }}</div>
                                <div class="text-[14px] text-slate-400 font-bold uppercase">{{ $referral->created_at->format('H:i A') }}</div>
                            </td>
                            <td>
                                <span class="badge border-none font-black text-[8px] px-2 py-1 h-auto rounded uppercase tracking-widest {{ $referral->level ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-50 text-slate-500' }}">
                                    {{ $referral->level_label }}
                                </span>
                            </td>
                            <td class="text-right px-8">
                                <span class="font-black text-slate-900 text-base">${{ number_format($referral->balance, 2) }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-20 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center text-2xl text-slate-300">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <p class="font-black uppercase tracking-widest text-[14px] text-slate-300">No referred users found</p>
                                    <p class="text-[13px] text-slate-400 font-bold">This investor has not referred anyone yet.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
