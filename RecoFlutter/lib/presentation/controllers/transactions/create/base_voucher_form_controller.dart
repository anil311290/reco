import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/config/api_endpoints.dart';
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
  final isSubmitting = false.obs;
  final paymentMode = ''.obs;
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
    dateController.text =
        '${now.year.toString().padLeft(4, '0')}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
    if (isPaymentReceipt && initialPayload != null) {
      final initialMode = initialPayload?['payment_mode']?.toString().trim() ?? '';
      if (initialMode.isNotEmpty) {
        paymentMode.value = initialMode;
      }
    }
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
      paymentMode: paymentMode.value.trim().isEmpty ? null : paymentMode.value,
    );
  }

  void _applyInitialPayload(Map<String, dynamic> payload) {
    _editingPayload = Map<String, dynamic>.from(payload);
    dateController.text = _shortDate(payload['voucher_date']?.toString());
    narrationController.text = (payload['narration'] ?? '').toString();

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
      final selectedMode = (payload['payment_mode'] ?? '').toString().trim();
      if (selectedMode.isNotEmpty) {
        paymentMode.value = selectedMode;
      }

      LookupOption? cashBank;
      for (final line in lines) {
        final accountId = _lookupInt(line['account_id']);
        final matchedCashBank = _matchLookup(cashBankAccounts, accountId);
        if (matchedCashBank != null) {
          cashBank = matchedCashBank;
          paymentMode.value =
              paymentMode.value.trim().isNotEmpty
                  ? paymentMode.value
                  : (matchedCashBank.transactionMode ?? '');
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
    final current = DateTime.tryParse(dateController.text) ?? DateTime.now();
    final selected = await showDatePicker(
      context: context,
      initialDate: current,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      dateController.text =
          '${selected.year.toString().padLeft(4, '0')}-${selected.month.toString().padLeft(2, '0')}-${selected.day.toString().padLeft(2, '0')}';
    }
  }

  Future<void> onPaymentModeChanged(String? value) async {
    if (value == null || value == paymentMode.value) {
      return;
    }
    paymentMode.value = value;
    selectedCashBankAccount.value = null;
    await lookupController.refreshCashBankAccounts(value);
    update();
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
      paymentRows.refresh();
      update();
      return;
    }

    if (selectedCashBankAccount.value?.valueKey == value.valueKey) {
      AppSnackbar.error('Particulars cannot be the same as the cash/bank account.');
      row.account.value = null;
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
      paymentRows.refresh();
      update();
      return;
    }

    row.account.value = value;
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
      if (paymentMode.value.trim().isEmpty) {
        AppSnackbar.error('Please select a payment mode.');
        return;
      }
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
      await _refreshList();
      Get.back<void>();
      AppSnackbar.success(
        isEditing
            ? '$title was updated locally and will sync when available.'
            : '$title was saved locally and will sync when available.',
      );
    } catch (error) {
      AppSnackbar.error(error.toString());
    } finally {
      isSubmitting.value = false;
    }
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
      if (recordId case final currentRecordId?) 'id': currentRecordId,
      'voucher_type': voucherType,
      'voucher_date': dateController.text.trim(),
      'payment_mode': paymentMode.value,
      'cash_bank_account_id': selectedCashBankAccount.value?.id,
      'narration': narrationController.text.trim().isEmpty
          ? null
          : narrationController.text.trim(),
      'payment_rows': validRows
          .map(
            (row) => <String, dynamic>{
              'account_id': row.account.value?.valueKey,
              'amount': row.amount,
              'description': row.descriptionController.text.trim().isEmpty
                  ? null
                  : row.descriptionController.text.trim(),
            },
          )
          .toList(),
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
      if (recordId case final currentRecordId?) 'id': currentRecordId,
      'voucher_type': 'journal',
      'voucher_date': dateController.text.trim(),
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
    return value.length >= 10 ? value.substring(0, 10) : value;
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
