import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/config/api_endpoints.dart';
import '../../../../core/utils/app_date_formatter.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../../data/repositories/transactions/transactions_repository.dart';
import '../all_vouchers_controller.dart';
import '../adjustments_controller.dart';
import '../payments_controller.dart';
import '../receipts_controller.dart';
import 'transaction_form_lookup_controller.dart';
import 'transaction_form_models.dart';

abstract class BaseVoucherFormController extends GetxController {
  BaseVoucherFormController(
    this.repository,
    this.lookupController, {
    this.initialPayload,
  });

  final TransactionsRepository repository;
  final TransactionFormLookupController lookupController;
  final Map<String, dynamic>? initialPayload;

  final formKey = GlobalKey<FormState>();
  final dateController = TextEditingController();
  final narrationController = TextEditingController();
  final referenceController = TextEditingController();
  final isSubmitting = false.obs;
  final selectedCashBankAccount = Rxn<LookupOption>();
  final paymentRows = <PaymentVoucherRowModel>[].obs;
  final adjustmentRows = <AdjustmentVoucherRowModel>[].obs;
  Map<String, dynamic>? _editingPayload;

  String get title;
  String get voucherType;
  String get module;
  String get endpoint;

  bool get isPaymentReceipt =>
      voucherType == 'payment' || voucherType == 'receipt';
  bool get isAdjustment => !isPaymentReceipt;
  bool get isEditing => initialPayload != null;
  String get cashBankLabel => voucherType == 'receipt' ? 'Received In' : 'Paid From';
  String get temporaryPrefix;

  List<LookupOption> get particulars => isPaymentReceipt
      ? lookupController.paymentParticulars
      : lookupController.adjustmentParticulars;

  List<LookupOption> get cashBankAccounts => lookupController.cashBankAccounts;

  List<LookupOption> availablePaymentParticularsFor(PaymentVoucherRowModel row) {
    final selectedCashBankKey = selectedCashBankAccount.value?.valueKey;
    final usedKeys = paymentRows
        .where((item) => item != row)
        .map((item) => item.account.value?.valueKey)
        .whereType<String>()
        .where((item) => item.isNotEmpty)
        .toSet();

    return particulars.where((item) {
      if (selectedCashBankKey != null && item.valueKey == selectedCashBankKey) {
        return false;
      }
      return !usedKeys.contains(item.valueKey);
    }).toList();
  }

  double get paymentTotal =>
      paymentRows.fold<double>(0, (sum, row) => sum + row.amount);
  double? get selectedCashBankAvailableBalance =>
      selectedCashBankAccount.value?.availableBalance;
  bool get isOverdraftCashBankAccount => selectedCashBankAvailableBalance == null;
  bool get isPaymentExceedingAvailableBalance {
    final available = selectedCashBankAvailableBalance;
    if (!isPaymentReceipt || voucherType != 'payment' || available == null) {
      return false;
    }
    return paymentTotal > available + 0.009;
  }
  String get paymentBalanceHint {
    if (voucherType != 'payment') {
      return '';
    }

    final account = selectedCashBankAccount.value;
    if (account == null) {
      return '';
    }

    final available = account.availableBalance;
    if (available == null) {
      return 'Overdraft account - no balance limit.';
    }

    final amount = available.toStringAsFixed(2);
    if (paymentTotal > available + 0.009) {
      return 'Available balance: Rs $amount - payment exceeds available balance';
    }
    return 'Available balance: Rs $amount';
  }
  double get totalDebit => adjustmentRows.fold<double>(
    0,
    (sum, row) => sum + (row.entryType.value == 'debit' ? row.amount : 0),
  );
  double get totalCredit => adjustmentRows.fold<double>(
    0,
    (sum, row) => sum + (row.entryType.value == 'credit' ? row.amount : 0),
  );

  @override
  void onInit() {
    super.onInit();
    _initializeForm();
  }

  Future<void> _initializeForm() async {
    final now = DateTime.now();
    dateController.text = AppDateFormatter.formatDisplay(now);
    await _loadLookups();
    if (initialPayload != null) {
      _applyInitialPayload(initialPayload!);
    } else if (isPaymentReceipt) {
      paymentRows.add(PaymentVoucherRowModel());
    } else {
      adjustmentRows.addAll(<AdjustmentVoucherRowModel>[
        AdjustmentVoucherRowModel(entryType: 'debit'),
        AdjustmentVoucherRowModel(entryType: 'credit'),
      ]);
    }
    update();
  }

