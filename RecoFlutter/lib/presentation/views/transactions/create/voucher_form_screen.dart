import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';

import '../../../widgets/common/custom_text_field.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/transactions/create/base_voucher_form_controller.dart';
import '../../../controllers/transactions/create/transaction_form_models.dart';
import 'widgets/transaction_form_components.dart';

class VoucherFormScreen<T extends BaseVoucherFormController>
    extends GetView<T> {
  const VoucherFormScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          controller.title,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      bottomNavigationBar: Obx(
        () => TransactionSubmitBar(
          text: 'Save ${controller.title}',
          isLoading: controller.isSubmitting.value,
          onPressed: controller.submit,
        ),
      ),
      body: Form(
        key: controller.formKey,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: <Widget>[
              TransactionFormSectionCard(
                title: 'Voucher Details',
                child: Column(
                  children: <Widget>[
                    CustomTextField(
                      label: 'Voucher Date',
                      controller: controller.dateController,
                      readOnly: true,
                      requiredField: true,
                      suffixIcon: Icons.calendar_today_outlined,
                      onSuffixTap: () => controller.pickDate(context),
                      onTap: () => controller.pickDate(context),
                      validator: (value) => (value == null || value.trim().isEmpty)
                          ? 'Voucher date required'
                          : null,
                    ),
                    if (controller.isPaymentReceipt)
                      Obx(
                        () => CustomDropdown<String>(
                          label: 'Payment Mode',
                          value: controller.paymentMode.value.isEmpty
                              ? null
                              : controller.paymentMode.value,
                          items: const <String>['cash', 'bank', 'od'],
                          itemLabelBuilder: (item) =>
                              item[0].toUpperCase() + item.substring(1),
                          onChanged: controller.onPaymentModeChanged,
                          hint: 'Select Mode',
                          requiredField: true,
                          isLoading: controller.lookupController.isLoading.value,
                          enabled: !controller.lookupController.isLoading.value,
                        ),
                      ),
                    if (controller.isPaymentReceipt)
                      Obx(
                        () => Column(
                          children: <Widget>[
                            CustomDropdown<LookupOption>(
                              label: controller.cashBankLabel,
                              value: controller.selectedCashBankAccount.value,
                              items: controller.cashBankAccounts,
                              itemLabelBuilder: (item) => item.label,
                              onChanged: controller.onCashBankAccountChanged,
                              requiredField: true,
                              isLoading: controller
                                  .lookupController.isCashBankAccountsLoading.value,
                              enabled: !controller
                                  .lookupController.isCashBankAccountsLoading.value,
                            ),
                            if (controller.voucherType == 'payment' &&
                                controller.paymentBalanceHint.isNotEmpty)
                              Align(
                                alignment: Alignment.centerLeft,
                                child: Padding(
                                  padding: const EdgeInsets.only(top: 6, left: 4),
                                  child: Text(
                                    controller.paymentBalanceHint,
                                    style: Theme.of(context).textTheme.bodySmall
                                        ?.copyWith(
                                          color: controller
                                                  .isPaymentExceedingAvailableBalance
                                              ? Theme.of(
                                                  context,
                                                ).colorScheme.error
                                              : Theme.of(context)
                                                  .colorScheme
                                                  .onSurfaceVariant,
                                          fontWeight: FontWeight.w500,
                                        ),
                                  ),
                                ),
                              ),
                          ],
                        ),
                      ),
                    CustomTextField(
                      label: 'Narration',
                      controller: controller.narrationController,
                      maxLines: 3,
                      hintText: 'Brief description',
                    ),
                  ],
                ),
              ),
              if (controller.isPaymentReceipt) _PaymentRowsSection<T>(),
              if (controller.isAdjustment) _AdjustmentRowsSection<T>(),
            ],
          ),
        ),
      ),
    );
  }
}

class _PaymentRowsSection<T extends BaseVoucherFormController>
    extends GetView<T> {
  @override
  Widget build(BuildContext context) {
    return Obx(
      () => TransactionFormSectionCard(
        title: 'Particulars',
        action: IconButton(
          onPressed: controller.addPaymentRow,
          icon: const Icon(Icons.add_circle_outline_rounded),
        ),
        child: Column(
          children: <Widget>[
            for (final row in controller.paymentRows) _PaymentRowCard<T>(row: row),
            const SizedBox(height: 8),
            TransactionAmountPill(
              label: 'Total Amount',
              value: 'Rs ${formatAmount(controller.paymentTotal)}',
            ),
            const SizedBox(height: 10),
            _VoucherModeNote<T>(),
          ],
        ),
      ),
    );
  }
}

class _PaymentRowCard<T extends BaseVoucherFormController> extends GetView<T> {
  const _PaymentRowCard({required this.row});

  final PaymentVoucherRowModel row;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Theme.of(context).dividerColor.withValues(alpha: .45),
        ),
      ),
      child: Column(
        children: <Widget>[
          ValueListenableBuilder<LookupOption?>(
            valueListenable: row.account,
            builder: (context, value, _) {
              return CustomDropdown<LookupOption>(
                label: 'Particulars',
                value: value,
                items: controller.availablePaymentParticularsFor(row),
                itemLabelBuilder: (item) {
                  final group = item.group?.trim();
                  if (group == null || group.isEmpty) {
                    return item.label;
                  }
                  return '[$group] ${item.label}';
                },
                onChanged: (next) =>
                    controller.onPaymentParticularChanged(row, next),
                requiredField: true,
                isLoading: controller
                    .lookupController.isPaymentParticularsLoading.value,
                enabled: !controller
                    .lookupController.isPaymentParticularsLoading.value,
              );
            },
          ),
          CustomTextField(
            label: 'Amount',
            controller: row.amountController,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            inputFormatters: <TextInputFormatter>[
              FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
            ],
            requiredField: true,
            onChanged: (_) => controller.refreshPaymentTotals(),
          ),
          CustomTextField(
            label: 'Description',
            controller: row.descriptionController,
            hintText: 'Optional description',
          ),
          if (controller.paymentRows.length > 1)
            Align(
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                onPressed: () => controller.removePaymentRow(row),
                icon: const Icon(Icons.delete_outline_rounded),
                label: const Text('Remove'),
              ),
            ),
        ],
      ),
    );
  }
}

