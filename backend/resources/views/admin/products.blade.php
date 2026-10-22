@extends('layouts.admin')

@section('title', 'Products')

@section('content')
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-6">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Products</h1>
        <p class="text-slate-500 font-medium text-base mt-1">Manage store products, pricing and descriptions</p>
    </div>
    <button onclick="create_product_modal.showModal()" class="btn btn-sm bg-slate-900 hover:bg-slate-800 text-white border-none rounded-lg px-6 font-semibold transition-all">
        <i class="fas fa-plus mr-2 text-[14px]"></i> Add New Product
    </button>
</div>

@if($products->isEmpty())
    <div class="card mt-6">
        <div class="p-16 text-center">
            <div class="mx-auto w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                <i class="fas fa-box-open text-3xl text-slate-300"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-700">No products yet</h3>
            <p class="text-slate-500 mt-2">Products you add will appear on the user dashboard store.</p>
            <button onclick="create_product_modal.showModal()" class="btn btn-sm bg-slate-900 hover:bg-slate-800 text-white border-none rounded-lg px-6 font-semibold mt-6">
                <i class="fas fa-plus mr-2 text-[14px]"></i> Add your first product
            </button>
        </div>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
        @foreach($products as $product)
            <div class="card overflow-hidden">
                <div class="h-44 bg-slate-50 flex items-center justify-center overflow-hidden border-b border-slate-100">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-contain p-4">
                    @else
                        <div class="flex flex-col items-center justify-center text-slate-300">
                            <i class="fas fa-image text-4xl mb-2"></i>
                            <span class="text-xs font-medium">No image</span>
                        </div>
                    @endif
                </div>
                <div class="p-6">
                    <div class="flex justify-between items-start mb-3">
                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase bg-blue-50 text-blue-600 border border-blue-100">{{ $product->category }}</span>
                        @if(!$product->is_active)
                            <span class="px-2 py-1 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-400 border border-slate-200">Inactive</span>
                        @endif
                    </div>
                    <h3 class="font-bold text-lg text-slate-900 line-clamp-1">{{ $product->name }}</h3>
                    <p class="text-sm text-slate-500 mt-1 line-clamp-2 h-10">{{ $product->description }}</p>
                    <div class="flex items-center justify-between mt-4">
                        <span class="text-lg font-bold text-slate-900">${{ number_format($product->price, 2) }}</span>
                        <div class="flex gap-2">
                            <button onclick="edit_product_{{ $product->id }}.showModal()" class="text-slate-400 hover:text-slate-600">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('admin.products.delete', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-400 hover:text-rose-600">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Product Modal -->
            <dialog id="edit_product_{{ $product->id }}" class="modal">
                <div class="modal-box rounded-2xl p-8 max-w-lg border border-slate-200 shadow-2xl">
                    <h3 class="font-bold text-xl text-slate-900">Edit Product</h3>
                    <p class="text-sm text-slate-500 mt-1">Modify details for {{ $product->name }}.</p>

                    <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
                        @csrf
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Product Name</span></label>
                            <input type="text" name="name" value="{{ $product->name }}" class="input input-bordered rounded-lg font-semibold" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Category</span></label>
                                <input type="text" name="category" value="{{ $product->category }}" class="input input-bordered rounded-lg font-semibold" required>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Brand</span></label>
                                <input type="text" name="brand" value="{{ $product->brand }}" class="input input-bordered rounded-lg font-semibold">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Price ($)</span></label>
                                <input type="number" step="0.01" name="price" value="{{ $product->price }}" class="input input-bordered rounded-lg font-semibold" required>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Old Price ($)</span></label>
                                <input type="text" name="old_price" value="{{ $product->old_price }}" class="input input-bordered rounded-lg font-semibold" placeholder="Optional">
                            </div>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Subtitle</span></label>
                            <input type="text" name="subtitle" value="{{ $product->subtitle }}" class="input input-bordered rounded-lg font-semibold" placeholder="Short tagline">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Description</span></label>
                            <textarea name="description" class="textarea textarea-bordered rounded-lg font-semibold h-24">{{ $product->description }}</textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Status</span></label>
                                <select name="is_active" class="select select-bordered rounded-lg font-semibold">
                                    <option value="1" {{ $product->is_active ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ !$product->is_active ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Image</span></label>
                                <input type="file" name="image" accept="image/*" class="file-input file-input-bordered rounded-lg font-semibold w-full text-sm">
                            </div>
                        </div>

                        <div class="flex gap-2 justify-end pt-2">
                            <button type="button" class="btn btn-sm btn-ghost font-bold text-sm" onclick="this.closest('dialog').close()">Cancel</button>
                            <button type="submit" class="btn btn-sm bg-slate-900 hover:bg-slate-800 text-white border-none rounded-lg px-6 font-bold text-sm">Update Product</button>
                        </div>
                    </form>
                </div>
            </dialog>
        @endforeach
    </div>
@endif

<!-- Create Product Modal -->
<dialog id="create_product_modal" class="modal">
    <div class="modal-box rounded-2xl p-8 max-w-lg border border-slate-200 shadow-2xl">
        <h3 class="font-bold text-xl text-slate-900">New Product</h3>
        <p class="text-sm text-slate-500 mt-1">Add a product to the store.</p>

        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf
            <div class="form-control">
                <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Product Name</span></label>
                <input type="text" name="name" class="input input-bordered rounded-lg font-semibold" placeholder="e.g. Premium Trading Signal" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Category</span></label>
                    <input type="text" name="category" class="input input-bordered rounded-lg font-semibold" placeholder="e.g. signals, premium" required>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Brand</span></label>
                    <input type="text" name="brand" class="input input-bordered rounded-lg font-semibold" placeholder="Optional">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Price ($)</span></label>
                    <input type="number" step="0.01" name="price" class="input input-bordered rounded-lg font-semibold" placeholder="0.00" required>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Old Price ($)</span></label>
                    <input type="text" name="old_price" class="input input-bordered rounded-lg font-semibold" placeholder="Optional">
                </div>
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Subtitle</span></label>
                <input type="text" name="subtitle" class="input input-bordered rounded-lg font-semibold" placeholder="Short tagline">
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Description</span></label>
                <textarea name="description" class="textarea textarea-bordered rounded-lg font-semibold h-24" placeholder="Product description..."></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Status</span></label>
                    <select name="is_active" class="select select-bordered rounded-lg font-semibold">
                        <option value="1" selected>Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-[14px] text-slate-400 uppercase tracking-wider">Image</span></label>
                    <input type="file" name="image" accept="image/*" class="file-input file-input-bordered rounded-lg font-semibold w-full text-sm">
                </div>
            </div>

            <div class="flex gap-2 justify-end pt-2">
                <button type="button" class="btn btn-sm btn-ghost font-bold text-sm" onclick="create_product_modal.close()">Cancel</button>
                <button type="submit" class="btn btn-sm bg-slate-900 hover:bg-slate-800 text-white border-none rounded-lg px-6 font-bold text-sm">Create Product</button>
            </div>
        </form>
    </div>
</dialog>
@endsection

