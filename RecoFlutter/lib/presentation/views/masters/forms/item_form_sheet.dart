import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';

import '../../../../core/utils/app_snackbar.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/masters/categories_controller.dart';
import '../../../controllers/masters/items_controller.dart';
import '../../../controllers/masters/masters_lookup_controller.dart';
import '../../../widgets/common/app_help_dialog.dart';
import '../../../widgets/common/common_button.dart';
import '../../../widgets/common/custom_text_field.dart';

class ItemFormSheet extends StatefulWidget {
  const ItemFormSheet({super.key, this.entity, this.initialType});

  final ItemEntity? entity;
  final String? initialType;

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
  final _descriptionController = TextEditingController();
  final _barcodeController = TextEditingController();
  final _stockController = TextEditingController(text: '0');
  final _quickCategoryNameController = TextEditingController();
  final _quickCategoryDescriptionController = TextEditingController();

  late final ItemsController controller;
  late final CategoriesController categoriesController;
  late final MastersLookupController lookupController;

  bool isSaving = false;
  bool isQuickAddingCategory = false;
  String type = 'goods';
  int? categoryId;
  int? taxRateId;
  String _unit = 'nos';
  bool _isActive = true;

  bool get _isServiceType => type == 'service';
  bool get _isEditMode => widget.entity != null;

  @override
  void initState() {
    super.initState();
    controller = Get.find<ItemsController>();
    categoriesController = Get.find<CategoriesController>();
    lookupController = Get.find<MastersLookupController>();

    final entity = widget.entity;
    _codeController.text = entity?.itemCode ?? '';
    _nameController.text = entity?.name ?? '';
    _hsnController.text = entity?.hsnSacCode ?? '';
    _purchaseController.text = _formatNumber(entity?.purchasePrice ?? 0);
    _sellingController.text = _formatNumber(entity?.sellingPrice ?? 0);
    _descriptionController.text = entity?.description ?? '';
    _barcodeController.text = entity?.barcode ?? '';
    _stockController.text = _formatNumber(entity?.openingStock ?? 0);
    type = entity?.type ?? widget.initialType ?? 'goods';
    categoryId = entity?.categoryId;
    taxRateId = entity?.taxRateId;
    _unit = entity?.unit.isNotEmpty == true
        ? entity!.unit
        : (_isServiceType ? 'hrs' : 'nos');
    _isActive = entity?.isActive ?? true;

    if (_isServiceType) {
      _purchaseController.text = '0';
      _barcodeController.clear();
      _stockController.text = '0';
      if (!const <String>['hrs', 'nos'].contains(_unit)) {
        _unit = 'hrs';
      }
    }
  }

