import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/network/api_client.dart';
import '../../../core/utils/amount_formatter.dart';
import '../../../core/utils/app_action_loader.dart';
import '../../../core/utils/app_date_formatter.dart';
import '../../../core/utils/app_snackbar.dart';
import '../masters/widgets/masters_ui_components.dart';

Future<void> showInvoiceSettlementDetails({
  required String invoiceType,
  required Object invoiceId,
  String? title,
}) async {
  await _showSettlementSheet(
    title: title == null || title.isEmpty
        ? 'Invoice Settlements'
        : 'Settlements · $title',
    loader: () async {
      final response = await Get.find<ApiClient>().get<Map<String, dynamic>>(
        ApiEndpoints.reportsInvoiceSettlementDetails,
        queryParameters: <String, dynamic>{
          'invoice_type': invoiceType,
          'invoice_id': invoiceId,
        },
      );
      return response.data?['data'] as Map<String, dynamic>? ??
          <String, dynamic>{};
    },
    rowsKey: 'settlements',
  );
}

Future<void> showPaymentSettlementDetails({
  required Object voucherId,
  String? title,
}) async {
  await _showSettlementSheet(
    title: title == null || title.isEmpty
        ? 'Payment Settlements'
        : 'Settlements · $title',
    loader: () async {
      final response = await Get.find<ApiClient>().get<Map<String, dynamic>>(
        ApiEndpoints.reportsPaymentSettlementDetails,
        queryParameters: <String, dynamic>{'voucher_id': voucherId},
      );
      return response.data?['data'] as Map<String, dynamic>? ??
          <String, dynamic>{};
    },
    rowsKey: 'invoices_settled',
  );
}

Future<void> _showSettlementSheet({
  required String title,
  required Future<Map<String, dynamic>> Function() loader,
  required String rowsKey,
}) async {
  Map<String, dynamic>? data;
  try {
    data = await AppActionLoader.run(loader, message: 'Loading settlements...');
  } catch (_) {
    AppSnackbar.error('Unable to load settlement details.');
    return;
  }

  if (data == null || data.isEmpty) {
    AppSnackbar.error('No settlement details found.');
    return;
  }

  final rows = (data[rowsKey] is List)
      ? (data[rowsKey] as List)
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList()
      : <Map<String, dynamic>>[];

  await Get.bottomSheet<void>(
    SafeArea(
      child: Material(
        color: Theme.of(Get.context!).cardColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              const MasterBottomSheetHandle(),
              const SizedBox(height: 10),
              Text(
                title,
                style: Theme.of(Get.context!).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: <Widget>[
                  if (data['total_allocated'] != null)
                    _metaChip(
                      'Allocated',
                      AmountFormatter.currency(data['total_allocated']),
                    ),
                  if (data['total_settled'] != null)
                    _metaChip(
                      'Settled',
                      AmountFormatter.currency(data['total_settled']),
                    ),
                  if (data['outstanding'] != null)
                    _metaChip(
                      'Outstanding',
                      AmountFormatter.currency(data['outstanding']),
                    ),
                ],
              ),
              const SizedBox(height: 12),
              if (rows.isEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 24),
                  child: Center(
                    child: Text(
                      'No settlement mappings yet.',
                      style: Theme.of(Get.context!).textTheme.bodyMedium
                          ?.copyWith(
                            color: Theme.of(Get.context!)
                                .colorScheme
                                .onSurfaceVariant,
                          ),
                    ),
                  ),
                )
              else
                ConstrainedBox(
                  constraints: BoxConstraints(
                    maxHeight: MediaQuery.of(Get.context!).size.height * 0.45,
                  ),
                  child: ListView.separated(
                    shrinkWrap: true,
                    itemCount: rows.length,
                    separatorBuilder: (_, _) => const Divider(height: 1),
                    itemBuilder: (context, index) {
                      final row = rows[index];
                      final primary = (row['voucher_number'] ??
                              row['invoice_number'] ??
                              row['reference_number'] ??
                              '-')
                          .toString();
                      final date = AppDateFormatter.formatDisplay(
                        (row['voucher_date'] ?? row['invoice_date'] ?? '')
                            .toString(),
                      );
                      final amount = AmountFormatter.currency(
                        row['amount_settled'] ??
                            row['amount_allocated'] ??
                            row['amount'] ??
                            0,
                      );
                      final status = (row['status'] ?? '').toString();
                      return ListTile(
                        dense: true,
                        contentPadding: EdgeInsets.zero,
                        title: Text(
                          primary,
                          style: Theme.of(context)
                              .textTheme
                              .bodyMedium
                              ?.copyWith(fontWeight: FontWeight.w700),
                        ),
                        subtitle: Text(
                          [
                            if (date.isNotEmpty && date != '-') date,
                            if (status.isNotEmpty) status,
                            if ((row['voucher_type'] ?? '').toString().isNotEmpty)
                              row['voucher_type'].toString(),
                          ].join(' · '),
                        ),
                        trailing: Text(
                          amount,
                          style: Theme.of(context).textTheme.bodyMedium
                              ?.copyWith(
                                fontWeight: FontWeight.w800,
                                color: const Color(0xFF2563EB),
                              ),
                        ),
                      );
                    },
                  ),
                ),
            ],
          ),
        ),
      ),
    ),
    isScrollControlled: true,
  );
}

Widget _metaChip(String label, String value) {
  return Container(
    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
    decoration: BoxDecoration(
      color: const Color(0xFF2563EB).withValues(alpha: .08),
      borderRadius: BorderRadius.circular(999),
    ),
    child: Text(
      '$label: $value',
      style: const TextStyle(
        fontWeight: FontWeight.w700,
        fontSize: 12,
        color: Color(0xFF2563EB),
      ),
    ),
  );
}