  @override
  void onClose() {
    dateController.dispose();
    narrationController.dispose();
    referenceController.dispose();
    for (final row in paymentRows) {
      row.dispose();
    }
    for (final row in adjustmentRows) {
      row.dispose();
    }
    super.onClose();
  }

  Future<void> _loadLookups() {
    return lookupController.loadVoucherLookups(
      voucherType: voucherType,
    );
  }

  void _applyInitialPayload(Map<String, dynamic> payload) {
    _editingPayload = Map<String, dynamic>.from(payload);
    dateController.text = _shortDate(payload['voucher_date']?.toString());
    narrationController.text = (payload['narration'] ?? '').toString();
    referenceController.text = (payload['reference_number'] ?? '').toString();

    for (final row in paymentRows) {
      row.dispose();
    }
    for (final row in adjustmentRows) {
      row.dispose();
    }
    paymentRows.clear();
    adjustmentRows.clear();

    final rawLines = payload['lines'];
    final lines = rawLines is List
        ? rawLines
              .whereType<Map>()
              .map((item) => Map<String, dynamic>.from(item))
              .toList()
        : <Map<String, dynamic>>[];

    if (isPaymentReceipt) {
      LookupOption? cashBank;
      for (final line in lines) {
        final accountId = _lookupInt(line['account_id']);
        final matchedCashBank = _matchLookup(cashBankAccounts, accountId);
        if (matchedCashBank != null) {
          cashBank = matchedCashBank;
          continue;
        }

        final amount = _lookupDouble(line['debit']) > 0
            ? _lookupDouble(line['debit'])
            : _lookupDouble(line['credit']);
        paymentRows.add(
          PaymentVoucherRowModel(
            account: _matchLookup(particulars, accountId),
            amount: amount > 0 ? amount.toStringAsFixed(2) : '',
            description: (line['description'] ?? '').toString(),
          ),
        );
      }

      selectedCashBankAccount.value = cashBank;
      if (paymentRows.isEmpty) {
        paymentRows.add(PaymentVoucherRowModel());
      }

      final rawSettlements =
          payload['settlements'] ?? payload['invoice_settlements'];
      if (rawSettlements is List) {
        final allocations = <Map<String, dynamic>>[];
        for (final item in rawSettlements.whereType<Map>()) {
          final invoiceId = _lookupInt(
            item['invoice_id'] ??
                item['sales_invoice_id'] ??
                item['purchase_invoice_id'],
          );
          final amount =
              _lookupDouble(item['amount'] ?? item['amount_allocated']);
          if (invoiceId == null || amount <= 0) continue;
          allocations.add(<String, dynamic>{
            'invoice_id': invoiceId,
            'invoice_number': item['invoice_number'] ?? item['number'],
            'amount': amount,
            if ((item['reference_number'] ?? '').toString().isNotEmpty)
              'reference_number': item['reference_number'],
          });
        }
        // Hydrate against the first party particular row (web stores per-row).
        final partyRow = paymentRows.firstWhereOrNull((row) => row.isPartyParticular);
        if (partyRow != null && allocations.isNotEmpty) {
          partyRow.invoiceAllocations
            ..clear()
            ..addAll(allocations);
        }
      }
      paymentRows.refresh();
      return;
    }

    for (final line in lines) {
      final debit = _lookupDouble(line['debit']);
      final credit = _lookupDouble(line['credit']);
      adjustmentRows.add(
        AdjustmentVoucherRowModel(
          account: _matchLookup(
            lookupController.adjustmentParticulars,
            _lookupInt(line['account_id']),
          ),
          entryType: debit > 0 ? 'debit' : 'credit',
          amount: (debit > 0 ? debit : credit).toStringAsFixed(2),
          description: (line['description'] ?? '').toString(),
        ),
      );
    }

    if (adjustmentRows.length < 2) {
      adjustmentRows.addAll(<AdjustmentVoucherRowModel>[
        AdjustmentVoucherRowModel(entryType: 'debit'),
        AdjustmentVoucherRowModel(entryType: 'credit'),
      ]);
    }
    adjustmentRows.refresh();
  }

  Future<void> pickDate(BuildContext context) async {
    final current = AppDateFormatter.parse(dateController.text) ?? DateTime.now();
    final selected = await showDatePicker(
      context: context,
      initialDate: current,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      dateController.text = AppDateFormatter.formatDisplay(selected);
    }
  }