  @override
  void dispose() {
    _codeController.dispose();
    _nameController.dispose();
    _hsnController.dispose();
    _purchaseController.dispose();
    _sellingController.dispose();
    _descriptionController.dispose();
    _barcodeController.dispose();
    _stockController.dispose();
    _quickCategoryNameController.dispose();
    _quickCategoryDescriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: Text(
          _isEditMode
              ? (_isServiceType ? 'Edit Service' : 'Edit Item')
              : (_isServiceType ? 'Create Service' : 'Create Item'),
          style: theme.textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: <Widget>[
              Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: <Widget>[
                          Text(
                            _isEditMode
                                ? (_isServiceType ? 'Edit Service' : 'Edit Item')
                                : (_isServiceType ? 'Create Service' : 'Create Item'),
                            style: theme.textTheme.titleLarge?.copyWith(
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            _isEditMode
                                ? 'Update item details, pricing, tax, and status information.'
                                : (_isServiceType
                                      ? 'Add service details, tax setup, and default pricing.'
                                      : 'Add item details, stock setup, pricing, and tax information.'),
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: scheme.onSurfaceVariant,
                              height: 1.35,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 12),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: (_isServiceType
                                ? const Color(0xFF0EA5E9)
                                : scheme.primary)
                            .withValues(alpha: .10),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        _isServiceType ? 'Service' : 'Goods',
                        style: theme.textTheme.labelSmall?.copyWith(
                          color: _isServiceType
                              ? const Color(0xFF0EA5E9)
                              : scheme.primary,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              CustomTextField(
                controller: _codeController,
                label: 'Item Code',
                hintText: 'Auto-generated on save',
                readOnly: true,
                bottomPadding: 14,
              ),
              CustomTextField(
                controller: _nameController,
                label: 'Name',
                hintText: 'Enter item or service name',
                requiredField: true,
                validator: _required,
                keyboardType: TextInputType.name,
                textInputAction: TextInputAction.next,
                bottomPadding: 14,
              ),
              _QuickAddCategoryAction(
                onTap: _showQuickAddCategoryDialog,
              ),
              Obx(
                () => CustomDropdown<int>(
                  label: 'Category',
                  hint: 'Select Category',
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
              CustomTextField(
                controller: _hsnController,
                label: _isServiceType ? 'SAC Code' : 'HSN/SAC Code',
                hintText: '',
                textInputAction: TextInputAction.next,
                bottomPadding: 14,
              ),
              Obx(
                () => CustomDropdown<int>(
                  label: 'Tax Rate',
                  hint: 'Select Tax Rate',
                  items: lookupController.taxes
                      .map((item) => item.id)
                      .toList(),
                  value: taxRateId,
                  itemLabelBuilder: (id) => lookupController.taxes
                      .firstWhere((item) => item.id == id)
                      .label,
                  onChanged: (value) => setState(() => taxRateId = value),
                ),
              ),
              if (!_isServiceType)
                CustomDropdown<String>(
                  label: 'Unit',
                  hint: 'Select Unit',
                  items: _unitOptions.map((item) => item.value).toList(),
                  value: _unit,
                  itemLabelBuilder: (value) => _unitLabel(value),
                  onChanged: (value) => setState(() => _unit = value ?? _unit),
                ),
              if (!_isServiceType)
                CustomTextField(
                  controller: _purchaseController,
                  label: 'Purchase Price',
                  hintText: '0',
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: <TextInputFormatter>[
                    FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
                  ],
                  bottomPadding: 14,
                ),
              if (!_isServiceType)
                CustomTextField(
                  controller: _stockController,
                  label: 'Opening Qty.',
                  hintText: '0',
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: <TextInputFormatter>[
                    FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
                  ],
                  bottomPadding: 14,
                ),
              if (!_isServiceType)
                CustomTextField(
                  controller: _sellingController,
                  label: 'Selling Price',
                  hintText: '0',
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: <TextInputFormatter>[
                    FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
                  ],
                  bottomPadding: 14,
                ),
              if (_isServiceType)
                CustomTextField(
                  controller: _sellingController,
                  label: 'Default Rate',
                  hintText: '0',
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: <TextInputFormatter>[
                    FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
                  ],
                  bottomPadding: 0,
                ),
              if (_isServiceType) ...<Widget>[
                const SizedBox(height: 4),
                Align(
                  alignment: Alignment.centerLeft,
                  child: AppHelpDialogButton(
                    title: 'Service Pricing Help',
                    tooltip: 'Default rate help',
                    label: 'Default rate help',
                    sections: const <AppHelpDialogSection>[
                      AppHelpDialogSection(
                        title: 'Default rate',
                        message:
                            'Default rate is optional for service items. The amount can still be changed later in the sales invoice.',
                      ),
                    ],
                  ),
                ),
                if (_isEditMode) ...<Widget>[
                  const SizedBox(height: 10),
                  const _InlineInfo(
                    title: 'Service behavior',
                    text:
                        'Stock does not apply to service items. They are non-stockable and post to Service Revenue via income account.',
                  ),
                ],
              ],
              if (!_isServiceType)
                CustomTextField(
                  controller: _barcodeController,
                  label: 'Barcode',
                  hintText: '',
                  textInputAction: TextInputAction.next,
                  keyboardType: TextInputType.text,
                  bottomPadding: 14,
                ),
              CustomTextField(
                controller: _descriptionController,
                label: 'Description',
                hintText: '',
                maxLines: 2,
                keyboardType: TextInputType.multiline,
                textInputAction: TextInputAction.newline,
                bottomPadding: 0,
              ),
              const SizedBox(height: 16),
              CommonButton(
                text: _isEditMode
                    ? (_isServiceType ? 'Update Service' : 'Update Item')
                    : (_isServiceType ? 'Save Service' : 'Save Item'),
                isLoading: isSaving,
                onPressed: _submit,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _showQuickAddCategoryDialog() async {
    _quickCategoryNameController.clear();
    _quickCategoryDescriptionController.clear();
    isQuickAddingCategory = false;

    await showDialog<void>(
      context: context,
      builder: (dialogContext) {
        final theme = Theme.of(dialogContext);
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              insetPadding: const EdgeInsets.symmetric(horizontal: 20),
              contentPadding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              titlePadding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
              title: Text(
                'Quick Add Category',
                style: theme.textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
              ),
              content: SizedBox(
                width: double.maxFinite,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: <Widget>[
                    CustomTextField(
                      controller: _quickCategoryNameController,
                      label: 'Category Name',
                      hintText: 'Enter category name',
                      requiredField: true,
                      keyboardType: TextInputType.name,
                    ),
                    CustomTextField(
                      controller: _quickCategoryDescriptionController,
                      label: 'Description',
                      hintText: 'Enter category description',
                      maxLines: 3,
                      keyboardType: TextInputType.multiline,
                      textInputAction: TextInputAction.newline,
                      bottomPadding: 0,
                    ),
                  ],
                ),
              ),
              actions: <Widget>[
                TextButton(
                  onPressed: isQuickAddingCategory
                      ? null
                      : () => Navigator.of(dialogContext).pop(),
                  child: const Text('Cancel'),
                ),
                FilledButton(
                  onPressed: isQuickAddingCategory
                      ? null
                      : () => _submitQuickCategory(setDialogState, dialogContext),
                  child: isQuickAddingCategory
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Add Category'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  Future<void> _submitQuickCategory(
    void Function(void Function()) setDialogState,
    BuildContext dialogContext,
  ) async {
    final name = _quickCategoryNameController.text.trim();
    if (name.isEmpty) {
      AppSnackbar.warning('Category name is required.');
      return;
    }

    setDialogState(() => isQuickAddingCategory = true);
    try {
      await categoriesController.save(
        ItemCategoryEntity(
          name: name,
          description: _quickCategoryDescriptionController.text.trim(),
          sortOrder: 0,
          isActive: true,
        ),
      );
      await lookupController.loadItemLookups();

      final created = lookupController.categories.firstWhereOrNull(
        (item) => item.label.trim().toLowerCase() == name.toLowerCase(),
      );
      if (created != null && mounted) {
        setState(() => categoryId = created.id);
      } else {
        AppSnackbar.warning(
          'Category saved. It will appear after server sync completes.',
        );
      }

      if (dialogContext.mounted) {
        Navigator.of(dialogContext).pop();
      }
    } finally {
      if (mounted) {
        setDialogState(() => isQuickAddingCategory = false);
      }
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

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
          purchasePrice:
              _isServiceType ? 0 : (double.tryParse(_purchaseController.text.trim()) ?? 0),
          sellingPrice: double.tryParse(_sellingController.text.trim()) ?? 0,
          unit: _unit,
          description: _descriptionController.text.trim(),
          barcode: _isServiceType ? '' : _barcodeController.text.trim(),
          openingStock:
              _isServiceType ? 0 : (double.tryParse(_stockController.text.trim()) ?? 0),
          isStockable: !_isServiceType,
          isActive: _isActive,
        ),
      );
      if (mounted) {
        Navigator.of(context).pop(true);
      }
    } finally {
      if (mounted) {
        setState(() => isSaving = false);
      }
    }
  }

  String _formatNumber(num value) {
    if (value == value.roundToDouble()) {
      return value.toInt().toString();
    }
    return value.toString();
  }

  String _unitLabel(String value) {
    const labels = <String, String>{
      'nos': 'Numbers (Nos)',
      'hrs': 'Hours (Hrs)',
      'kg': 'Kilogram (Kg)',
      'ltr': 'Litre (Ltr)',
      'mtr': 'Metre (Mtr)',
      'pcs': 'Pieces (Pcs)',
      'box': 'Box',
      'set': 'Set',
    };
    return labels[value] ?? value;
  }

  String? _required(String? value) {
    return (value ?? '').trim().isEmpty ? 'Required field' : null;
  }
}

const List<_UnitOption> _unitOptions = <_UnitOption>[
  _UnitOption('nos'),
  _UnitOption('hrs'),
  _UnitOption('kg'),
  _UnitOption('ltr'),
  _UnitOption('mtr'),
  _UnitOption('pcs'),
  _UnitOption('box'),
  _UnitOption('set'),
];

class _UnitOption {
  const _UnitOption(this.value);
  final String value;
}

class _QuickAddCategoryAction extends StatelessWidget {
  const _QuickAddCategoryAction({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Align(
        alignment: Alignment.centerRight,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(999),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                Icon(
                  Icons.add_circle_outline_rounded,
                  size: 16,
                  color: theme.colorScheme.primary,
                ),
                const SizedBox(width: 4),
                Text(
                  'Quick Add',
                  style: theme.textTheme.labelLarge?.copyWith(
                    color: theme.colorScheme.primary,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _InlineInfo extends StatelessWidget {
  const _InlineInfo({
    required this.title,
    required this.text,
  });

  final String title;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.centerLeft,
      child: AppHelpDialogButton(
        title: title,
        tooltip: title,
        label: title,
        sections: <AppHelpDialogSection>[
          AppHelpDialogSection(
            title: title,
            message: text,
          ),
        ],
      ),
    );
  }
}
