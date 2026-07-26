import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/masters/items_controller.dart';
import '../../../controllers/masters/masters_lookup_controller.dart';
import '../../../widgets/common/common_button.dart';
import '../../../widgets/common/custom_text_field.dart';

class ItemFormSheet extends StatefulWidget {
  const ItemFormSheet({super.key, this.entity});
  final ItemEntity? entity;
  @override
  State<ItemFormSheet> createState() => _ItemFormSheetState();
}

class _ItemFormSheetState extends State<ItemFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _codeController = TextEditingController();
  final _nameController = TextEditingController();
  final _hsnController = TextEditingController();
  final _purchaseController = TextEditingController(text: '0');
  final _sellingController = TextEditingController(text: '0');
  final _unitController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _barcodeController = TextEditingController();
  final _stockController = TextEditingController(text: '0');
  late final ItemsController controller;
  late final MastersLookupController lookupController;
  bool isSaving = false;
  String type = 'goods';
  int? categoryId;
  int? taxRateId;
  int? incomeAccountId;
  int? expenseAccountId;
  String _unit = 'nos';
  bool _isStockable = true;
  bool _isActive = true;

  bool get _isServiceType => type == 'service';

  @override
  void initState() {
    super.initState();
    controller = Get.find<ItemsController>();
    lookupController = Get.find<MastersLookupController>();
    final entity = widget.entity;
    _codeController.text = entity?.itemCode ?? '';
    _nameController.text = entity?.name ?? '';
    _hsnController.text = entity?.hsnSacCode ?? '';
    _purchaseController.text = '${entity?.purchasePrice ?? 0}';
    _sellingController.text = '${entity?.sellingPrice ?? 0}';
    _unitController.text = entity?.unit ?? 'nos';
    _descriptionController.text = entity?.description ?? '';
    _barcodeController.text = entity?.barcode ?? '';
    _stockController.text = '${entity?.openingStock ?? 0}';
    type = entity?.type ?? 'goods';
    categoryId = entity?.categoryId;
    taxRateId = entity?.taxRateId;
    incomeAccountId = entity?.incomeAccountId;
    expenseAccountId = entity?.expenseAccountId;
    _unit = entity?.unit ?? 'nos';
    _isStockable = entity?.isStockable ?? true;
    _isActive = entity?.isActive ?? true;
  }

  @override
  void dispose() {
    _codeController.dispose();
    _nameController.dispose();
    _hsnController.dispose();
    _purchaseController.dispose();
    _sellingController.dispose();
    _unitController.dispose();
    _descriptionController.dispose();
    _barcodeController.dispose();
    _stockController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.entity == null ? 'Create Item' : 'Edit Item',
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
                controller: _codeController,
                label: 'Item Code',
                hintText: 'Item code',
                validator: _required,
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _nameController,
                label: 'Item Name',
                hintText: 'Item name',
                validator: _required,
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _hsnController,
                label: 'HSN / SAC',
                hintText: 'HSN/SAC',
              ),
              const SizedBox(height: 12),
              CustomDropdown<String>(
                label: 'Item Type',
                items: const ['goods', 'service'],
                value: type,
                itemLabelBuilder: _capitalize,
                onChanged: (value) {
                  setState(() {
                    type = value ?? type;
                    if (_isServiceType) {
                      _isStockable = false;
                      _stockController.text = '0';
                    } else {
                      _isStockable = true;
                    }
                  });
                },
              ),
              if (_isServiceType) ...[
                const SizedBox(height: 12),
                _InfoNoteCard(
                  text:
                      'Service type me stock tracking nahi hota. Isliye opening stock aur stockable controls hide kiye gaye hain.',
                ),
              ],
              const SizedBox(height: 12),
              Obx(
                () => CustomDropdown<int>(
                  label: 'Category',
                  items: lookupController.categories
                      .map((item) => item.id)
                      .toList(),
                  value: categoryId,
                  itemLabelBuilder: (id) => lookupController.categories
                      .firstWhere((item) => item.id == id)
                      .label,
                  onChanged: (value) => setState(() => categoryId = value),
                ),
              ),
              const SizedBox(height: 12),
              Obx(
                () => CustomDropdown<int>(
                  label: 'Tax Rate',
                  items: lookupController.taxes.map((item) => item.id).toList(),
                  value: taxRateId,
                  itemLabelBuilder: (id) => lookupController.taxes
                      .firstWhere((item) => item.id == id)
                      .label,
                  onChanged: (value) => setState(() => taxRateId = value),
                ),
              ),
              const SizedBox(height: 12),
              Obx(
                () => CustomDropdown<int>(
                  label: 'Income Account',
                  items: lookupController.incomeAccounts
                      .map((item) => item.id)
                      .toList(),
                  value: incomeAccountId,
                  itemLabelBuilder: (id) => lookupController.incomeAccounts
                      .firstWhere((item) => item.id == id)
                      .label,
                  onChanged: (value) => setState(() => incomeAccountId = value),
                ),
              ),
              const SizedBox(height: 12),
              Obx(
                () => CustomDropdown<int>(
                  label: 'Expense Account',
                  items: lookupController.expenseAccounts
                      .map((item) => item.id)
                      .toList(),
                  value: expenseAccountId,
                  itemLabelBuilder: (id) => lookupController.expenseAccounts
                      .firstWhere((item) => item.id == id)
                      .label,
                  onChanged: (value) =>
                      setState(() => expenseAccountId = value),
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: <Widget>[
                  Expanded(
                    child: CustomTextField(
                      controller: _purchaseController,
                      label: 'Purchase Price',
                      hintText: '0',
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: CustomTextField(
                      controller: _sellingController,
                      label: 'Selling Price',
                      hintText: '0',
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: <Widget>[
                  Expanded(
                    child: CustomDropdown<String>(
                      label: 'Unit',
                      items: const ['nos', 'kg', 'ltr', 'mtr', 'pcs', 'box', 'set'],
                      value: _unit,
                      itemLabelBuilder: _capitalize,
                      onChanged: (v) => setState(() => _unit = v ?? _unit),
                    ),
                  ),
                  if (!_isServiceType) ...[
                    const SizedBox(width: 12),
                    Expanded(
                      child: CustomTextField(
                        controller: _stockController,
                        label: 'Opening Stock',
                        hintText: '0',
                      ),
                    ),
                  ],
                ],
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _barcodeController,
                label: 'Barcode',
                hintText: 'Barcode',
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _descriptionController,
                label: 'Description',
                hintText: 'Description',
              ),
              const SizedBox(height: 12),
              if (!_isServiceType)
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: const Text('Stockable Item'),
                  subtitle: const Text('Enable stock tracking for this item'),
                  value: _isStockable,
                  onChanged: (v) => setState(() => _isStockable = v),
                  dense: true,
                ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Active Item'),
                subtitle: const Text('Inactive items won\'t appear in dropdowns'),
                value: _isActive,
                onChanged: (v) => setState(() => _isActive = v),
                dense: true,
              ),
              const SizedBox(height: 8),
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

  String _capitalize(String value) {
    if (value.isEmpty) return value;
    return '${value[0].toUpperCase()}${value.substring(1)}';
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => isSaving = true);
    try {
      await controller.save(
        ItemEntity(
          id: widget.entity?.id,
          localId: widget.entity?.localId,
          itemCode: _codeController.text.trim(),
          name: _nameController.text.trim(),
          hsnSacCode: _hsnController.text.trim(),
          type: type,
          categoryId: categoryId,
          taxRateId: taxRateId,
          incomeAccountId: incomeAccountId,
          expenseAccountId: expenseAccountId,
          purchasePrice: double.tryParse(_purchaseController.text.trim()) ?? 0,
          sellingPrice: double.tryParse(_sellingController.text.trim()) ?? 0,
          unit: _unit,
          description: _descriptionController.text.trim(),
          barcode: _barcodeController.text.trim(),
          openingStock: double.tryParse(_stockController.text.trim()) ?? 0,
          isStockable: _isStockable,
          isActive: _isActive,
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

class _InfoNoteCard extends StatelessWidget {
  const _InfoNoteCard({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: theme.colorScheme.primary.withValues(alpha: .06),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: theme.colorScheme.primary.withValues(alpha: .10),
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Icon(
            Icons.info_outline_rounded,
            size: 18,
            color: theme.colorScheme.primary,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
                fontWeight: FontWeight.w500,
                height: 1.35,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
