@extends('layouts.admin')

@section('title', 'Registered Investors')

@section('content')
<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900">Registered Investors</h1>
            <p class="text-slate-500 font-medium">Manage and monitor platform members</p>
        </div>
        <form action="{{ route('admin.users') }}" method="GET" class="flex gap-2">
            <div class="relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-base"></i>
                <input name="search" value="{{ $search ?? '' }}" class="input border-slate-200 bg-white rounded-xl w-64 pl-11 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all font-medium text-base" placeholder="Search by name, email or ID..." />
            </div>
            <button type="submit" class="btn btn-primary text-white rounded-xl px-6 font-black text-sm uppercase tracking-widest">Search</button>
            @if($search)
                <a href="{{ route('admin.users') }}" class="btn bg-slate-100 border-none text-slate-500 rounded-xl px-4 hover:bg-slate-200">
                    <i class="fas fa-times"></i>
                </a>
            @endif
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="table table-lg">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
<th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 py-5 px-8">Member</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 text-center">Account Balance</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 text-center">Plan / Level</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 text-center">Payout Status</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400">Joined Date</th>
                        <th class="font-bold text-[14px] uppercase tracking-[0.15em] text-slate-400 text-right px-8">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($users as $user)
                    <tr class="hover:bg-slate-50/50 transition-all duration-200 group">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="bg-slate-100 text-slate-500 rounded-xl w-10 h-10 flex items-center justify-center border border-slate-200 overflow-hidden shrink-0 font-black text-sm">
                                    @if($user->avatar_url)
                                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="object-cover w-full h-full" />
                                    @else
                                        {{ substr($user->name ?? 'U', 0, 1) }}
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-slate-900 text-base truncate">{{ $user->name }}</span>
                                        <span class="text-[13px] font-black bg-slate-100 text-slate-500 px-2 py-0.5 rounded uppercase tracking-tighter">{{ $user->Nex_id }}</span>
                                    </div>
                                    <div class="text-[14px] text-slate-400 font-bold uppercase tracking-tighter truncate">{{ $user->email }}</div>
                                    @if($user->phone)
                                        <div class="text-[12px] text-primary font-black uppercase tracking-widest mt-0.5">
                                            <i class="fas fa-phone-alt mr-1"></i> {{ $user->phone }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
<td class="text-center">
<span class="font-black text-base text-slate-900">₦{{ number_format($user->balance, 2) }}</span>
                            <p class="text-[13px] font-black text-slate-400 uppercase tracking-widest mt-0.5">Available</p>
                        </td>
                        <td class="text-center">
                            <span class="badge border-none font-black text-[13px] px-2 py-1 h-auto rounded-md uppercase tracking-widest {{ $user->hasPlan() ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-50 text-slate-500' }}">
                                {{ $user->level_label }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($user->withdrawal_date)
                                <span class="badge bg-emerald-50 text-emerald-600 border-none font-black text-[13px] px-2 py-1 h-auto rounded-md uppercase tracking-widest">
                                    <i class="fas fa-calendar-alt mr-1"></i> {{ date('M d, Y', strtotime($user->withdrawal_date)) }}
                                </span>
                            @else
                                <span class="text-[13px] font-black text-slate-300 uppercase tracking-widest italic">Not Set</span>
                            @endif
                        </td>
                        <td>
                            <div class="font-bold text-slate-900 text-sm">{{ $user->created_at->format('M d, Y') }}</div>
                            <div class="text-[14px] text-slate-400 font-bold uppercase">{{ $user->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="text-right px-8">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-square btn-ghost btn-sm text-slate-400 hover:text-slate-900" title="View Profile">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button onclick="fund_modal_{{ $user->id }}.showModal()" class="btn btn-square btn-ghost btn-sm text-emerald-400 hover:text-emerald-600" title="Fund Wallet">
                                    <i class="fas fa-plus-circle"></i>
                                </button>
<button onclick="edit_modal_{{ $user->id }}.showModal()" class="btn btn-square btn-ghost btn-sm text-slate-400 hover:text-slate-900" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="plan_modal_{{ $user->id }}.showModal()" class="btn btn-square btn-ghost btn-sm text-indigo-400 hover:text-indigo-600" title="Change Plan">
                                    <i class="fas fa-layer-group"></i>
                                </button>
                            </div>

                            <!-- Fund Wallet Modal -->
                            <dialog id="fund_modal_{{ $user->id }}" class="modal backdrop-blur-sm">
                                <div class="modal-box rounded-[2.5rem] p-10 max-w-md border-none shadow-2xl relative text-left">
                                    <div class="flex justify-between items-start mb-8">
                                        <div>
                                            <h3 class="font-black text-2xl text-slate-900">Wallet Management</h3>
                                            <p class="text-base font-medium text-slate-500">Fund or adjust balance for <strong>{{ $user->name }}</strong></p>
                                        </div>
                                        <button class="btn btn-circle btn-ghost btn-sm text-slate-400" onclick="this.closest('dialog').close()">✕</button>
                                    </div>
                                    
                                    <form action="{{ route('admin.users.fund', $user->id) }}" method="POST" class="space-y-6">
                                        @csrf
                                        <div class="form-control">
                                            <label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Adjustment Type</span></label>
                                            <select name="type" class="select border-slate-200 bg-slate-50 rounded-xl font-bold focus:bg-white focus:border-primary transition-all w-full" required>
                                                <option value="credit">Credit (Add Money)</option>
                                                <option value="debit">Debit (Remove Money)</option>
                                            </select>
                                        </div>

                                        <div class="form-control">
<label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Amount (₦)</span></label>
                                            <input type="number" step="0.01" name="amount" class="input border-slate-200 bg-slate-50 rounded-xl font-black text-lg focus:bg-white focus:border-primary transition-all w-full" placeholder="0.00" required>
                                        </div>

                                        <div class="form-control">
                                            <label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Audit Description</span></label>
                                            <textarea name="description" class="textarea border-slate-200 bg-slate-50 rounded-2xl h-24 font-medium focus:bg-white focus:border-primary transition-all w-full" placeholder="Reason for this adjustment..." required></textarea>
                                        </div>

                                        <div class="flex flex-col gap-3 pt-4 border-t border-slate-50">
                                            <button type="submit" class="btn btn-primary text-white rounded-xl h-14 font-black uppercase tracking-widest">Update Balance</button>
                                            <button type="button" class="btn btn-ghost rounded-xl h-12 font-bold text-slate-400" onclick="this.closest('dialog').close()">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </dialog>

                            <!-- Edit Modal -->
                            <dialog id="edit_modal_{{ $user->id }}" class="modal backdrop-blur-sm">
                                <div class="modal-box rounded-[2.5rem] p-10 max-w-2xl border-none shadow-2xl relative text-left">
                                    <div class="flex justify-between items-start mb-8">
                                        <div>
                                            <h3 class="font-black text-2xl text-slate-900">Modify Investor</h3>
                                            <p class="text-base font-medium text-slate-500">Updating profile for <strong>{{ $user->name }}</strong></p>
                                        </div>
                                        <button class="btn btn-circle btn-ghost btn-sm text-slate-400" onclick="this.closest('dialog').close()">✕</button>
                                    </div>
                                    
                                    <form action="{{ route('admin.users.update', $user->id) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                                        @csrf
                                        <div class="flex items-center gap-8 pb-8 border-b border-slate-100">
                                            <div class="bg-slate-100 rounded-2xl w-24 h-24 flex items-center justify-center border border-slate-200 overflow-hidden shrink-0 shadow-sm">
                                                @if($user->avatar_url)
                                                    <img src="{{ $user->avatar_url }}" class="object-cover w-full h-full" />
                                                @else
                                                    <span class="text-2xl font-black text-slate-400 uppercase">{{ substr($user->name, 0, 1) }}</span>
                                                @endif
                                            </div>
                                            <div class="form-control w-full">
                                                <label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Update Identification Photo</span></label>
                                                <input type="file" name="avatar" class="file-input border-slate-200 bg-slate-50 w-full rounded-xl" accept="image/*" />
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div class="form-control">
                                                <label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Legal Name</span></label>
                                                <input type="text" name="name" class="input border-slate-200 bg-slate-50 rounded-xl font-bold focus:bg-white focus:border-primary transition-all" value="{{ $user->name }}" required>
                                            </div>
                                            <div class="form-control">
                                                <label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Email Address</span></label>
                                                <input type="email" name="email" class="input border-slate-200 bg-slate-50 rounded-xl font-bold focus:bg-white focus:border-primary transition-all" value="{{ $user->email }}" required>
                                            </div>
                                            <div class="form-control">
                                                <label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Phone Number</span></label>
                                                <input type="text" name="phone" class="input border-slate-200 bg-slate-50 rounded-xl font-bold focus:bg-white focus:border-primary transition-all" value="{{ $user->phone }}">
                                            </div>
                                            <div class="form-control">
<label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Capital Balance (₦)</span></label>
                                                <input type="number" step="0.01" name="balance" class="input border-slate-200 bg-slate-50 rounded-xl font-black text-lg focus:bg-white focus:border-primary transition-all" value="{{ $user->balance }}" required>
                                            </div>
                                            <div class="form-control">
                                                <label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Scheduled Payout</span></label>
                                                <input type="date" name="withdrawal_date" class="input border-slate-200 bg-slate-50 rounded-xl font-bold focus:bg-white focus:border-primary transition-all" value="{{ $user->withdrawal_date }}">
                                            </div>
                                        </div>

                                        <div class="form-control">
                                            <label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Internal Audit Note</span></label>
                                            <textarea name="description" class="textarea border-slate-200 bg-slate-50 rounded-2xl h-24 font-medium focus:bg-white focus:border-primary transition-all" placeholder="Required if capital balance is adjusted..."></textarea>
                                        </div>

<div class="flex flex-col gap-3 pt-4 border-t border-slate-50">
                                            <button type="submit" class="btn btn-primary text-white rounded-xl h-14 font-black uppercase tracking-widest">Commit Changes</button>
                                            <button type="button" class="btn btn-ghost rounded-xl h-12 font-bold text-slate-400" onclick="this.closest('dialog').close()">Discard</button>
                                        </div>
                                    </form>
                                </div>
                            </dialog>

                            <!-- Change Plan Modal -->
                            <dialog id="plan_modal_{{ $user->id }}" class="modal backdrop-blur-sm">
                                <div class="modal-box rounded-[2.5rem] p-10 max-w-md border-none shadow-2xl relative text-left">
                                    <div class="flex justify-between items-start mb-8">
                                        <div>
                                            <h3 class="font-black text-2xl text-slate-900">Change Account Plan</h3>
                                            <p class="text-base font-medium text-slate-500">Update plan for <strong>{{ $user->name }}</strong></p>
                                        </div>
                                        <button class="btn btn-circle btn-ghost btn-sm text-slate-400" onclick="this.closest('dialog').close()">✕</button>
                                    </div>

                                    <form action="{{ route('admin.users.level', $user->id) }}" method="POST" class="space-y-6">
                                        @csrf
                                        <div class="form-control">
                                            <label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Current Plan</span></label>
                                            <div class="px-4 py-3 bg-indigo-50 text-indigo-600 rounded-xl font-black text-sm uppercase tracking-widest">
                                                <i class="fas fa-layer-group mr-2"></i> {{ $user->level_label }}
                                            </div>
                                        </div>

                                        <div class="form-control">
                                            <label class="label mb-1"><span class="label-text font-black text-[14px] text-slate-400 uppercase tracking-widest">Select New Plan</span></label>
                                            <select name="level" class="select border-slate-200 bg-slate-50 rounded-xl font-bold focus:bg-white focus:border-primary transition-all w-full" required>
                                                @foreach(\App\Models\User::LEVELS as $key => $label)
                                                    <option value="{{ $key }}" {{ $user->level === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="flex flex-col gap-3 pt-4 border-t border-slate-50">
                                            <button type="submit" class="btn btn-primary text-white rounded-xl h-14 font-black uppercase tracking-widest">Save Plan</button>
                                            <button type="button" class="btn btn-ghost rounded-xl h-12 font-bold text-slate-400" onclick="this.closest('dialog').close()">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </dialog>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-20">
                            <div class="w-20 h-20 bg-slate-50 rounded-3xl flex items-center justify-center text-slate-200 text-3xl mx-auto mb-4">
                                <i class="fas fa-users text-3xl opacity-20"></i>
                            </div>
                            <p class="text-slate-400 font-black text-base uppercase tracking-widest">No matching investors found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center pb-8">
        {{ $users->appends(['search' => $search])->links() }}
    </div>
</div>
@endsection
