import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/config/api_endpoints.dart';
import '../../../../core/utils/app_action_loader.dart';
import '../../../../core/utils/app_alert_dialog.dart';
import '../../../../core/utils/app_date_formatter.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../../data/models/transactions/transaction_entities.dart';
import '../../../../data/repositories/accounts/accounts_repository.dart';
import '../../../../data/repositories/masters/items_repository.dart';
import '../../../../data/repositories/masters/parties_repository.dart';
import '../../../../data/repositories/masters/tax_rates_repository.dart';
import '../../../../data/repositories/transactions/transactions_repository.dart';
import '../../../controllers/transactions/base_transactions_tab_controller.dart';
import '../../../controllers/transactions/create/base_invoice_form_controller.dart';
import '../../../controllers/transactions/create/base_voucher_form_controller.dart';
import '../../../controllers/transactions/create/transaction_form_lookup_controller.dart';
import '../../../widgets/common/custom_text_field.dart';
import '../create/adjustment_voucher_screen.dart';
import '../create/payment_voucher_screen.dart';
import '../create/purchase_invoice_screen.dart';
import '../create/receipt_voucher_screen.dart';
import '../create/sales_invoice_screen.dart';

Future<void> openInvoiceEditor(TransactionRecord record) async {
  final payload = await _resolveInvoicePayload(record);
  if (payload == null) {
    AppSnackbar.error('Invoice details are not available for editing.');
    return;
  }

  switch (record.kind) {
    case TransactionRecordKind.salesInvoice:
      Get.to(
        () => const SalesInvoiceScreen(),
        binding: BindingsBuilder(() {
          final lookup = Get.put(
            TransactionFormLookupController(
              Get.find<PartiesRepository>(),
              Get.find<AccountsRepository>(),
              Get.find<ItemsRepository>(),
              Get.find<TaxRatesRepository>(),
            ),
          );
          Get.put(
            SalesInvoiceFormController(
              Get.find<TransactionsRepository>(),
              lookup,
              initialPayload: payload,
            ),
          );
        }),
      );
      return;
    case TransactionRecordKind.purchaseInvoice:
      Get.to(
        () => const PurchaseInvoiceScreen(),
        binding: BindingsBuilder(() {
          final lookup = Get.put(
            TransactionFormLookupController(
              Get.find<PartiesRepository>(),
              Get.find<AccountsRepository>(),
              Get.find<ItemsRepository>(),
              Get.find<TaxRatesRepository>(),
            ),
          );
          Get.put(
            PurchaseInvoiceFormController(
              Get.find<TransactionsRepository>(),
              lookup,
              initialPayload: payload,
            ),
          );
        }),
      );
      return;
    case TransactionRecordKind.voucher:
      AppSnackbar.error('Edit is not available for this record.');
      return;
  }
}

Future<void> openVoucherEditor(TransactionRecord record) async {
  if (record.kind != TransactionRecordKind.voucher) {
    AppSnackbar.error('Edit is not available for this record.');
    return;
  }

  final payload = await _resolveInvoicePayload(record);
  if (payload == null) {
    AppSnackbar.error('Voucher details are not available for editing.');
    return;
  }

  TransactionFormLookupController lookupBuilder() {
    return Get.put(
      TransactionFormLookupController(
        Get.find<PartiesRepository>(),
        Get.find<AccountsRepository>(),
        Get.find<ItemsRepository>(),
        Get.find<TaxRatesRepository>(),
      ),
    );
  }

  switch (record.type) {
    case 'payment':
      Get.to(
        () => const PaymentVoucherScreen(),
        binding: BindingsBuilder(() {
          final lookup = lookupBuilder();
          Get.put(
            PaymentVoucherFormController(
              Get.find<TransactionsRepository>(),
              lookup,
              initialPayload: payload,
            ),
          );
        }),
      );
      return;
    case 'receipt':
      Get.to(
        () => const ReceiptVoucherScreen(),
        binding: BindingsBuilder(() {
          final lookup = lookupBuilder();
          Get.put(
            ReceiptVoucherFormController(
              Get.find<TransactionsRepository>(),
              lookup,
              initialPayload: payload,
            ),
          );
        }),
      );
      return;
    case 'journal':
    case 'adjustment':
      Get.to(
        () => const AdjustmentVoucherScreen(),
        binding: BindingsBuilder(() {
          final lookup = lookupBuilder();
          Get.put(
            AdjustmentVoucherFormController(
              Get.find<TransactionsRepository>(),
              lookup,
              initialPayload: payload,
            ),
          );
        }),
      );
      return;
    default:
      AppSnackbar.error('Edit is not available for this voucher type.');
      return;
  }
}

