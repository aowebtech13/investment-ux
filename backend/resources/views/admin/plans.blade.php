@extends('layouts.admin')

@section('title', 'Investment Plans')

@section('content')
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-6">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Investment Plans</h1>
        <p class="text-slate-500 font-medium text-base mt-1">Manage investment tiers and returns</p>
    </div>
    <button onclick="create_plan_modal.showModal()" class="btn btn-sm bg-slate-900 hover:bg-slate-800 text-white border-none rounded-lg px-6 font-semibold transition-all">
        <i class="fas fa-plus mr-2 text-[14px]"></i> Create New Plan
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
    @foreach($plans as $plan)
        <div class="card overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border {{ $plan->category == 'Salary' ? 'bg-purple-50 text-purple-600 border-purple-100' : ($plan->category == 'Earnings' ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-green-50 text-green-600 border-green-100') }}">
                            {{ $plan->category }}
                        </span>
                        @if(!$plan->is_active)
                            <span class="px-2 py-1 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-400 border border-slate-200">
                                Inactive
                            </span>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button onclick="edit_plan_{{ $plan->id }}.showModal()" class="text-slate-400 hover:text-slate-600">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form action="{{ route('admin.plans.delete', $plan->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this plan?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-slate-400 hover:text-rose-600">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <h3 class="font-bold text-xl text-slate-900">{{ $plan->name }}</h3>
                <p class="text-sm text-slate-500 mt-1 line-clamp-2 h-10">{{ $plan->description }}</p>
                
                <div class="grid grid-cols-2 gap-4 mt-6 p-4 bg-slate-50 rounded-xl">
                    <div>
                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Amount</span>
                        <span class="text-sm font-bold text-slate-900">${{ number_format($plan->amount) }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">ROI / Duration</span>
                        <span class="text-sm font-bold text-slate-900">{{ $plan->interest_rate }}% / {{ $plan->duration_days }}d</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Plan Modal -->
        <dialog id="edit_plan_{{ $plan->id }}" class="modal">
            <div class="modal-box rounded-2xl p-8 max-w-lg border border-slate-200 shadow-2xl">
                <h3 class="font-bold text-xl text-slate-900">Edit Plan</h3>
                <p class="text-sm text-slate-500 mt-1">Modify configuration for {{ $plan->name }}.</p>
                
                <form action="{{ route('admin.plans.update', $plan->id) }}" method="POST" class="mt-6 space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-control col-span-2">
                            <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Plan Name</span></label>
                            <input type="text" name="name" value="{{ $plan->name }}" class="input input-bordered rounded-lg font-semibold" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Category</span></label>
                            <select name="category" class="select select-bordered rounded-lg font-semibold" required>
                                <option value="Earnings" {{ $plan->category == 'Earnings' ? 'selected' : '' }}>Earnings</option>
                                <option value="Salary" {{ $plan->category == 'Salary' ? 'selected' : '' }}>Salary</option>
                                <option value="Investment Tier" {{ $plan->category == 'Investment Tier' ? 'selected' : '' }}>Investment Tier</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Status</span></label>
                            <select name="is_active" class="select select-bordered rounded-lg font-semibold" required>
                                <option value="1" {{ $plan->is_active ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ !$plan->is_active ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="form-control col-span-2">
                            <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Amount ($)</span></label>
                            <input type="number" step="0.01" name="amount" value="{{ $plan->amount }}" class="input input-bordered rounded-lg font-semibold" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Interest Rate (%)</span></label>
                            <input type="number" step="0.01" name="interest_rate" value="{{ $plan->interest_rate }}" class="input input-bordered rounded-lg font-semibold" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Duration (Days)</span></label>
                            <input type="number" name="duration_days" value="{{ $plan->duration_days }}" class="input input-bordered rounded-lg font-semibold" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Return Type</span></label>
                            <select name="return_type" class="select select-bordered rounded-lg font-semibold" required>
                                <option value="daily" {{ $plan->return_type == 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ $plan->return_type == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="monthly" {{ $plan->return_type == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="end_of_term" {{ $plan->return_type == 'end_of_term' ? 'selected' : '' }}>End of Term</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Description</span></label>
                        <textarea name="description" class="textarea textarea-bordered rounded-lg font-semibold h-24">{{ $plan->description }}</textarea>
                    </div>

                    <div class="flex gap-2 justify-end pt-2">
                        <button type="button" class="btn btn-sm btn-ghost font-bold text-sm" onclick="this.closest('dialog').close()">Cancel</button>
                        <button type="submit" class="btn btn-sm bg-slate-900 hover:bg-slate-800 text-white border-none rounded-lg px-6 font-bold text-sm">Update Plan</button>
                    </div>
                </form>
            </div>
        </dialog>
    @endforeach
</div>

<!-- Create Plan Modal -->
<dialog id="create_plan_modal" class="modal">
    <div class="modal-box rounded-2xl p-8 max-w-lg border border-slate-200 shadow-2xl">
        <h3 class="font-bold text-xl text-slate-900">New Investment Plan</h3>
        <p class="text-sm text-slate-500 mt-1">Configure a new tier for investors.</p>
        
        <form action="{{ route('admin.plans.create') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="form-control col-span-2">
                    <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Plan Name</span></label>
                    <input type="text" name="name" class="input input-bordered rounded-lg font-semibold" placeholder="e.g. Diamond Starter" required>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Category</span></label>
                    <select name="category" class="select select-bordered rounded-lg font-semibold" required>
                        <option value="Earnings" selected>Earnings</option>
                        <option value="Salary">Salary</option>
                        <option value="Investment Tier">Investment Tier</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Interest Rate (%)</span></label>
                    <input type="number" step="0.01" name="interest_rate" class="input input-bordered rounded-lg font-semibold" placeholder="10" required>
                </div>
                <div class="form-control col-span-2">
                    <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Amount ($)</span></label>
                    <input type="number" step="0.01" name="amount" class="input input-bordered rounded-lg font-semibold" placeholder="1000" required>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Duration (Days)</span></label>
                    <input type="number" name="duration_days" class="input input-bordered rounded-lg font-semibold" placeholder="30" required>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Return Type</span></label>
                    <select name="return_type" class="select select-bordered rounded-lg font-semibold" required>
                        <option value="daily" selected>Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="end_of_term">End of Term</option>
                    </select>
                </div>
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Description</span></label>
                <textarea name="description" class="textarea textarea-bordered rounded-lg font-semibold h-24" placeholder="Brief details about the plan..."></textarea>
            </div>

            <div class="flex gap-2 justify-end pt-2">
                <button type="button" class="btn btn-sm btn-ghost font-bold text-sm" onclick="create_plan_modal.close()">Cancel</button>
                <button type="submit" class="btn btn-sm bg-slate-900 hover:bg-slate-800 text-white border-none rounded-lg px-6 font-bold text-sm">Create Plan</button>
            </div>
        </form>
    </div>
</dialog>
@endsection