  void onCashBankAccountChanged(LookupOption? value) {
    selectedCashBankAccount.value = value;
    if (value == null) {
      paymentRows.refresh();
      update();
      return;
    }
    for (final row in paymentRows) {
      if (row.account.value?.valueKey == value.valueKey) {
        row.account.value = null;
      }
    }
    paymentRows.refresh();
    update();
  }

  void onPaymentParticularChanged(
    PaymentVoucherRowModel row,
    LookupOption? value,
  ) {
    if (value == null) {
      row.account.value = null;
      row.invoiceAllocations.clear();
      paymentRows.refresh();
      update();
      return;
    }

    if (selectedCashBankAccount.value?.valueKey == value.valueKey) {
      AppSnackbar.error('Particulars cannot be the same as the cash/bank account.');
      row.account.value = null;
      row.invoiceAllocations.clear();
      paymentRows.refresh();
      update();
      return;
    }

    final duplicate = paymentRows.any(
      (item) => item != row && item.account.value?.valueKey == value.valueKey,
    );
    if (duplicate) {
      AppSnackbar.error('This particular is already selected. Combine the amount in the same row.');
      row.account.value = null;
      row.invoiceAllocations.clear();
      paymentRows.refresh();
      update();
      return;
    }

    final previousKey = row.account.value?.valueKey;
    row.account.value = value;
    if (previousKey != value.valueKey) {
      row.invoiceAllocations.clear();
    }
    paymentRows.refresh();
    update();
  }

  void addPaymentRow() {
    paymentRows.add(PaymentVoucherRowModel());
    paymentRows.refresh();
    update();
  }

  void refreshPaymentTotals() {
    paymentRows.refresh();
    update();
  }

  void removePaymentRow(PaymentVoucherRowModel row) {
    if (paymentRows.length == 1) {
      return;
    }
    paymentRows.remove(row);
    row.dispose();
    paymentRows.refresh();
    update();
  }

  void addAdjustmentRow() {
    adjustmentRows.add(AdjustmentVoucherRowModel(entryType: 'debit'));
    adjustmentRows.refresh();
    update();
  }

  void refreshAdjustmentTotals() {
    adjustmentRows.refresh();
    update();
  }

  void removeAdjustmentRow(AdjustmentVoucherRowModel row) {
    if (adjustmentRows.length <= 2) {
      return;
    }
    adjustmentRows.remove(row);
    row.dispose();
    adjustmentRows.refresh();
    update();
  }

  Future<void> submit() async {
    FocusManager.instance.primaryFocus?.unfocus();
    if (!formKey.currentState!.validate()) {
      return;
    }
    if (isPaymentReceipt) {
      if (selectedCashBankAccount.value == null) {
        AppSnackbar.error('Please select a cash/bank account.');
        return;
      }
      final validRows = paymentRows
          .where((row) => row.account.value != null && row.amount > 0)
          .toList();
      if (validRows.isEmpty) {
        AppSnackbar.error('Please add at least one particulars row.');
        return;
      }
    } else {
      final validRows = adjustmentRows
          .where((row) => row.account.value != null && row.amount > 0)
          .toList();
      if (validRows.length < 2) {
        AppSnackbar.error('Adjustment requires at least 2 valid lines.');
        return;
      }
      if ((totalDebit - totalCredit).abs() > 0.01) {
        AppSnackbar.error('Total debit and credit must be equal.');
        return;
      }
    }

    final shouldContinue = await _confirmVoucherReview();
    if (!shouldContinue) {
      return;
    }

    isSubmitting.value = true;
    try {
      final payload = isPaymentReceipt ? _buildPaymentPayload() : _buildAdjustmentPayload();
      if (isEditing) {
        final recordId = _lookupInt(_editingPayload?['id']);
        final localId = recordId == null ? null : 'remote-$module-$recordId';
        if (recordId == null || localId == null) {
          throw Exception('Unable to edit this voucher right now.');
        }
        await repository.updateRecord(
          module: module,
          endpoint: '$endpoint/$recordId',
          localId: localId,
          serverId: recordId.toString(),
          payload: payload,
        );
      } else {
        await repository.createRecord(
          module: module,
          endpoint: endpoint,
          payload: payload,
        );
      }
      Get.back<void>();
      AppSnackbar.success(
        isEditing
            ? '$title was updated locally and will sync when available.'
            : '$title was saved locally and will sync when available.',
      );
      unawaited(_refreshList());
    } catch (error) {
      AppSnackbar.error(error.toString());
    } finally {
      isSubmitting.value = false;
    }
  }