Future<TransactionRecord> resolveTransactionDetailRecord(
  TransactionRecord record,
) async {
  final payload = await _resolveInvoicePayload(record);
  if (payload == null) {
    return record;
  }

  switch (record.kind) {
    case TransactionRecordKind.salesInvoice:
      return TransactionRecord.fromSalesInvoice(
        <String, dynamic>{
          'payload': payload,
          if (record.localId != null) 'local_id': record.localId,
          'sync_status': record.syncStatus,
          'is_dirty': record.isDirty,
        },
      );
    case TransactionRecordKind.purchaseInvoice:
      return TransactionRecord.fromPurchaseInvoice(
        <String, dynamic>{
          'payload': payload,
          if (record.localId != null) 'local_id': record.localId,
          'sync_status': record.syncStatus,
          'is_dirty': record.isDirty,
        },
      );
    case TransactionRecordKind.voucher:
      return TransactionRecord.fromVoucher(
        <String, dynamic>{
          'payload': payload,
          if (record.localId != null) 'local_id': record.localId,
          'sync_status': record.syncStatus,
          'is_dirty': record.isDirty,
        },
      );
  }
}

Future<void> printSalesInvoice(TransactionRecord record) async {
  if (record.kind != TransactionRecordKind.salesInvoice || record.id == null) {
    AppSnackbar.error('Print is not available for this invoice.');
    return;
  }

  try {
    await AppActionLoader.run(
      () async {
        final repository = Get.find<TransactionsRepository>();
        final endpoints = <String>[
          ApiEndpoints.salesInvoicePdf(record.id!),
          ApiEndpoints.exportSalesInvoicePdf(record.id!),
        ];

        for (final endpoint in endpoints) {
          try {
            final response = await repository.exportFile(
              endpoint: endpoint,
            );
            final exportData = _extractExportData(response);
            if (exportData == null) {
              continue;
            }

            final base64 = exportData['content_base64']?.toString();
            if (base64 != null && base64.isNotEmpty) {
              final bytes = base64Decode(base64);
              final directory = await getTemporaryDirectory();
              final file = File(
                p.join(
                  directory.path,
                  'sales_invoice_${record.id}_${DateTime.now().millisecondsSinceEpoch}.pdf',
                ),
              );
              await file.writeAsBytes(bytes);
              await _openOrSharePdf(file.path);
              return;
            }

            final url = _resolveExportUrl(
              exportData['download_url']?.toString(),
              exportData['path']?.toString(),
              exportData['url']?.toString(),
              exportData['file_url']?.toString(),
              exportData['file_path']?.toString(),
            );
            if (url == null || url.isEmpty) {
              continue;
            }

            final uri = Uri.tryParse(url);
            if (uri != null &&
                await launchUrl(uri, mode: LaunchMode.externalApplication)) {
              AppSnackbar.success('Invoice PDF opened successfully.');
              return;
            }
          } catch (_) {
            continue;
          }
        }

        AppSnackbar.error('PDF export is not available right now.');
      },
      message: 'Preparing invoice PDF...',
    );
  } catch (error) {
    AppSnackbar.error(error.toString());
  }
}

