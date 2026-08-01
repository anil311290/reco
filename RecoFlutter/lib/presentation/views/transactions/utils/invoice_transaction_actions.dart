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
  final cashBankOptions = cashBankRecords
      .map(LookupOption.fromJson)
      .where((item) => item.id > 0 && item.label.trim().isNotEmpty)
      .toList();

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
    text: DateTime.now().toIso8601String().substring(0, 10),
  );
  String paymentMode = '';
  LookupOption? selectedAccount;

  return Get.dialog<Map<String, dynamic>>(
    StatefulBuilder(
      builder: (context, setState) {
        final theme = Theme.of(context);
        final filteredAccounts = paymentMode.isEmpty
            ? cashBankOptions
            : cashBankOptions
                .where((item) => item.transactionMode == paymentMode)
                .toList();
        if (selectedAccount != null &&
            !filteredAccounts.any((item) => item.id == selectedAccount!.id)) {
          selectedAccount = null;
        }

        Future<void> pickDate() async {
          final parsed = DateTime.tryParse(dateController.text) ?? DateTime.now();
          final selected = await showDatePicker(
            context: context,
            initialDate: parsed,
            firstDate: DateTime(2000),
            lastDate: DateTime(2100),
          );
          if (selected != null) {
            dateController.text = selected.toIso8601String().substring(0, 10);
            setState(() {});
          }
        }

        return Dialog(
          insetPadding: const EdgeInsets.symmetric(horizontal: 20),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(18),
          ),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
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
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: theme.colorScheme.errorContainer.withValues(alpha: .35),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    'Balance Due: Rs ${record.balanceDue.toStringAsFixed(2)}',
                    style: theme.textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                const SizedBox(height: 14),
                TextField(
                  controller: dateController,
                  readOnly: true,
                  onTap: pickDate,
                  decoration: const InputDecoration(
                    labelText: 'Payment Date',
                    suffixIcon: Icon(Icons.calendar_today_outlined),
                  ),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  initialValue: paymentMode.isEmpty ? null : paymentMode,
                  decoration: const InputDecoration(
                    labelText: 'Payment Mode',
                  ),
                  items: const <DropdownMenuItem<String>>[
                    DropdownMenuItem(value: 'cash', child: Text('Cash')),
                    DropdownMenuItem(value: 'bank', child: Text('Bank')),
                    DropdownMenuItem(value: 'od', child: Text('OD')),
                  ],
                  onChanged: (value) {
                    setState(() {
                      paymentMode = value ?? '';
                      selectedAccount = null;
                    });
                  },
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<LookupOption>(
                  initialValue: selectedAccount,
                  decoration: InputDecoration(
                    labelText: record.kind == TransactionRecordKind.salesInvoice
                        ? 'Received In'
                        : 'Paid From',
                  ),
                  items: filteredAccounts
                      .map(
                        (item) => DropdownMenuItem<LookupOption>(
                          value: item,
                          child: Text(item.label),
                        ),
                      )
                      .toList(),
                  onChanged: (value) {
                    setState(() {
                      selectedAccount = value;
                    });
                  },
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: amountController,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: const InputDecoration(
                    labelText: 'Amount',
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: <Widget>[
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () => Get.back<Map<String, dynamic>>(),
                        child: const Text('Cancel'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: FilledButton(
                        onPressed: () {
                          final amount =
                              double.tryParse(amountController.text.trim()) ?? 0;
                          if (paymentMode.isEmpty) {
                            AppSnackbar.error('Please select payment mode.');
                            return;
                          }
                          if (selectedAccount == null) {
                            AppSnackbar.error('Please select cash / bank account.');
                            return;
                          }
                          if (amount <= 0) {
                            AppSnackbar.error('Please enter valid amount.');
                            return;
                          }
                          Get.back<Map<String, dynamic>>(
                            result: <String, dynamic>{
                              'amount': amount,
                              'payment_mode': paymentMode,
                              'cash_bank_account_id': selectedAccount!.id,
                              'payment_date': dateController.text.trim(),
                            },
                          );
                        },
                        child: const Text('Record Payment'),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    ),
    barrierDismissible: true,
  );
}