class _AdjustmentRowsSection<T extends BaseVoucherFormController>
    extends GetView<T> {
  @override
  Widget build(BuildContext context) {
    return Obx(
      () {
        final difference = (controller.totalDebit - controller.totalCredit).abs();
        return TransactionFormSectionCard(
        title: 'Voucher Lines',
        action: IconButton(
          onPressed: controller.addAdjustmentRow,
          icon: const Icon(Icons.add_circle_outline_rounded),
        ),
        child: Column(
          children: <Widget>[
            for (final row in controller.adjustmentRows)
              _AdjustmentRowCard<T>(row: row),
            const SizedBox(height: 8),
            TransactionAmountPill(
              label: 'Total Debit',
              value: 'Rs ${formatAmount(controller.totalDebit)}',
              valueColor: const Color(0xFF16A36A),
            ),
            const SizedBox(height: 10),
            TransactionAmountPill(
              label: 'Total Credit',
              value: 'Rs ${formatAmount(controller.totalCredit)}',
              valueColor: const Color(0xFFF29B38),
            ),
            const SizedBox(height: 10),
            TransactionAmountPill(
              label: 'Difference',
              value: 'Rs ${formatAmount(difference)}',
              valueColor: difference <= 0.009
                  ? const Color(0xFF16A36A)
                  : Theme.of(context).colorScheme.error,
            ),
            const SizedBox(height: 10),
            _AdjustmentVoucherNote<T>(),
          ],
        ),
      );
      },
    );
  }
}

class _AdjustmentRowCard<T extends BaseVoucherFormController>
    extends GetView<T> {
  const _AdjustmentRowCard({required this.row});

  final AdjustmentVoucherRowModel row;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Theme.of(context).dividerColor.withValues(alpha: .45),
        ),
      ),
      child: Column(
        children: <Widget>[
          ValueListenableBuilder<LookupOption?>(
            valueListenable: row.account,
            builder: (context, value, _) {
              return CustomDropdown<LookupOption>(
                label: 'Particulars (Party / Ledger)',
                value: value,
                items: controller.particulars,
                itemLabelBuilder: (item) {
                  final group = item.group?.trim();
                  if (group == null || group.isEmpty) {
                    return item.label;
                  }
                  return '[$group] ${item.label}';
                },
                onChanged: (next) => row.account.value = next,
                hint: 'Select Party / Ledger',
                requiredField: true,
                isLoading: controller
                    .lookupController.isAdjustmentParticularsLoading.value,
                enabled: !controller
                    .lookupController.isAdjustmentParticularsLoading.value,
              );
            },
          ),
          ValueListenableBuilder<String>(
            valueListenable: row.entryType,
            builder: (context, value, _) {
              return CustomDropdown<String>(
                label: 'Dr / Cr',
                value: value,
                items: const <String>['debit', 'credit'],
                itemLabelBuilder: (item) =>
                    item[0].toUpperCase() + item.substring(1),
                onChanged: (next) {
                  if (next != null) {
                    row.entryType.value = next;
                    controller.refreshAdjustmentTotals();
                  }
                },
                requiredField: true,
              );
            },
          ),
          CustomTextField(
            label: 'Amount',
            controller: row.amountController,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            inputFormatters: <TextInputFormatter>[
              FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
            ],
            requiredField: true,
            onChanged: (_) => controller.refreshAdjustmentTotals(),
          ),
          if (controller.adjustmentRows.length > 2)
            Align(
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                onPressed: () => controller.removeAdjustmentRow(row),
                icon: const Icon(Icons.delete_outline_rounded),
                label: const Text('Remove'),
              ),
            ),
        ],
      ),
    );
  }
}

class _AdjustmentVoucherNote<T extends BaseVoucherFormController>
    extends GetView<T> {
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: theme.colorScheme.primary.withValues(alpha: .06),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: theme.colorScheme.primary.withValues(alpha: .10),
        ),
      ),
      child: Text(
        'Journal (Tally style): add debit and credit lines. Total Debit must equal Total Credit - auto-posted to ledger & journal.',
        style: theme.textTheme.bodySmall?.copyWith(
          color: theme.colorScheme.onSurfaceVariant,
          fontWeight: FontWeight.w500,
          height: 1.35,
        ),
      ),
    );
  }
}

class _VoucherModeNote<T extends BaseVoucherFormController> extends GetView<T> {
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isReceipt = controller.voucherType == 'receipt';
    final note = isReceipt
        ? 'Receipt (Tally style): Dr Cash/Bank, Cr Party - auto-posted to ledger & journal.'
        : 'Payment (Tally style): Dr Party, Cr Cash/Bank - auto-posted to ledger & journal.';
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: theme.colorScheme.primary.withValues(alpha: .06),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: theme.colorScheme.primary.withValues(alpha: .10),
        ),
      ),
      child: Text(
        note,
        style: theme.textTheme.bodySmall?.copyWith(
          color: theme.colorScheme.onSurfaceVariant,
          fontWeight: FontWeight.w500,
          height: 1.35,
        ),
      ),
    );
  }
}