Future<void> printVoucher(TransactionRecord record) async {
  if (record.kind != TransactionRecordKind.voucher || record.id == null) {
    AppSnackbar.error('Print is not available for this voucher.');
    return;
  }

  try {
    await AppActionLoader.run(
      () async {
        final repository = Get.find<TransactionsRepository>();
        final response = await repository.exportFile(
          endpoint: ApiEndpoints.exportVoucherPdf(record.id!),
        );
        final exportData = _extractExportData(response);
        if (exportData == null) {
          AppSnackbar.error('PDF export is not available right now.');
          return;
        }

        final base64 = exportData['content_base64']?.toString();
        if (base64 != null && base64.isNotEmpty) {
          final bytes = base64Decode(base64);
          final directory = await getTemporaryDirectory();
          final file = File(
            p.join(
              directory.path,
              'voucher_${record.id}_${DateTime.now().millisecondsSinceEpoch}.pdf',
            ),
          );
          await file.writeAsBytes(bytes);
          await _openOrSharePdf(file.path);
          return;
        }

        final url = _resolveExportUrl(
          exportData['download_url']?.toString(),
          exportData['path']?.toString(),
          exportData['url']?.toString(),
          exportData['file_url']?.toString(),
          exportData['file_path']?.toString(),
        );
        if (url == null || url.isEmpty) {
          AppSnackbar.error('PDF export is not available right now.');
          return;
        }

        final uri = Uri.tryParse(url);
        if (uri != null &&
            await launchUrl(uri, mode: LaunchMode.externalApplication)) {
          AppSnackbar.success('Voucher PDF opened successfully.');
          return;
        }
        AppSnackbar.error('Unable to open voucher PDF.');
      },
      message: 'Preparing voucher PDF...',
    );
  } catch (error) {
    AppSnackbar.error(error.toString());
  }
}

/// Web-style edit rule: draft always; posted only if not linked to an invoice.
bool canEditVoucherRecord(TransactionRecord record) {
  if (record.kind != TransactionRecordKind.voucher) {
    return false;
  }
  if (record.status == 'draft') {
    return true;
  }
  if (record.status != 'posted') {
    return false;
  }
  final salesId = record.rawPayload['sales_invoice_id'];
  final purchaseId = record.rawPayload['purchase_invoice_id'];
  final hasSales = salesId != null && salesId.toString().trim().isNotEmpty;
  final hasPurchase =
      purchaseId != null && purchaseId.toString().trim().isNotEmpty;
  return !hasSales && !hasPurchase;
}

Future<void> deleteTransactionRecord({
  required BaseTransactionsTabController controller,
  required TransactionRecord record,
  bool closeAfterDelete = false,
}) async {
  final shouldDelete = await AppAlertDialog.confirmDelete(
    title: 'Delete Record',
    message:
        'Are you sure you want to delete ${record.number.isEmpty ? record.typeLabel : record.number}?',
  );

  if (shouldDelete == true) {
    await controller.deleteRecord(record);
    if (closeAfterDelete && Get.isOverlaysOpen == false) {
      Get.back<void>();
    }
  }
}

Future<bool> recordInvoicePayment(TransactionRecord record) async {
  if (record.id == null ||
      (record.kind != TransactionRecordKind.salesInvoice &&
          record.kind != TransactionRecordKind.purchaseInvoice)) {
    AppSnackbar.error('Record payment is not available for this invoice.');
    return false;
  }

  if (record.balanceDue <= 0) {
    AppSnackbar.error('No balance due remaining for this invoice.');
    return false;
  }

  if (!Get.find<TransactionsRepository>().networkMonitorService.isOnline.value) {
    AppSnackbar.error('Internet is required to record invoice payment.');
    return false;
  }

  final accountsRepository = Get.find<AccountsRepository>();
  final cashBankRecords = await AppActionLoader.run(
    () => accountsRepository.getCashBankAccounts(),
    message: 'Loading payment options...',
  );
  final cashBankOptions = _mapCashBankOptions(cashBankRecords);

  if (cashBankOptions.isEmpty) {
    AppSnackbar.error('Cash / Bank accounts are not available.');
    return false;
  }

  final paymentPayload = await _showInvoicePaymentDialog(
    record: record,
    cashBankOptions: cashBankOptions,
  );
  if (paymentPayload == null) {
    return false;
  }

  final repository = Get.find<TransactionsRepository>();
  final endpoint = record.kind == TransactionRecordKind.salesInvoice
      ? '${ApiEndpoints.salesInvoices}/${record.id}/payment'
      : '${ApiEndpoints.purchaseInvoices}/${record.id}/payment';

  await AppActionLoader.run(
    () async {
      await repository.apiClient.post<Map<String, dynamic>>(
        endpoint,
        data: paymentPayload,
      );
    },
    message: 'Recording payment...',
  );

  AppSnackbar.success(
    record.kind == TransactionRecordKind.salesInvoice
        ? 'Payment recorded and receipt voucher posted.'
        : 'Payment recorded and payment voucher posted.',
  );
  return true;
}

