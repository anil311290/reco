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
  bool _isActive = true;

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
    _isActive = entity?.isActive ?? true;
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
                itemLabelBuilder: _capitalize,
                onChanged: (value) =>
                    setState(() => accountType = value ?? accountType),
              ),
              const SizedBox(height: 12),
              CustomDropdown<String>(
                label: 'Transaction Mode',
                items: const ['cash', 'bank', 'od'],
                value: transactionMode,
                itemLabelBuilder: _capitalize,
                onChanged: (value) => setState(() => transactionMode = value),
              ),
              Padding(
                padding: const EdgeInsets.only(left: 4, right: 4, bottom: 12),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Expanded(
                      child: Text(
                        'Transaction mode is only for cash, bank, or OD ledgers. For normal ledgers, leave it blank.',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: Theme.of(context)
                                  .colorScheme
                                  .onSurfaceVariant,
                              height: 1.35,
                            ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    InkWell(
                      borderRadius: BorderRadius.circular(20),
                      onTap: () => _showTransactionModeHelp(context),
                      child: Padding(
                        padding: const EdgeInsets.all(4),
                        child: Icon(
                          Icons.info_outline_rounded,
                          size: 18,
                          color: Theme.of(context).colorScheme.primary,
                        ),
                      ),
                    ),
                  ],
                ),
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
                      itemLabelBuilder: _capitalize,
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
                readOnly: true,
                onTap: () => _pickDate(_dateController),
                suffixIcon: Icons.calendar_today,
                onSuffixTap: () => _pickDate(_dateController),
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
                title: const Text('Active Account'),
                subtitle: const Text('Inactive accounts won\'t appear in dropdowns'),
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

  Future<void> _pickDate(TextEditingController controller) async {
    final initial = DateTime.tryParse(controller.text.trim());
    final picked = await showDatePicker(
      context: context,
      initialDate: initial ?? DateTime.now(),
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
      helpText: 'Select date',
    );
    if (picked != null) {
      controller.text =
          '${picked.year}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
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

  void _showTransactionModeHelp(BuildContext context) {
    showDialog<void>(
      context: context,
      builder: (dialogContext) {
        final scheme = Theme.of(dialogContext).colorScheme;
        return AlertDialog(
          insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          titlePadding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
          contentPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
          actionsPadding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
          title: Row(
            children: <Widget>[
              Icon(Icons.info_outline_rounded, color: scheme.primary, size: 20),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  'Transaction Mode Help',
                  style: Theme.of(dialogContext).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                ),
              ),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              _HelpBlock(
                title: 'Cash',
                text: 'Use for physical cash accounts and petty cash balances.',
              ),
              const SizedBox(height: 10),
              _HelpBlock(
                title: 'Bank',
                text: 'Use for current, savings, and online bank ledger accounts.',
              ),
              const SizedBox(height: 10),
              _HelpBlock(
                title: 'OD',
                text: 'Use for overdraft or cash-credit style accounts.',
              ),
              const SizedBox(height: 14),
              Text(
                'Other info:',
                style: Theme.of(dialogContext).textTheme.bodySmall?.copyWith(
                      fontWeight: FontWeight.w700,
                      color: scheme.onSurfaceVariant,
                    ),
              ),
              const SizedBox(height: 6),
              Text(
                'Transaction mode appears only for asset accounts. Keep it blank for regular ledgers.',
                style: Theme.of(dialogContext).textTheme.bodySmall?.copyWith(
                      color: scheme.onSurfaceVariant,
                      height: 1.35,
                    ),
              ),
            ],
          ),
          actions: <Widget>[
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(),
              child: const Text('Close'),
            ),
          ],
        );
      },
    );
  }
}

class _HelpBlock extends StatelessWidget {
  const _HelpBlock({required this.title, required this.text});

  final String title;
  final String text;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: scheme.surfaceContainerHighest.withValues(alpha: .45),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            title,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
          ),
          const SizedBox(height: 4),
          Text(
            text,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: scheme.onSurfaceVariant,
                  height: 1.3,
                ),
          ),
        ],
      ),
    );
  }
}
