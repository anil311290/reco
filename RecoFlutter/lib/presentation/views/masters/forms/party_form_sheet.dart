import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/masters/masters_lookup_controller.dart';
import '../../../controllers/masters/parties_controller.dart';
import '../../../widgets/common/common_button.dart';
import '../../../widgets/common/custom_text_field.dart';

class PartyFormSheet extends StatefulWidget {
  const PartyFormSheet({super.key, this.entity});

  final PartyEntity? entity;

  @override
  State<PartyFormSheet> createState() => _PartyFormSheetState();
}

class _PartyFormSheetState extends State<PartyFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _codeController = TextEditingController();
  final _nameController = TextEditingController();
  final _mobileController = TextEditingController();
  final _emailController = TextEditingController();
  final _addressController = TextEditingController();
  final _postalController = TextEditingController();
  final _gstController = TextEditingController();
  final _panController = TextEditingController();
  final _amountController = TextEditingController(text: '0');
  final _dateController = TextEditingController();
  final _remarksController = TextEditingController();

  late final PartiesController controller;
  late final MastersLookupController lookupController;
  bool isSaving = false;
  String partyType = 'debtor';
  String openingBalanceType = 'debit';
  int? selectedStateId;
  int? selectedCityId;
  bool _isActive = true;

  @override
  void initState() {
    super.initState();
    controller = Get.find<PartiesController>();
    lookupController = Get.find<MastersLookupController>();
    final entity = widget.entity;
    _codeController.text = entity?.partyCode ?? '';
    _nameController.text = entity?.name ?? '';
    _mobileController.text = entity?.mobile ?? '';
    _emailController.text = entity?.email ?? '';
    _addressController.text = entity?.address ?? '';
    _postalController.text = entity?.postalCode ?? '';
    _gstController.text = entity?.gstin ?? '';
    _panController.text = entity?.panNumber ?? '';
    _amountController.text = '${entity?.openingBalance ?? 0}';
    _dateController.text = entity?.openingDate ?? '';
    _remarksController.text = entity?.remarks ?? '';
    partyType = entity?.type ?? 'debtor';
    openingBalanceType = entity?.openingBalanceType ?? 'debit';
    selectedStateId = entity?.stateId;
    selectedCityId = entity?.cityId;
    _isActive = entity?.isActive ?? true;
    if (selectedStateId != null) {
      lookupController.loadCitiesForState(selectedStateId);
    }
  }

  @override
  void dispose() {
    _codeController.dispose();
    _nameController.dispose();
    _mobileController.dispose();
    _emailController.dispose();
    _addressController.dispose();
    _postalController.dispose();
    _gstController.dispose();
    _panController.dispose();
    _amountController.dispose();
    _dateController.dispose();
    _remarksController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.entity == null ? 'Create Party' : 'Edit Party',
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
                label: 'Party Code',
                hintText: 'AR0001 / AP0001',
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _nameController,
                label: 'Party Name',
                hintText: 'Party name',
                validator: _required,
              ),
              const SizedBox(height: 12),
              Row(
                children: <Widget>[
                  Expanded(
                    child: CustomDropdown<String>(
                      label: 'Party Type',
                      items: const <String>['debtor', 'creditor'],
                      value: partyType,
                      itemLabelBuilder: _capitalize,
                      onChanged: (value) =>
                          setState(() => partyType = value ?? partyType),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: CustomTextField(
                      controller: _mobileController,
                      label: 'Mobile',
                      hintText: 'Mobile',
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: <Widget>[
                  Expanded(
                    child: CustomTextField(
                      controller: _emailController,
                      label: 'Email',
                      hintText: 'Email',
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: CustomTextField(
                      controller: _gstController,
                      label: 'GSTIN',
                      hintText: 'GSTIN',
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _panController,
                label: 'PAN Number',
                hintText: 'PAN',
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _addressController,
                label: 'Address',
                hintText: 'Address',
                validator: _required,
              ),
              const SizedBox(height: 12),
              Obx(
                () => CustomDropdown<int>(
                  label: 'State',
                  items: lookupController.states
                      .map((item) => item.id)
                      .toList(),
                  value: selectedStateId,
                  requiredField: true,
                  itemLabelBuilder: (id) => lookupController.states
                      .firstWhere((item) => item.id == id)
                      .label,
                  validator: _requiredDropdown,
                  onChanged: (value) async {
                    setState(() {
                      selectedStateId = value;
                      selectedCityId = null;
                    });
                    await lookupController.loadCitiesForState(value);
                  },
                ),
              ),
              const SizedBox(height: 12),
              Obx(
                () => CustomDropdown<int>(
                  label: 'City',
                  items: lookupController.cities
                      .map((item) => item.id)
                      .toList(),
                  value: selectedCityId,
                  requiredField: true,
                  itemLabelBuilder: (id) => lookupController.cities
                      .firstWhere((item) => item.id == id)
                      .label,
                  validator: _requiredDropdown,
                  onChanged: (value) => setState(() => selectedCityId = value),
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: <Widget>[
                  Expanded(
                    child: CustomTextField(
                      controller: _postalController,
                      label: 'Postal Code',
                      hintText: 'Postal code',
                      validator: _required,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: CustomTextField(
                      controller: _amountController,
                      label: 'Opening Balance',
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
                      label: 'Balance Type',
                      items: const <String>['debit', 'credit'],
                      value: openingBalanceType,
                      requiredField: true,
                      itemLabelBuilder: _capitalize,
                      validator: _requiredDropdown,
                      onChanged: (value) => setState(
                        () => openingBalanceType = value ?? openingBalanceType,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: CustomTextField(
                      controller: _dateController,
                      label: 'Opening Date',
                      hintText: 'YYYY-MM-DD',
                      readOnly: true,
                      onTap: _pickDate,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _remarksController,
                label: 'Remarks',
                hintText: 'Remarks',
              ),
              const SizedBox(height: 12),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Active Party'),
                subtitle: const Text('Inactive parties won\'t appear in dropdowns'),
                value: _isActive,
                onChanged: (v) => setState(() => _isActive = v),
                dense: true,
              ),
              const SizedBox(height: 12),
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

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (picked != null) {
      _dateController.text = picked.toIso8601String().split('T').first;
    }
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
        PartyEntity(
          id: widget.entity?.id,
          localId: widget.entity?.localId,
          partyCode: _codeController.text.trim(),
          name: _nameController.text.trim(),
          type: partyType,
          mobile: _mobileController.text.trim(),
          email: _emailController.text.trim(),
          address: _addressController.text.trim(),
          stateId: selectedStateId,
          state:
              lookupController.states
                  .firstWhereOrNull((item) => item.id == selectedStateId)
                  ?.label ??
              '',
          cityId: selectedCityId,
          city:
              lookupController.cities
                  .firstWhereOrNull((item) => item.id == selectedCityId)
                  ?.label ??
              '',
          postalCode: _postalController.text.trim(),
          gstin: _gstController.text.trim(),
          panNumber: _panController.text.trim(),
          openingBalance: double.tryParse(_amountController.text.trim()) ?? 0,
          openingBalanceType: openingBalanceType,
          openingDate: _dateController.text.trim(),
          remarks: _remarksController.text.trim(),
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

  String? _requiredDropdown<T>(T? value) =>
      value == null ? 'This field is required' : null;
}