List<LookupOption> _mapCashBankOptions(List<Map<String, dynamic>> records) {
  final seen = <String>{};
  final options = <LookupOption>[];

  for (final record in records) {
    final id = int.tryParse(record['id']?.toString() ?? '') ?? 0;
    final label = (record['text'] ??
            record['account_name'] ??
            record['name'] ??
            record['label'] ??
            '')
        .toString()
        .trim();
    if (id <= 0 || label.isEmpty) {
      continue;
    }

    final option = LookupOption(
      id: id,
      label: label,
      code: (record['code'] ?? record['account_code'])?.toString(),
      rawId: record['id']?.toString(),
      group: record['group']?.toString(),
      kind: record['kind']?.toString(),
      transactionMode: record['transaction_mode']?.toString(),
      availableBalance: _parseNullableDouble(record['available_balance']),
    );

    final dedupeKey = label.toLowerCase();
    if (!seen.add(dedupeKey)) {
      continue;
    }
    options.add(option);
  }

  return options;
}

double? _parseNullableDouble(dynamic value) {
  if (value == null) {
    return null;
  }
  if (value is num) {
    return value.toDouble();
  }
  return double.tryParse(value.toString());
}

Future<bool> postInvoice(TransactionRecord record) async {
  if (record.id == null ||
      (record.kind != TransactionRecordKind.salesInvoice &&
          record.kind != TransactionRecordKind.purchaseInvoice)) {
    AppSnackbar.error('Post is not available for this invoice.');
    return false;
  }

  if (record.status != 'draft') {
    AppSnackbar.error('Only draft invoices can be posted.');
    return false;
  }

  if (!Get.find<TransactionsRepository>().networkMonitorService.isOnline.value) {
    AppSnackbar.error('Internet is required to post this invoice.');
    return false;
  }

  final confirmed = await AppAlertDialog.confirm(
    title: 'Post Invoice?',
    message: record.kind == TransactionRecordKind.salesInvoice
        ? 'This will post the sales invoice to ledgers and update stock.'
        : 'This will post the purchase invoice to ledgers and update stock.',
    confirmText: 'Yes, post it',
    cancelText: 'No',
  );

  if (confirmed != true) {
    return false;
  }

  final endpoint = record.kind == TransactionRecordKind.salesInvoice
      ? ApiEndpoints.salesInvoicePost(record.id!)
      : ApiEndpoints.purchaseInvoicePost(record.id!);

  await AppActionLoader.run(
    () async {
      await Get.find<TransactionsRepository>().apiClient.post<Map<String, dynamic>>(
        endpoint,
        data: const <String, dynamic>{},
      );
    },
    message: 'Posting invoice...',
  );

  AppSnackbar.success('Invoice posted successfully.');
  return true;
}

Future<bool> cancelInvoice(TransactionRecord record) async {
  if (record.id == null ||
      (record.kind != TransactionRecordKind.salesInvoice &&
          record.kind != TransactionRecordKind.purchaseInvoice)) {
    AppSnackbar.error('Cancel is not available for this invoice.');
    return false;
  }

  if (!Get.find<TransactionsRepository>().networkMonitorService.isOnline.value) {
    AppSnackbar.error('Internet is required to cancel this invoice.');
    return false;
  }

  final confirmed = await AppAlertDialog.confirm(
    title: 'Cancel Invoice?',
    message: record.kind == TransactionRecordKind.salesInvoice
        ? 'Linked receipts and sales posting will be cancelled, ledgers reversed, and stock restored.'
        : 'Linked payments and purchase posting will be cancelled, ledgers reversed, and stock adjusted.',
    confirmText: 'Yes, cancel it',
    cancelText: 'No',
    isDestructive: true,
  );

  if (confirmed != true) {
    return false;
  }

  final repository = Get.find<TransactionsRepository>();
  final endpoint = record.kind == TransactionRecordKind.salesInvoice
      ? '${ApiEndpoints.salesInvoices}/${record.id}/cancel'
      : '${ApiEndpoints.purchaseInvoices}/${record.id}/cancel';

  await AppActionLoader.run(
    () async {
      await repository.apiClient.post<Map<String, dynamic>>(
        endpoint,
        data: const <String, dynamic>{},
      );
    },
    message: 'Cancelling invoice...',
  );

  AppSnackbar.success('Invoice cancelled successfully.');
  return true;
}