  Future<bool> _confirmVoucherReview() async {
    final result = await Get.dialog<bool>(
      _VoucherReviewDialog(controller: this),
      barrierDismissible: true,
    );
    return result ?? false;
  }

  Map<String, dynamic> _buildPaymentPayload() {
    final validRows = paymentRows
        .where((row) => row.account.value != null && row.amount > 0)
        .toList();
    final recordId = _lookupInt(_editingPayload?['id']);
    LookupOption? partyToken;
    for (final row in validRows) {
      final account = row.account.value;
      if (account != null &&
          (account.kind == 'party' || account.valueKey.startsWith('party:'))) {
        partyToken = account;
        break;
      }
    }

    return <String, dynamic>{
      if (recordId != null) 'id': recordId,
      'voucher_type': voucherType,
      'voucher_date': AppDateFormatter.toApiDate(dateController.text.trim()),
      'payment_mode':
          selectedCashBankAccount.value?.transactionMode?.trim().isNotEmpty == true
              ? selectedCashBankAccount.value!.transactionMode!.trim()
              : null,
      'cash_bank_account_id': selectedCashBankAccount.value?.id,
      'narration': narrationController.text.trim().isEmpty
          ? null
          : narrationController.text.trim(),
      'reference_number': referenceController.text.trim().isEmpty
          ? null
          : referenceController.text.trim(),
      'payment_rows': validRows.map((row) {
        final account = row.account.value;
        final rowPayload = <String, dynamic>{
          'account_id': account?.valueKey,
          'amount': row.amount,
          'description': row.descriptionController.text.trim().isEmpty
              ? null
              : row.descriptionController.text.trim(),
        };
        if (row.isPartyParticular && row.invoiceAllocations.isNotEmpty) {
          rowPayload['invoice_allocations'] = row.invoiceAllocations
              .map(
                (item) => <String, dynamic>{
                  'invoice_id': item['invoice_id'],
                  'amount': item['amount'],
                  if ((item['reference_number'] ?? '').toString().isNotEmpty)
                    'reference_number': item['reference_number'],
                },
              )
              .toList();
        }
        return rowPayload;
      }).toList(),
      'voucher_number': '$temporaryPrefix-${DateTime.now().millisecondsSinceEpoch}',
      'status': 'draft',
      'type_label': title.replaceAll(' Voucher', ''),
      'total_debit': paymentTotal,
      'party': partyToken == null ? null : <String, dynamic>{'name': partyToken.label},
    };
  }

  Map<String, dynamic> _buildAdjustmentPayload() {
    final validRows = adjustmentRows
        .where((row) => row.account.value != null && row.amount > 0)
        .toList();
    final recordId = _lookupInt(_editingPayload?['id']);
    return <String, dynamic>{
      if (recordId != null) 'id': recordId,
      'voucher_type': 'journal',
      'voucher_date': AppDateFormatter.toApiDate(dateController.text.trim()),
      'narration': narrationController.text.trim().isEmpty
          ? null
          : narrationController.text.trim(),
      'adjustment_rows': validRows
          .map(
            (row) => <String, dynamic>{
              'account_id': row.account.value?.valueKey,
              'entry_type': row.entryType.value,
              'amount': row.amount,
              'description': row.descriptionController.text.trim().isEmpty
                  ? null
                  : row.descriptionController.text.trim(),
            },
          )
          .toList(),
      'voucher_number': '$temporaryPrefix-${DateTime.now().millisecondsSinceEpoch}',
      'status': 'draft',
      'type_label': 'Adjustment',
      'total_debit': totalDebit,
    };
  }

  Future<void> _refreshList() async {
    if (module == 'payments' && Get.isRegistered<PaymentsController>()) {
      await Get.find<PaymentsController>().refreshData();
    }
    if (module == 'receipts' && Get.isRegistered<ReceiptsController>()) {
      await Get.find<ReceiptsController>().refreshData();
    }
    if (module == 'adjustments' && Get.isRegistered<AdjustmentsController>()) {
      await Get.find<AdjustmentsController>().refreshData();
    }
    if (Get.isRegistered<AllVouchersController>()) {
      await Get.find<AllVouchersController>().refreshData();
    }
  }

