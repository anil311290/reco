import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/masters/accounts_controller.dart';
import '../../../widgets/common/common_button.dart';
import '../../../widgets/common/custom_text_field.dart';

class AccountFormSheet extends StatefulWidget {
  const AccountFormSheet({super.key, this.entity});
  final AccountEntity? entity;
  @override
  State<AccountFormSheet> createState() => _AccountFormSheetState();
}

class _AccountFormSheetState extends State<AccountFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _amountController = TextEditingController(text: '0');
  final _dateController = TextEditingController();
  final _remarksController = TextEditingController();
  late final AccountsController controller;
  bool isSaving = false;
  String accountType = 'asset';
  String balanceType = 'debit';
  String? transactionMode;

  @override
  void initState() {
    super.initState();
    controller = Get.find<AccountsController>();
    final entity = widget.entity;
    _nameController.text = entity?.accountName ?? '';
    _amountController.text = '${entity?.openingBalance ?? 0}';
    _dateController.text = entity?.openingDate ?? '';
    _remarksController.text = entity?.remarks ?? '';
    accountType = entity?.accountType ?? 'asset';
    balanceType = entity?.balanceType ?? 'debit';
    transactionMode = entity?.transactionMode.isEmpty == true
        ? null
        : entity?.transactionMode;
  }

  @override
  void dispose() {
    _nameController.dispose();
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
          widget.entity == null ? 'Create Account' : 'Edit Account',
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
                label: 'Account Name',
                hintText: 'Account name',
                validator: _required,
              ),
              const SizedBox(height: 12),
              CustomDropdown<String>(
                label: 'Account Type',
                items: const [
                  'asset',
                  'liability',
                  'income',
                  'expense',
                  'equity',
                ],
                value: accountType,
                itemLabelBuilder: (item) => item,
                onChanged: (value) =>
                    setState(() => accountType = value ?? accountType),
              ),
              const SizedBox(height: 12),
              CustomDropdown<String>(
                label: 'Transaction Mode',
                items: const ['cash', 'bank', 'od'],
                value: transactionMode,
                itemLabelBuilder: (item) => item,
                onChanged: (value) => setState(() => transactionMode = value),
              ),
              const SizedBox(height: 12),
              Row(
                children: <Widget>[
                  Expanded(
                    child: CustomTextField(
                      controller: _amountController,
                      label: 'Opening Balance',
                      hintText: '0',
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: CustomDropdown<String>(
                      label: 'Balance Type',
                      items: const ['debit', 'credit'],
                      value: balanceType,
                      itemLabelBuilder: (item) => item,
                      onChanged: (value) =>
                          setState(() => balanceType = value ?? balanceType),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _dateController,
                label: 'Opening Date',
                hintText: 'YYYY-MM-DD',
              ),
              const SizedBox(height: 12),
              CustomTextField(
                controller: _remarksController,
                label: 'Remarks',
                hintText: 'Remarks',
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
        AccountEntity(
          id: widget.entity?.id,
          localId: widget.entity?.localId,
          accountCode: widget.entity?.accountCode ?? '',
          accountName: _nameController.text.trim(),
          accountType: accountType,
          transactionMode: transactionMode ?? '',
          openingBalance: double.tryParse(_amountController.text.trim()) ?? 0,
          balanceType: balanceType,
          openingDate: _dateController.text.trim(),
          remarks: _remarksController.text.trim(),
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