Future<Map<String, dynamic>?> _resolveInvoicePayload(TransactionRecord record) async {
  if (record.id == null) {
    return record.rawPayload;
  }

  final endpoint = switch (record.kind) {
    TransactionRecordKind.salesInvoice => ApiEndpoints.salesInvoiceDetail(record.id!),
    TransactionRecordKind.purchaseInvoice => ApiEndpoints.purchaseInvoiceDetail(record.id!),
    TransactionRecordKind.voucher => ApiEndpoints.voucherDetail(record.id!),
  };
  if (endpoint.isEmpty) {
    return record.rawPayload;
  }

  try {
    final repository = Get.find<TransactionsRepository>();
    final response = await repository.fetchRecordDetail(endpoint: endpoint);
    final data = response['data'];
    if (data is Map<String, dynamic>) {
      return data;
    }
  } catch (_) {
    return record.rawPayload;
  }

  return record.rawPayload;
}

Future<void> _openOrSharePdf(String filePath) async {
  final openResult = await OpenFilex.open(filePath);
  if (openResult.type == ResultType.done) {
    AppSnackbar.success('Invoice PDF opened successfully.');
    return;
  }

  final fileUri = Uri.file(filePath);
  if (await launchUrl(fileUri, mode: LaunchMode.externalApplication)) {
    AppSnackbar.success('Invoice PDF opened successfully.');
    return;
  }

  final shareResult = await SharePlus.instance.share(
    ShareParams(
      files: <XFile>[XFile(filePath)],
      subject: 'Sales Invoice PDF',
      text: 'Sales invoice exported successfully.',
    ),
  );
  if (shareResult.status == ShareResultStatus.success ||
      shareResult.status == ShareResultStatus.dismissed) {
    AppSnackbar.success('Invoice PDF exported successfully.');
    return;
  }

  AppSnackbar.error('Unable to open invoice PDF.');
}

Map<String, dynamic>? _extractExportData(Map<String, dynamic> response) {
  final candidates = <dynamic>[
    response['data'],
    response['result'],
    response['file'],
    response,
  ];

  for (final candidate in candidates) {
    final resolved = _findExportPayload(candidate);
    if (resolved != null) {
      return resolved;
    }
  }

  return null;
}

Map<String, dynamic>? _findExportPayload(dynamic candidate) {
  if (candidate is Map<String, dynamic>) {
    final hasExportKeys =
        candidate['content_base64'] != null ||
        candidate['download_url'] != null ||
        candidate['path'] != null ||
        candidate['url'] != null ||
        candidate['file_url'] != null ||
        candidate['file_path'] != null;
    if (hasExportKeys) {
      return candidate;
    }

    for (final value in candidate.values) {
      final nested = _findExportPayload(value);
      if (nested != null) {
        return nested;
      }
    }
  } else if (candidate is List) {
    for (final value in candidate) {
      final nested = _findExportPayload(value);
      if (nested != null) {
        return nested;
      }
    }
  }

  return null;
}

String? _resolveExportUrl(String? primary, [String? secondary, String? third, String? fourth, String? fifth]) {
  final values = <String?>[primary, secondary, third, fourth, fifth];
  for (final raw in values) {
    final value = raw?.trim() ?? '';
    if (value.isEmpty) {
      continue;
    }
    if (value.startsWith('http://') || value.startsWith('https://')) {
      return value;
    }
    return '${AppConfig.origin}$value';
  }
  return null;
}

