import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/utils/app_date_formatter.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../../data/repositories/masters/financial_years_repository.dart';
import '../../../controllers/masters/accounts_controller.dart';
import '../../../widgets/common/app_help_dialog.dart';
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
  final _remarksController = TextEditingController();

  late final AccountsController controller;
  late final FinancialYearsRepository _financialYearsRepository;

  bool isSaving = false;
  bool _isActive = true;
  String accountType = 'asset';
  String balanceType = 'debit';
  bool isCashBankOd = false;
  String _openingDateApi = AppDateFormatter.toApiDate(DateTime.now());
  String _openingDateDisplay = AppDateFormatter.formatDisplay(DateTime.now());

  bool get _isCreate => widget.entity == null;
  bool get _isAssetAccount => accountType == 'asset';
  bool get _canEditAccountType =>
      _isCreate ||
      (!(widget.entity?.isSystem ?? false) && !(widget.entity?.isInUse ?? false));
  bool get _canEditCashBankToggle =>
      _isCreate || !(widget.entity?.isInUse ?? false);

  @override
  void initState() {
    super.initState();
    controller = Get.find<AccountsController>();
    _financialYearsRepository = Get.find<FinancialYearsRepository>();

    final entity = widget.entity;
    _nameController.text = entity?.accountName ?? '';
    _amountController.text = '${entity?.openingBalance ?? 0}';
    _remarksController.text = entity?.remarks ?? '';
    accountType = entity?.accountType ?? 'asset';
    isCashBankOd = entity?.isCashBankOd ?? false;
    balanceType = entity?.balanceType ?? _defaultBalanceType(accountType);
    _isActive = entity?.isActive ?? true;

    _syncAccountTypeState(accountType, onlyCashBank: true);
    _loadOpeningDateDefault();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _amountController.dispose();
    _remarksController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: Text(
          _isCreate ? 'Create Account' : 'Edit Account',
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
              Container(
                margin: const EdgeInsets.only(bottom: 16),
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(24),
                  gradient: LinearGradient(
                    colors: <Color>[
                      scheme.primary.withValues(alpha: .12),
                      scheme.secondary.withValues(alpha: .08),
                    ],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  border: Border.all(
                    color: scheme.primary.withValues(alpha: .14),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 7,
                      ),
                      decoration: BoxDecoration(
                        color: scheme.surface.withValues(alpha: .9),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        'Account Master',
                        style: theme.textTheme.labelMedium?.copyWith(
                          color: scheme.primary,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                    const SizedBox(height: 10),
                    Text(
                      _isCreate
                          ? 'Create a new ledger account'
                          : 'Edit account details',
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Create clean chart-of-accounts entries with smart code generation, opening balance support, and asset cash-bank eligibility control.',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: scheme.onSurfaceVariant,
                        height: 1.35,
                      ),
                    ),
                  ],
                ),
              ),

              if (!_isCreate) ...<Widget>[
                const SizedBox(height: 12),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: theme.cardColor,
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(
                      color: scheme.outlineVariant.withValues(alpha: .7),
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Text(
                        'Account Code',
                        style: theme.textTheme.labelMedium?.copyWith(
                          color: scheme.onSurfaceVariant,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        widget.entity?.accountCode.isNotEmpty == true
                            ? widget.entity!.accountCode
                            : 'Generated ledger',
                        style: theme.textTheme.titleSmall?.copyWith(
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Account code is preserved from the existing record.',
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: scheme.onSurfaceVariant,
                          height: 1.35,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 12),
              CustomTextField(
                controller: _nameController,
                label: 'Account Name',
                hintText: 'e.g., Cash, SBI Bank, Trade Payables',
                requiredField: true,
                validator: _required,
              ),
              CustomDropdown<String>(
                label: 'Account Type',
                items: const <String>[
                  'asset',
                  'liability',
                  'income',
                  'expense',
                  'equity',
                ],
                value: accountType,
                itemLabelBuilder: _capitalize,
                requiredField: true,
                enabled: _canEditAccountType,
                onChanged: (value) {
                  if (value == null || !_canEditAccountType) return;
                  setState(() => _syncAccountTypeState(value));
                },
              ),
              const SizedBox(height: 12),
              if (!_isCreate && (widget.entity?.isInUse ?? false))
                Container(
                  margin: const EdgeInsets.only(bottom: 12),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: scheme.secondaryContainer.withValues(alpha: .42),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    'This ledger is already linked to transactions. You can rename it, update notes, or change active status only.',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: scheme.onSurfaceVariant,
                      fontWeight: FontWeight.w600,
                      height: 1.35,
                    ),
                  ),
                ),
              if (!_isCreate && (widget.entity?.isSystem ?? false))
                Container(
                  margin: const EdgeInsets.only(bottom: 12),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: scheme.primary.withValues(alpha: .10),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    'This is a system ledger. Some classification fields are locked.',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: scheme.onSurfaceVariant,
                      fontWeight: FontWeight.w600,
                      height: 1.35,
                    ),
                  ),
                ),
              AnimatedSwitcher(
                duration: const Duration(milliseconds: 180),
                child: _isAssetAccount
                    ? Container(
                        key: const ValueKey<String>('asset-cash-bank-toggle'),
                        margin: const EdgeInsets.only(bottom: 12),
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: theme.cardColor,
                          borderRadius: BorderRadius.circular(18),
                          border: Border.all(
                            color: scheme.outlineVariant.withValues(alpha: .7),
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: <Widget>[
                            Row(
                              children: <Widget>[
                                Expanded(
                                  child: Text(
                                    'Is Cash/Bank/OD?',
                                    style: theme.textTheme.titleSmall?.copyWith(
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                                Text(
                                  'Yes stores 1, No stores 0.',
                                  style: theme.textTheme.bodySmall?.copyWith(
                                    color: scheme.onSurfaceVariant,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 10),
                            SwitchListTile(
                              contentPadding: EdgeInsets.zero,
                              title: Text(
                                'Yes, this is a Cash/Bank/OD ledger',
                                style: theme.textTheme.bodyMedium?.copyWith(
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              value: isCashBankOd,
                              onChanged: _canEditCashBankToggle
                                  ? (value) => setState(() => isCashBankOd = value)
                                  : null,
                            ),
                          ],
                        ),
                      )
                    : const SizedBox.shrink(),
              ),
              Row(
                children: <Widget>[
                  Expanded(
                    child: CustomTextField(
                      controller: _amountController,
                      label: 'Opening Balance',
                      hintText: '0.00',
                      prefixText: '₹ ',
                      readOnly: !_isCreate,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: CustomDropdown<String>(
                      label: 'Balance Type',
                      items: const <String>['debit', 'credit'],
                      value: balanceType,
                      enableSearch: false,
                      itemLabelBuilder: _capitalize,
                      requiredField: true,
                      enabled: _isCreate,
                      onChanged: (value) {
                        if (value == null) return;
                        setState(() => balanceType = value);
                      },
                    ),
                  ),
                ],
              ),
              if (_isCreate) ...<Widget>[
                const SizedBox(height: 5),
                Text(
                  'Opening date is auto-set to current financial year start date: $_openingDateDisplay.',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: scheme.onSurfaceVariant,
                    height: 1.35,
                  ),
                ),
                const SizedBox(height: 8),
              ],
              CustomTextField(
                controller: _remarksController,
                label: 'Notes',
                hintText: 'Add usage hints or internal notes',
                maxLines: 4,
              ),

              const SizedBox(height: 12),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Active Account'),
                subtitle:
                    const Text('Inactive accounts won\'t appear in dropdowns'),
                value: _isActive,
                onChanged: (value) => setState(() => _isActive = value),
                dense: true,
              ),
              const SizedBox(height: 12),
              Row(
                children: <Widget>[
                  Text(
                    'Account Help',
                    style: theme.textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const Spacer(),
                  const AppHelpDialogButton(
                    title: 'Account Help',
                    tooltip: 'Account form help',
                    sections: <AppHelpDialogSection>[
                      AppHelpDialogSection(
                        title: 'Auto-generated code',
                        message:
                            'Account code is generated from the selected account type in the live server flow.',
                      ),
                      AppHelpDialogSection(
                        title: 'Asset accounts',
                        message:
                            'Use Cash / Bank / OD only when this asset should appear in receipt and payment selections.',
                      ),
                      AppHelpDialogSection(
                        title: 'Ledger impact',
                        message:
                            'Saved opening balances flow into vouchers and ledger reports after sync.',
                      ),
                      AppHelpDialogSection(
                        title: 'Opening date',
                        message:
                            'Opening date is auto-set to the current financial year start date. It is not entered manually in the form.',
                      ),
                      AppHelpDialogSection(
                        title: 'Quick tips',
                        message:
                            'Use clear account names such as Cash, HDFC Bank, or Sales Revenue. Keep opening balance and balance type accurate for reports.',
                      ),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 12),
              CommonButton(
                text: _isCreate ? 'Create Account' : 'Update Account',
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

  String _defaultBalanceType(String type) {
    return (type == 'asset' || type == 'expense') ? 'debit' : 'credit';
  }

  Future<void> _loadOpeningDateDefault() async {
    try {
      final currentFinancialYear =
          await _financialYearsRepository.getCurrentFinancialYear();
      final startDate = currentFinancialYear?.startDate.trim();
      if (startDate != null && startDate.isNotEmpty) {
        _openingDateApi = startDate;
        _openingDateDisplay = AppDateFormatter.formatDisplay(startDate);
      }
    } catch (_) {
      _openingDateApi = AppDateFormatter.toApiDate(DateTime.now());
      _openingDateDisplay = AppDateFormatter.formatDisplay(DateTime.now());
    }
    if (mounted) {
      setState(() {});
    }
  }

  void _syncAccountTypeState(String value, {bool onlyCashBank = false}) {
    accountType = value;
    if (!onlyCashBank) {
      balanceType = _defaultBalanceType(value);
    }
    if (value != 'asset') {
      isCashBankOd = false;
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    if (_isCreate) {
      final confirmed = await _confirmOpeningBalance();
      if (!confirmed) return;
    }

    setState(() => isSaving = true);
    try {
      await controller.save(
        AccountEntity(
          id: widget.entity?.id,
          localId: widget.entity?.localId,
          accountCode: widget.entity?.accountCode ?? '',
          accountName: _nameController.text.trim(),
          accountType: _canEditAccountType
              ? accountType
              : (widget.entity?.accountType ?? accountType),
          transactionMode: widget.entity?.transactionMode ?? '',
          isCashBankOd: _isAssetAccount
              ? (_canEditCashBankToggle
                    ? isCashBankOd
                    : (widget.entity?.isCashBankOd ?? false))
              : false,
          openingBalance: _isCreate
              ? (double.tryParse(_amountController.text.trim()) ?? 0)
              : (widget.entity?.openingBalance ?? 0),
          balanceType: _isCreate
              ? balanceType
              : (widget.entity?.balanceType ?? balanceType),
          openingDate: _isCreate
              ? _openingDateApi
              : (widget.entity?.openingDate ??
                  _openingDateApi),
          remarks: _remarksController.text.trim(),
          isActive: _isActive,
          isSystem: widget.entity?.isSystem ?? false,
          isInUse: widget.entity?.isInUse ?? false,
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

  Future<bool> _confirmOpeningBalance() async {
    final amount = double.tryParse(_amountController.text.trim()) ?? 0;
    final message = amount > 0
        ? 'Opening balance of ₹${amount.toStringAsFixed(2)} (${_capitalize(balanceType)}) dated $_openingDateDisplay will be posted and cannot be edited later.'
        : 'No opening balance will be posted. Opening balance cannot be set later after the ledger is created.';

    final result = await showDialog<bool>(
      context: context,
      builder: (dialogContext) {
        final theme = Theme.of(dialogContext);
        final scheme = theme.colorScheme;
        return AlertDialog(
          insetPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 24),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          title: Text(
            'Confirm Opening Balance',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          content: Text(
            message,
            style: theme.textTheme.bodyMedium?.copyWith(
              color: scheme.onSurfaceVariant,
              height: 1.45,
            ),
          ),
          actions: <Widget>[
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(false),
              child: const Text('Review Again'),
            ),
            FilledButton(
              onPressed: () => Navigator.of(dialogContext).pop(true),
              child: const Text('Yes, Create Ledger'),
            ),
          ],
        );
      },
    );
    return result ?? false;
  }

  String? _required(String? value) =>
      (value ?? '').trim().isEmpty ? 'Required field' : null;
}
