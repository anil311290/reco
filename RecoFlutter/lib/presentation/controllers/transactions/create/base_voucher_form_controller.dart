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
  BaseVoucherFormController(this.repository, this.lookupController);

  final TransactionsRepository repository;
  final TransactionFormLookupController lookupController;

  final formKey = GlobalKey<FormState>();
  final dateController = TextEditingController();
  final narrationController = TextEditingController();
  final isSubmitting = false.obs;
  final paymentMode = ''.obs;
  final selectedCashBankAccount = Rxn<LookupOption>();
  final paymentRows = <PaymentVoucherRowModel>[].obs;
  final adjustmentRows = <AdjustmentVoucherRowModel>[].obs;

  String get title;
  String get voucherType;
  String get module;
  String get endpoint;

  bool get isPaymentReceipt =>
      voucherType == 'payment' || voucherType == 'receipt';
  bool get isAdjustment => !isPaymentReceipt;
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
    final now = DateTime.now();
    dateController.text =
        '${now.year.toString().padLeft(4, '0')}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
    if (isPaymentReceipt) {
      paymentRows.add(PaymentVoucherRowModel());
    } else {
      adjustmentRows.addAll(<AdjustmentVoucherRowModel>[
        AdjustmentVoucherRowModel(entryType: 'debit'),
        AdjustmentVoucherRowModel(entryType: 'credit'),
      ]);
    }
    _loadLookups();
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
  }

  void onCashBankAccountChanged(LookupOption? value) {
    selectedCashBankAccount.value = value;
    if (value == null) {
      paymentRows.refresh();
      return;
    }
    for (final row in paymentRows) {
      if (row.account.value?.valueKey == value.valueKey) {
        row.account.value = null;
      }
    }
    paymentRows.refresh();
  }

  void onPaymentParticularChanged(
    PaymentVoucherRowModel row,
    LookupOption? value,
  ) {
    if (value == null) {
      row.account.value = null;
      paymentRows.refresh();
      return;
    }

    if (selectedCashBankAccount.value?.valueKey == value.valueKey) {
      AppSnackbar.error('Particulars cash/bank account ke same nahi ho sakta.');
      row.account.value = null;
      paymentRows.refresh();
      return;
    }

    final duplicate = paymentRows.any(
      (item) => item != row && item.account.value?.valueKey == value.valueKey,
    );
    if (duplicate) {
      AppSnackbar.error('Ye particulars already selected hai. Same row me amount combine karein.');
      row.account.value = null;
      paymentRows.refresh();
      return;
    }

    row.account.value = value;
    paymentRows.refresh();
  }

  void addPaymentRow() => paymentRows.add(PaymentVoucherRowModel());

  void refreshPaymentTotals() => paymentRows.refresh();

  void removePaymentRow(PaymentVoucherRowModel row) {
    if (paymentRows.length == 1) {
      return;
    }
    paymentRows.remove(row);
    row.dispose();
  }

  void addAdjustmentRow() =>
      adjustmentRows.add(AdjustmentVoucherRowModel(entryType: 'debit'));

  void refreshAdjustmentTotals() => adjustmentRows.refresh();

  void removeAdjustmentRow(AdjustmentVoucherRowModel row) {
    if (adjustmentRows.length <= 2) {
      return;
    }
    adjustmentRows.remove(row);
    row.dispose();
  }

  Future<void> submit() async {
    FocusManager.instance.primaryFocus?.unfocus();
    if (!formKey.currentState!.validate()) {
      return;
    }
    if (isPaymentReceipt) {
      if (paymentMode.value.trim().isEmpty) {
        AppSnackbar.error('Payment mode select karein.');
        return;
      }
      if (selectedCashBankAccount.value == null) {
        AppSnackbar.error('Cash/Bank account select karein.');
        return;
      }
      final validRows = paymentRows
          .where((row) => row.account.value != null && row.amount > 0)
          .toList();
      if (validRows.isEmpty) {
        AppSnackbar.error('Kam se kam ek particulars row add karein.');
        return;
      }
    } else {
      final validRows = adjustmentRows
          .where((row) => row.account.value != null && row.amount > 0)
          .toList();
      if (validRows.length < 2) {
        AppSnackbar.error('Adjustment me kam se kam 2 valid lines chahiye.');
        return;
      }
      if ((totalDebit - totalCredit).abs() > 0.01) {
        AppSnackbar.error('Total debit aur credit same hona chahiye.');
        return;
      }
    }

    isSubmitting.value = true;
    try {
      final payload = isPaymentReceipt ? _buildPaymentPayload() : _buildAdjustmentPayload();
      await repository.createRecord(
        module: module,
        endpoint: endpoint,
        payload: payload,
      );
      await _refreshList();
      Get.back<void>();
      AppSnackbar.success('$title local me save ho gaya. Sync available hone par ho jayega.');
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
    return <String, dynamic>{
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
}

class PaymentVoucherFormController extends BaseVoucherFormController {
  PaymentVoucherFormController(super.repository, super.lookupController);

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
  ReceiptVoucherFormController(super.repository, super.lookupController);

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
  AdjustmentVoucherFormController(super.repository, super.lookupController);

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