  LookupOption? _matchLookup(List<LookupOption> source, int? id) {
    if (id == null) {
      return null;
    }
    final idText = id.toString();
    return source.firstWhereOrNull(
      (item) =>
          item.id == id ||
          item.rawId == idText ||
          item.valueKey == idText ||
          item.valueKey.endsWith(':$idText'),
    );
  }

  int? _lookupInt(dynamic value) {
    if (value == null) {
      return null;
    }
    if (value is int) {
      return value;
    }
    return int.tryParse(value.toString());
  }

  double _lookupDouble(dynamic value) {
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value?.toString() ?? '') ?? 0;
  }

  String _shortDate(String? value) {
    if (value == null || value.trim().isEmpty) {
      return '';
    }
    return AppDateFormatter.formatDisplay(value);
  }
}

class _VoucherReviewDialog extends StatelessWidget {
  const _VoucherReviewDialog({required this.controller});

  final BaseVoucherFormController controller;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final isEditing = controller.isEditing;

    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 24),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxHeight: 560),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Row(
                children: <Widget>[
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: scheme.primary.withValues(alpha: .12),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(
                      Icons.fact_check_outlined,
                      color: scheme.primary,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Text(
                          isEditing
                              ? 'Review voucher update'
                              : 'Confirm voucher details',
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          'Please verify the values before ${isEditing ? 'updating' : 'creating'} this voucher.',
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: scheme.onSurfaceVariant,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Expanded(
                child: SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      _VoucherReviewSection(
                        title: 'Voucher Details',
                        rows: <_VoucherReviewItem>[
                          _VoucherReviewItem(
                            label: 'Type',
                            value: controller.title.replaceAll(' Voucher', ''),
                          ),
                          _VoucherReviewItem(
                            label: 'Date',
                            value: controller.dateController.text.trim(),
                          ),
                          if (controller.isPaymentReceipt)
                            _VoucherReviewItem(
                              label: controller.cashBankLabel,
                              value: controller.selectedCashBankAccount.value?.label ??
                                  '-',
                            ),
                          _VoucherReviewItem(
                            label: 'Narration',
                            value: controller.narrationController.text.trim().isEmpty
                                ? '-'
                                : controller.narrationController.text.trim(),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      _VoucherReviewSection(
                        title: controller.isPaymentReceipt
                            ? 'Particulars Summary'
                            : 'Voucher Lines Summary',
                        child: Column(
                          children: controller.isPaymentReceipt
                              ? _buildPaymentRows(theme, scheme)
                              : _buildAdjustmentRows(theme, scheme),
                        ),
                      ),
                      const SizedBox(height: 12),
                      _VoucherReviewSection(
                        title: 'Totals',
                        rows: controller.isPaymentReceipt
                            ? <_VoucherReviewItem>[
                                _VoucherReviewItem(
                                  label: 'Total Amount',
                                  value:
                                      'Rs ${controller.paymentTotal.toStringAsFixed(2)}',
                                ),
                              ]
                            : <_VoucherReviewItem>[
                                _VoucherReviewItem(
                                  label: 'Total Debit',
                                  value:
                                      'Rs ${controller.totalDebit.toStringAsFixed(2)}',
                                ),
                                _VoucherReviewItem(
                                  label: 'Total Credit',
                                  value:
                                      'Rs ${controller.totalCredit.toStringAsFixed(2)}',
                                ),
                              ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: <Widget>[
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () => Get.back(result: false),
                      child: const Text('Review Again'),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: FilledButton(
                      onPressed: () => Get.back(result: true),
                      child: Text(
                        isEditing ? 'Update Voucher' : 'Create Voucher',
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  List<Widget> _buildPaymentRows(ThemeData theme, ColorScheme scheme) {
    final rows = controller.paymentRows
        .where((row) => row.account.value != null && row.amount > 0)
        .toList();

    return List<Widget>.generate(rows.length, (index) {
      final row = rows[index];
      final description = row.descriptionController.text.trim();
      return _VoucherReviewLineCard(
        index: index + 1,
        title: row.account.value?.label ?? '-',
        amount: 'Rs ${row.amount.toStringAsFixed(2)}',
        typeLabel: 'Amount',
        note: description.isEmpty ? null : description,
        accentColor: scheme.primary,
      );
    });
  }

  List<Widget> _buildAdjustmentRows(ThemeData theme, ColorScheme scheme) {
    final rows = controller.adjustmentRows
        .where((row) => row.account.value != null && row.amount > 0)
        .toList();

    return List<Widget>.generate(rows.length, (index) {
      final row = rows[index];
      final description = row.descriptionController.text.trim();
      final entryType = row.entryType.value == 'credit' ? 'Credit' : 'Debit';
      final accentColor = row.entryType.value == 'credit'
          ? const Color(0xFFF29B38)
          : const Color(0xFF16A36A);
      return _VoucherReviewLineCard(
        index: index + 1,
        title: row.account.value?.label ?? '-',
        amount: 'Rs ${row.amount.toStringAsFixed(2)}',
        typeLabel: entryType,
        note: description.isEmpty ? null : description,
        accentColor: accentColor,
      );
    });
  }

}

class _VoucherReviewSection extends StatelessWidget {
  const _VoucherReviewSection({
    required this.title,
    this.rows = const <_VoucherReviewItem>[],
    this.child,
  });

  final String title;
  final List<_VoucherReviewItem> rows;
  final Widget? child;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: scheme.outlineVariant.withValues(alpha: .7)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            title,
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w800,
            ),
          ),
          if (rows.isNotEmpty) ...<Widget>[
            const SizedBox(height: 10),
            for (final row in rows) ...<Widget>[
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Expanded(
                    child: Text(
                      row.label,
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: scheme.onSurfaceVariant,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      row.value,
                      textAlign: TextAlign.right,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ],
              ),
              if (row != rows.last) const SizedBox(height: 9),
            ],
          ],
          if (child != null) ...<Widget>[
            const SizedBox(height: 10),
            child!,
          ],
        ],
      ),
    );
  }
}

class _VoucherReviewItem {
  const _VoucherReviewItem({
    required this.label,
    required this.value,
  });

  final String label;
  final String value;
}

class _VoucherReviewLineCard extends StatelessWidget {
  const _VoucherReviewLineCard({
    required this.index,
    required this.title,
    required this.amount,
    required this.typeLabel,
    required this.accentColor,
    this.note,
  });

  final int index;
  final String title;
  final String amount;
  final String typeLabel;
  final String? note;
  final Color accentColor;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        color: accentColor.withValues(alpha: .06),
        border: Border.all(color: accentColor.withValues(alpha: .2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              Container(
                width: 24,
                height: 24,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: accentColor.withValues(alpha: .14),
                  shape: BoxShape.circle,
                ),
                child: Text(
                  '$index',
                  style: theme.textTheme.labelSmall?.copyWith(
                    color: accentColor,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  title,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: <Widget>[
              Text(
                typeLabel,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: accentColor,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const Spacer(),
              Text(
                amount,
                style: theme.textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
          if (note != null && note!.isNotEmpty) ...<Widget>[
            const SizedBox(height: 6),
            Text(
              note!,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
                height: 1.35,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class PaymentVoucherFormController extends BaseVoucherFormController {
  PaymentVoucherFormController(
    super.repository,
    super.lookupController, {
    super.initialPayload,
  });

  @override
  String get title => 'Payment Voucher';

  @override
  String get voucherType => 'payment';

  @override
  String get module => 'payments';

  @override
  String get endpoint => ApiEndpoints.payments;

  @override
  String get temporaryPrefix => 'PAY';
}

class ReceiptVoucherFormController extends BaseVoucherFormController {
  ReceiptVoucherFormController(
    super.repository,
    super.lookupController, {
    super.initialPayload,
  });

  @override
  String get title => 'Receipt Voucher';

  @override
  String get voucherType => 'receipt';

  @override
  String get module => 'receipts';

  @override
  String get endpoint => ApiEndpoints.receipts;

  @override
  String get temporaryPrefix => 'REC';
}

class AdjustmentVoucherFormController extends BaseVoucherFormController {
  AdjustmentVoucherFormController(
    super.repository,
    super.lookupController, {
    super.initialPayload,
  });

  @override
  String get title => 'Adjustment Voucher';

  @override
  String get voucherType => 'adjustment';

  @override
  String get module => 'adjustments';

  @override
  String get endpoint => ApiEndpoints.adjustments;

  @override
  String get temporaryPrefix => 'ADJ';
}
