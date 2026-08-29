import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';

import '../../../../core/config/api_endpoints.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/utils/amount_formatter.dart';
import '../../../../core/utils/app_action_loader.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../widgets/common/custom_text_field.dart';
import '../../../widgets/common/app_help_dialog.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/transactions/create/base_voucher_form_controller.dart';
import '../../../controllers/transactions/create/transaction_form_models.dart';
import '../../masters/forms/account_form_sheet.dart';
import '../../masters/forms/party_form_sheet.dart';
import 'widgets/transaction_form_components.dart';

class VoucherFormScreen<T extends BaseVoucherFormController>
    extends GetView<T> {
  const VoucherFormScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          controller.isEditing ? 'Edit ${controller.title}' : controller.title,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      bottomNavigationBar: Obx(
        () => TransactionSubmitBar(
          text: controller.isEditing
              ? 'Update ${controller.title}'
              : 'Save ${controller.title}',
          isLoading: controller.isSubmitting.value,
          onPressed: controller.submit,
        ),
      ),
      body: Form(
        key: controller.formKey,
        child: GetBuilder<T>(
          builder: (_) => SingleChildScrollView(
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
                        Column(
                          children: <Widget>[
                            CustomDropdown<LookupOption>(
                              label: controller.cashBankLabel,
                              value: controller.selectedCashBankAccount.value,
                              items: controller.cashBankAccounts,
                              itemLabelBuilder: (item) => item.label,
                              onChanged: controller.onCashBankAccountChanged,
                              hint: 'Select Cash / Bank / OD',
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
                                  padding: const EdgeInsets.only(top: 6, left: 4,bottom: 10),
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
                if (controller.isPaymentReceipt) _BillWiseAllocateSection<T>(),
                if (controller.isAdjustment) _AdjustmentRowsSection<T>(),
              ],
            ),
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
    return TransactionFormSectionCard(
      title: 'Particulars',
      action: IconButton(
        onPressed: controller.addPaymentRow,
        icon: const Icon(Icons.add_circle_outline_rounded),
      ),
      child: Column(
        children: <Widget>[
          _VoucherInlineQuickActions<T>(label: 'Particulars'),
          const SizedBox(height: 4),
          for (final row in controller.paymentRows) _PaymentRowCard<T>(row: row),
          const SizedBox(height: 8),
          TransactionAmountPill(
            label: 'Total Amount',
            value: 'Rs ${formatAmount(controller.paymentTotal)}',
          ),
          const SizedBox(height: 10),
          _VoucherModeHelp<T>(),
        ],
      ),
    );
  }
}

class _BillWiseAllocateSection<T extends BaseVoucherFormController>
    extends GetView<T> {
  @override
  Widget build(BuildContext context) {
    return Obx(() {
      final party = _selectedParty(controller);
      if (party == null) {
        return const SizedBox.shrink();
      }
      final allocations = controller.invoiceAllocations;
      return TransactionFormSectionCard(
        title: 'Bill-wise Details',
        action: TextButton.icon(
          onPressed: () => _openAllocateSheet(context, party),
          icon: const Icon(Icons.link_rounded, size: 18),
          label: Text(allocations.isEmpty ? 'Allocate' : 'Edit'),
        ),
        child: allocations.isEmpty
            ? Text(
                'Optionally allocate this ${controller.voucherType} against outstanding invoices for ${party.label}.',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
              )
            : Column(
                children: allocations
                    .map(
                      (item) => ListTile(
                        contentPadding: EdgeInsets.zero,
                        dense: true,
                        title: Text(
                          (item['invoice_number'] ?? item['invoice_id']).toString(),
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                        trailing: Text(
                          AmountFormatter.currency(item['amount']),
                          style: const TextStyle(fontWeight: FontWeight.w800),
                        ),
                      ),
                    )
                    .toList(),
              ),
      );
    });
  }

  LookupOption? _selectedParty(BaseVoucherFormController controller) {
    for (final row in controller.paymentRows) {
      final account = row.account.value;
      if (account == null) continue;
      final kind = (account.kind ?? '').toLowerCase();
      final key = account.valueKey;
      if (kind == 'party' || key.startsWith('party:')) {
        return account;
      }
    }
    return null;
  }

  Future<void> _openAllocateSheet(
    BuildContext context,
    LookupOption party,
  ) async {
    int? partyId;
    if (party.valueKey.startsWith('party:')) {
      partyId = int.tryParse(party.valueKey.substring('party:'.length));
    }
    partyId ??= party.id;
    if (partyId <= 0) {
      AppSnackbar.error('Select a party particular first.');
      return;
    }

    final resolvedPartyId = partyId;
    List<Map<String, dynamic>> invoices = <Map<String, dynamic>>[];
    try {
      invoices = await AppActionLoader.run(
        () async {
          final response = await Get.find<ApiClient>().get<Map<String, dynamic>>(
            ApiEndpoints.partyOutstandingInvoices(resolvedPartyId),
            queryParameters: <String, dynamic>{
              'invoice_type':
                  controller.voucherType == 'receipt' ? 'sales' : 'purchase',
            },
          );
          final data = response.data?['data'];
          final list = <Map<String, dynamic>>[];
          if (data is List) {
            for (final item in data.whereType<Map>()) {
              list.add(Map<String, dynamic>.from(item));
            }
          }
          return list;
        },
        message: 'Loading invoices...',
      );
    } catch (_) {
      AppSnackbar.error('Unable to load outstanding invoices.');
      return;
    }

    if (invoices.isEmpty) {
      AppSnackbar.error('No outstanding invoices for this party.');
      return;
    }

    final controllers = <int, TextEditingController>{};
    for (final invoice in invoices) {
      final id = int.tryParse(invoice['id']?.toString() ?? '') ?? 0;
      if (id <= 0) continue;
      final existing = controller.invoiceAllocations.firstWhereOrNull(
        (item) => item['invoice_id'] == id,
      );
      controllers[id] = TextEditingController(
        text: existing?['amount']?.toString() ?? '',
      );
    }

    await Get.bottomSheet<void>(
      SafeArea(
        child: Material(
          color: Theme.of(context).cardColor,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  'Allocate to invoices',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                ),
                const SizedBox(height: 10),
                ConstrainedBox(
                  constraints: BoxConstraints(
                    maxHeight: MediaQuery.of(context).size.height * 0.5,
                  ),
                  child: ListView(
                    shrinkWrap: true,
                    children: invoices.map((invoice) {
                      final id =
                          int.tryParse(invoice['id']?.toString() ?? '') ?? 0;
                      final balance = double.tryParse(
                            invoice['balance_due']?.toString() ?? '0',
                          ) ??
                          0;
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: <Widget>[
                            Text(
                              (invoice['invoice_number'] ?? '-').toString(),
                              style: const TextStyle(fontWeight: FontWeight.w700),
                            ),
                            Text(
                              'Balance ${AmountFormatter.currency(balance)}',
                              style: Theme.of(context).textTheme.labelMedium,
                            ),
                            CustomTextField(
                              label: 'Allocate',
                              controller: controllers[id],
                              keyboardType:
                                  const TextInputType.numberWithOptions(
                                decimal: true,
                              ),
                              bottomPadding: 0,
                            ),
                          ],
                        ),
                      );
                    }).toList(),
                  ),
                ),
                const SizedBox(height: 10),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                    onPressed: () {
                      final next = <Map<String, dynamic>>[];
                      for (final invoice in invoices) {
                        final id =
                            int.tryParse(invoice['id']?.toString() ?? '') ?? 0;
                        final amount = double.tryParse(
                              controllers[id]?.text.trim() ?? '',
                            ) ??
                            0;
                        if (id <= 0 || amount <= 0) continue;
                        next.add(<String, dynamic>{
                          'invoice_id': id,
                          'invoice_number': invoice['invoice_number'],
                          'amount': amount,
                        });
                      }
                      controller.invoiceAllocations.assignAll(next);
                      Get.back<void>();
                    },
                    child: const Text('Save Allocations'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
      isScrollControlled: true,
    );

    for (final c in controllers.values) {
      c.dispose();
    }
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
                hint: 'Select Particulars',
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

class _VoucherInlineQuickActions<T extends BaseVoucherFormController>
    extends GetView<T> {
  const _VoucherInlineQuickActions({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            '$label *',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 4),
          Align(
            alignment: Alignment.centerRight,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: <Widget>[
                _VoucherQuickActionLink(
                  label: 'Quick Add Cash / Bank Ledger',
                  onTap: () => _openVoucherQuickAddLedger(context, controller),
                ),
                const SizedBox(height: 2),
                _VoucherQuickActionLink(
                  label: 'Quick Add Party',
                  onTap: () => _openVoucherQuickAddParty(context, controller),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _VoucherQuickActionLink extends StatelessWidget {
  const _VoucherQuickActionLink({
    required this.label,
    required this.onTap,
  });

  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(6),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 2, vertical: 1),
        child: Text(
          label,
          textAlign: TextAlign.right,
          style: theme.textTheme.labelMedium?.copyWith(
            color: theme.colorScheme.primary,
            fontWeight: FontWeight.w700,
            decoration: TextDecoration.underline,
            decorationColor: theme.colorScheme.primary,
          ),
        ),
      ),
    );
  }
}

class _AdjustmentRowsSection<T extends BaseVoucherFormController>
    extends GetView<T> {
  @override
  Widget build(BuildContext context) {
    final difference = (controller.totalDebit - controller.totalCredit).abs();
    return TransactionFormSectionCard(
        title: 'Voucher Lines',
        action: IconButton(
          onPressed: controller.addAdjustmentRow,
          icon: const Icon(Icons.add_circle_outline_rounded),
        ),
        child: Column(
          children: <Widget>[
            _VoucherInlineQuickActions<T>(label: 'Particulars (Party / Ledger)'),
            const SizedBox(height: 4),
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
            const _AdjustmentVoucherHelp(),
          ],
        ),
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
                enableSearch: false,
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

class _AdjustmentVoucherHelp extends StatelessWidget {
  const _AdjustmentVoucherHelp();

  @override
  Widget build(BuildContext context) {
    return const Align(
      alignment: Alignment.centerLeft,
      child: AppHelpDialogButton(
        title: 'Journal Voucher Help',
        tooltip: 'Journal voucher help',
        label: 'Journal voucher help',
        sections: <AppHelpDialogSection>[
          AppHelpDialogSection(
            title: 'Journal flow',
            message:
                'Add debit and credit lines in Tally style. Total Debit must equal Total Credit and entries auto-post to ledger and journal.',
          ),
        ],
      ),
    );
  }
}

Future<void> _openVoucherQuickAddParty(
  BuildContext context,
  BaseVoucherFormController controller,
) async {
  final result = await Get.to(
    () => PartyFormSheet(
      initialType: controller.voucherType == 'payment' ? 'creditor' : 'debtor',
    ),
  );
  if (result == null) {
    return;
  }
  await controller.lookupController.loadVoucherLookups(
    voucherType: controller.voucherType,
  );
  controller.update();
}

Future<void> _openVoucherQuickAddLedger(
  BuildContext context,
  BaseVoucherFormController controller,
) async {
  final result = await Get.to(() => const AccountFormSheet());
  if (result == null) {
    return;
  }
  await controller.lookupController.loadVoucherLookups(
    voucherType: controller.voucherType,
  );
  controller.update();
}

class _VoucherModeHelp<T extends BaseVoucherFormController> extends GetView<T> {
  @override
  Widget build(BuildContext context) {
    final isReceipt = controller.voucherType == 'receipt';
    final note = isReceipt
        ? 'Receipt follows Tally style: Dr Cash or Bank, Cr Party. The entry auto-posts to ledger and journal.'
        : 'Payment follows Tally style: Dr Party, Cr Cash or Bank. The entry auto-posts to ledger and journal.';
    return Align(
      alignment: Alignment.centerLeft,
      child: AppHelpDialogButton(
        title: isReceipt ? 'Receipt Voucher Help' : 'Payment Voucher Help',
        tooltip: isReceipt ? 'Receipt voucher help' : 'Payment voucher help',
        label: isReceipt ? 'Receipt voucher help' : 'Payment voucher help',
        sections: <AppHelpDialogSection>[
          AppHelpDialogSection(
            title: isReceipt ? 'Receipt flow' : 'Payment flow',
            message: note,
          ),
        ],
      ),
    );
  }
}
