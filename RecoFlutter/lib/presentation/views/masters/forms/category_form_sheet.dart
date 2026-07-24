import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/masters/categories_controller.dart';
import '../../../widgets/common/common_button.dart';
import '../../../widgets/common/custom_text_field.dart';

class CategoryFormSheet extends StatefulWidget {
  const CategoryFormSheet({super.key, this.entity});
  final ItemCategoryEntity? entity;
  @override
  State<CategoryFormSheet> createState() => _CategoryFormSheetState();
}

class _CategoryFormSheetState extends State<CategoryFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _sortOrderController = TextEditingController(text: '0');
  late final CategoriesController controller;
  bool isSaving = false;

  @override
  void initState() {
    super.initState();
    controller = Get.find<CategoriesController>();
    _nameController.text = widget.entity?.name ?? '';
    _descriptionController.text = widget.entity?.description ?? '';
    _sortOrderController.text = '${widget.entity?.sortOrder ?? 0}';
  }

  @override
  void dispose() {
    _nameController.dispose();
    _descriptionController.dispose();
    _sortOrderController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.entity == null ? 'Create Category' : 'Edit Category',
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.w800,
              ),
        ),
      ),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              CustomTextField(
                controller: _nameController,
                label: 'Category Name',
                hintText: 'Category name',
                validator: _required,
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _descriptionController,
                label: 'Description',
                hintText: 'Description',
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _sortOrderController,
                label: 'Sort Order',
                hintText: '0',
              ),
              const SizedBox(height: 20),
              CommonButton(
                text: 'Save',
                isLoading: isSaving,
                onPressed: _submit,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => isSaving = true);
    try {
      await controller.save(
        ItemCategoryEntity(
          id: widget.entity?.id,
          localId: widget.entity?.localId,
          name: _nameController.text.trim(),
          description: _descriptionController.text.trim(),
          sortOrder: int.tryParse(_sortOrderController.text.trim()) ?? 0,
          isActive: widget.entity?.isActive ?? true,
        ),
      );
      if (mounted) Navigator.of(context).pop();
    } finally {
      if (mounted) setState(() => isSaving = false);
    }
  }

  String? _required(String? value) =>
      (value ?? '').trim().isEmpty ? 'Required field' : null;
}
