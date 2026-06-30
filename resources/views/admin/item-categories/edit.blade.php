@extends('layouts.app')

@section('title', 'Edit Item Category')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Edit Item Category</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.item-categories.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form id="itemCategoryForm">
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ $itemCategory->name }}" required>
                </div>
                <div class="col-md-4">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" min="0" value="{{ $itemCategory->sort_order }}">
                </div>
                <div class="col-md-4">
                    <label for="is_active" class="form-label">Status</label>
                    <select class="form-select" id="is_active" name="is_active">
                        <option value="1" {{ $itemCategory->is_active ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ !$itemCategory->is_active ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3">{{ $itemCategory->description }}</textarea>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Update Category
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
ajaxFormSubmit('#itemCategoryForm', '{{ route("admin.item-categories.update", $itemCategory->id) }}', 'PUT', '{{ route("admin.item-categories.index") }}');
</script>
@endsection