Future<Map<String, dynamic>?> _showInvoicePaymentDialog({
  required TransactionRecord record,
  required List<LookupOption> cashBankOptions,
}) {
  final amountController = TextEditingController(
    text: record.balanceDue.toStringAsFixed(2),
  );
  final dateController = TextEditingController(
    text: AppDateFormatter.formatDisplay(DateTime.now()),
  );
  LookupOption? selectedAccount;

  return Get.bottomSheet<Map<String, dynamic>>(
    StatefulBuilder(
      builder: (context, setState) {
        final theme = Theme.of(context);
        if (selectedAccount != null &&
            !cashBankOptions.any((item) => item.id == selectedAccount!.id)) {
          selectedAccount = null;
        }

        Future<void> pickDate() async {
          final parsed = AppDateFormatter.parse(dateController.text) ?? DateTime.now();
          final selected = await showDatePicker(
            context: context,
            initialDate: parsed,
            firstDate: DateTime(2000),
            lastDate: DateTime(2100),
          );
          if (selected != null) {
            dateController.text = AppDateFormatter.formatDisplay(selected);
            setState(() {});
          }
        }

        return Container(
          decoration: BoxDecoration(
            color: theme.colorScheme.surface,
            borderRadius: const BorderRadius.vertical(
              top: Radius.circular(24),
            ),
          ),
          child: SafeArea(
            top: false,
            child: SingleChildScrollView(
              padding: EdgeInsets.only(
                left: 16,
                right: 16,
                top: 12,
                bottom: MediaQuery.of(context).viewInsets.bottom + 16,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Center(
                    child: Container(
                      width: 44,
                      height: 5,
                      margin: const EdgeInsets.only(bottom: 16),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.outlineVariant,
                        borderRadius: BorderRadius.circular(999),
                      ),
                    ),
                  ),
                  Text(
                    'Record Payment',
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    record.kind == TransactionRecordKind.salesInvoice
                        ? 'Creates a receipt voucher against this sales invoice.'
                        : 'Creates a payment voucher against this purchase invoice.',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                  const SizedBox(height: 14),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: theme.colorScheme.primaryContainer.withValues(alpha: .55),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                        color: theme.colorScheme.primary.withValues(alpha: .15),
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Text(
                          'Balance Due',
                          style: theme.textTheme.labelLarge?.copyWith(
                            color: theme.colorScheme.onSurfaceVariant,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '₹${record.balanceDue.toStringAsFixed(2)}',
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w800,
                            color: theme.colorScheme.primary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),
                  CustomTextField(
                    label: 'Payment Date',
                    controller: dateController,
                    readOnly: true,
                    onTap: pickDate,
                    suffixIcon: Icons.edit_calendar_rounded,
                  ),
                  CustomDropdown<LookupOption>(
                    label: record.kind == TransactionRecordKind.salesInvoice
                        ? 'Received In'
                        : 'Paid From',
                    value: selectedAccount,
                    items: cashBankOptions,
                    itemLabelBuilder: (item) => item.label,
                    onChanged: (value) {
                      setState(() {
                        selectedAccount = value;
                      });
                    },
                  ),
                  CustomTextField(
                    label: 'Amount',
                    controller: amountController,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.only(top: 2, bottom: 12),
                    child: Text(
                      record.kind == TransactionRecordKind.salesInvoice
                          ? 'Posts a Receipt voucher (Dr Cash/Bank, Cr Party) linked to this invoice.'
                          : 'Posts a Payment voucher (Dr Party, Cr Cash/Bank) linked to this invoice.',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: <Widget>[
                      Expanded(
                        flex: 4,
                        child: OutlinedButton(
                          onPressed: () => Get.back<Map<String, dynamic>>(),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: theme.colorScheme.onSurfaceVariant,
                            side: BorderSide(
                              color: theme.colorScheme.outlineVariant,
                            ),
                            minimumSize: const Size.fromHeight(50),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: const Text('Cancel'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        flex: 6,
                        child: FilledButton(
                          onPressed: () {
                            final amount =
                                double.tryParse(amountController.text.trim()) ?? 0;
                            if (selectedAccount == null) {
                              AppSnackbar.error('Please select cash / bank account.');
                              return;
                            }
                            if (amount > record.balanceDue) {
                              AppSnackbar.error('Payment amount cannot exceed balance due.');
                              return;
                            }
                            if (amount <= 0) {
                              AppSnackbar.error('Please enter valid amount.');
                              return;
                            }
                          Get.back<Map<String, dynamic>>(
                              result: <String, dynamic>{
                                'amount': amount,
                                'cash_bank_account_id': selectedAccount!.id,
                                'payment_date': AppDateFormatter.toApiDate(
                                  dateController.text.trim(),
                                ),
                              },
                            );
                          },
                          style: FilledButton.styleFrom(
                            minimumSize: const Size.fromHeight(50),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: const Text('Record Payment'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        );
      },
    ),
    isDismissible: true,
    enableDrag: true,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
  );
}
