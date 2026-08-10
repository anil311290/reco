import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/utils/app_date_formatter.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/settings/financial_years_controller.dart';
import '../../../widgets/common/common_button.dart';
import '../../../widgets/common/custom_text_field.dart';

class FinancialYearFormSheet extends StatefulWidget {
  const FinancialYearFormSheet({super.key, this.entity});
  final FinancialYearEntity? entity;

  @override
  State<FinancialYearFormSheet> createState() => _FinancialYearFormSheetState();
}

class _FinancialYearFormSheetState extends State<FinancialYearFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _startDateController = TextEditingController();
  final _endDateController = TextEditingController();
  late final FinancialYearsController _controller;
  bool _isSaving = false;

  bool get _isEdit => widget.entity != null;

  @override
  void initState() {
    super.initState();
    _controller = Get.find<FinancialYearsController>();
    final entity = widget.entity;
    _nameController.text = entity?.name ?? '';
    _startDateController.text = entity?.startDate.isNotEmpty == true
        ? AppDateFormatter.formatDisplay(entity!.startDate)
        : '';
    _endDateController.text = entity?.endDate.isNotEmpty == true
        ? AppDateFormatter.formatDisplay(entity!.endDate)
        : '';
  }

  @override
  void dispose() {
    _nameController.dispose();
    _startDateController.dispose();
    _endDateController.dispose();
    super.dispose();
  }

  Future<void> _onSave() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSaving = true);
    try {
      final entity = FinancialYearEntity(
        id: widget.entity?.id,
        localId: widget.entity?.localId,
        name: _nameController.text.trim(),
        startDate: AppDateFormatter.toApiDate(_startDateController.text.trim()),
        endDate: AppDateFormatter.toApiDate(_endDateController.text.trim()),
      );

      if (_isEdit) {
        await _controller.updateFinancialYear(entity);
      } else {
        await _controller.createFinancialYear(entity);
      }

      if (mounted) Get.back();
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  Future<void> _pickDate(TextEditingController controller) async {
    final initial = AppDateFormatter.parse(controller.text.trim());
    final picked = await showDatePicker(
      context: context,
      initialDate: initial ?? DateTime.now(),
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
      helpText: 'Select date',
    );
    if (picked != null) {
      controller.text = AppDateFormatter.formatDisplay(picked);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          _isEdit ? 'Edit Financial Year' : 'Create Financial Year',
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
              CustomTextField(
                controller: _nameController,
                label: 'Financial Year Name',
                hintText: 'e.g. FY 2025-26',
                validator: _required,
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _startDateController,
                label: 'Start Date',
                hintText: 'DD-MMM-YYYY',
                validator: _required,
                readOnly: true,
                onTap: () => _pickDate(_startDateController),
                suffixIcon: Icons.calendar_today,
                onSuffixTap: () => _pickDate(_startDateController),
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _endDateController,
                label: 'End Date',
                hintText: 'DD-MMM-YYYY',
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return 'End date is required';
                  }
                  final start = AppDateFormatter.parse(
                    _startDateController.text.trim(),
                  );
                  final end = AppDateFormatter.parse(value.trim());
                  if (start != null && end != null && !end.isAfter(start)) {
                    return 'End date must be after start date';
                  }
                  return null;
                },
                readOnly: true,
                onTap: () => _pickDate(_endDateController),
                suffixIcon: Icons.calendar_today,
                onSuffixTap: () => _pickDate(_endDateController),
              ),
              const SizedBox(height: 20),
              CommonButton(
                text: _isEdit ? 'Update Financial Year' : 'Create Financial Year',
                isLoading: _isSaving,
                onPressed: _onSave,
              ),
            ],
          ),
        ),
      ),
    );
  }

  String? _required(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'This field is required';
    }
    return null;
  }
}
