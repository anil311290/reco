import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/masters/tax_rates_controller.dart';
import '../../../widgets/common/common_button.dart';
import '../../../widgets/common/custom_text_field.dart';

class TaxRateFormSheet extends StatefulWidget {
  const TaxRateFormSheet({super.key, this.entity});
  final TaxRateEntity? entity;
  @override
  State<TaxRateFormSheet> createState() => _TaxRateFormSheetState();
}

class _TaxRateFormSheetState extends State<TaxRateFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _codeController = TextEditingController();
  final _nameController = TextEditingController();
  final _rateController = TextEditingController(text: '0');
  final _notesController = TextEditingController();
  late final TaxRatesController controller;
  bool isSaving = false;
  String type = 'addition';
  String category = 'GST';
  String status = 'active';

  @override
  void initState() {
    super.initState();
    controller = Get.find<TaxRatesController>();
    final entity = widget.entity;
    _codeController.text = entity?.taxCode ?? '';
    _nameController.text = entity?.taxName ?? '';
    _rateController.text = '${entity?.taxRate ?? 0}';
    _notesController.text = entity?.notes ?? '';
    type = entity?.taxType ?? 'addition';
    category = entity?.taxCategory ?? 'GST';
    status = entity?.status ?? 'active';
  }

  @override
  void dispose() {
    _codeController.dispose();
    _nameController.dispose();
    _rateController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.entity == null ? 'Create Tax Rate' : 'Edit Tax Rate',
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
                label: 'Tax Code',
                hintText: 'Tax code',
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _nameController,
                label: 'Tax Name',
                hintText: 'Tax name',
                validator: _required,
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _rateController,
                label: 'Tax Rate',
                hintText: '0',
              ),
              const SizedBox(height: 12),
              CustomDropdown<String>(
                label: 'Tax Type',
                items: const ['addition', 'deduction'],
                value: type,
                itemLabelBuilder: _capitalize,
                onChanged: (value) => setState(() => type = value ?? type),
              ),
              const SizedBox(height: 12),
              CustomDropdown<String>(
                label: 'Tax Category',
                items: const [
                  'GST',
                  'CGST',
                  'SGST',
                  'IGST',
                  'TDS',
                  'TCS',
                  'CESS',
                  'OTHER',
                ],
                value: category,
                itemLabelBuilder: _capitalize,
                onChanged: (value) =>
                    setState(() => category = value ?? category),
              ),
              const SizedBox(height: 12),
              CustomDropdown<String>(
                label: 'Status',
                items: const ['active', 'inactive'],
                value: status,
                itemLabelBuilder: _capitalize,
                onChanged: (value) => setState(() => status = value ?? status),
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _notesController,
                label: 'Notes',
                hintText: 'Notes',
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

  String _capitalize(String value) {
    if (value.isEmpty) return value;
    return '${value[0].toUpperCase()}${value.substring(1)}';
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => isSaving = true);
    try {
      await controller.save(
        TaxRateEntity(
          id: widget.entity?.id,
          localId: widget.entity?.localId,
          taxCode: _codeController.text.trim(),
          taxName: _nameController.text.trim(),
          taxRate: double.tryParse(_rateController.text.trim()) ?? 0,
          taxType: type,
          taxCategory: category,
          notes: _notesController.text.trim(),
          status: status,
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
