@permission('accounts.create')
<div class="modal fade" id="quickAddCategoryModal" tabindex="-1" aria-labelledby="quickAddCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickAddCategoryModalLabel">Quick Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickAddCategoryForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="quick_category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quick_category_name" name="name" maxlength="255" required>
                    </div>
                    <div>
                        <label for="quick_category_description" class="form-label">Description</label>
                        <textarea class="form-control" id="quick_category_description" name="description" rows="3" maxlength="1000"></textarea>
                    </div>
                    <input type="hidden" name="is_active" value="1">
                    <input type="hidden" name="sort_order" value="0">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function() {
    const categorySelect = $('#category_id');
    const categoryModalElement = document.getElementById('quickAddCategoryModal');
    const categoryModal = bootstrap.Modal.getOrCreateInstance(categoryModalElement);
    let previousCategoryId = categorySelect.val() || '';

    categorySelect.on('focus', function() {
        if ($(this).val() !== '__quick_add__') {
            previousCategoryId = $(this).val() || '';
        }
    });

    categorySelect.on('change.quickAddCategory', function() {
        if ($(this).val() !== '__quick_add__') {
            previousCategoryId = $(this).val() || '';
            return;
        }

        $(this).val(previousCategoryId);
        $('#quickAddCategoryForm')[0].reset();
        clearValidationErrors('#quickAddCategoryForm');
        categoryModal.show();
        categoryModalElement.addEventListener('shown.bs.modal', function() {
            $('#quick_category_name').trigger('focus');
        }, { once: true });
    });

    ajaxFormSubmit(
        '#quickAddCategoryForm',
        '{{ route("admin.item-categories.store") }}',
        'POST',
        function(response) {
            const category = response.data;
            categorySelect.find('option[value="__quick_add__"]').before(
                new Option(category.name, category.id, true, true)
            );
            previousCategoryId = String(category.id);
            categorySelect.val(previousCategoryId).trigger('change');
            categoryModal.hide();
            $('#quickAddCategoryForm')[0].reset();
        }
    );
});
</script>
@endpush
@endpermission
