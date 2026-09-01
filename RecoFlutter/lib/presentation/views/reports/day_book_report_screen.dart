import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/utils/app_action_loader.dart';
import '../../../core/utils/app_date_formatter.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/repositories/transactions/transactions_repository.dart';
import '../../../data/models/transactions/transaction_entities.dart';
import '../../controllers/reports/day_book_report_controller.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import '../masters/widgets/masters_ui_components.dart';
import '../masters/history/party_history_screen.dart';
import 'ledger_report_screen.dart';
import '../transactions/details/transaction_detail_screen.dart';
import '../transactions/utils/invoice_transaction_actions.dart';
import 'widgets/report_ui_components.dart';

class DayBookReportScreen extends GetView<DayBookReportController> {
  const DayBookReportScreen({super.key});

  static const double _rowHeight = 68;

  @override
  Widget build(BuildContext context) {
    final lookup = Get.find<ReportLookupController>();
    return Scaffold(
      appBar: AppBar(
        title: ReportPageTitle(
          title: 'Day Book',
          icon: FontAwesomeIcons.calendarDay.data,
          color: Color(0xFF0891B2),
        ),
      ),
      body: Obx(
        () {
          if (controller.shouldShowInitialLoader) {
            return const ReportLoadingView();
          }
          final report = controller.reportData['data'];
          final rows = report is Map<String, dynamic> && report['rows'] is List
              ? List<Map<String, dynamic>>.from(
                  (report['rows'] as List).whereType<Map>(),
                )
              : <Map<String, dynamic>>[];
          return RefreshIndicator(
            onRefresh: controller.loadReport,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: <Widget>[
                ReportFilterPanel(
                  title: 'Filters',
                  subtitle: 'Pick date and financial year to refresh the report.',
                  icon: FontAwesomeIcons.sliders.data,
                  iconColor: const Color(0xFF0891B2),
                  child: Column(
                    children: <Widget>[
                      ReportDateRangeRow(
                        fromController: controller.fromDateController,
                        toController: controller.toDateController,
                        onFromTap: () => _pickDate(context, controller.fromDateController),
                        onToTap: () => _pickDate(context, controller.toDateController),
                      ),
                      const SizedBox(height: 10),
                      Obx(
                        () => CustomDropdown<int>(
                          label: 'Financial Year',
                          value: controller.financialYearId.value,
                          items: lookup.financialYears
                              .map((item) => _asInt(item['id']))
                              .whereType<int>()
                              .toList(),
                          itemLabelBuilder: (value) {
                            final item = lookup.financialYears.firstWhere(
                              (fy) => _asInt(fy['id']) == value,
                              orElse: () => <String, dynamic>{},
                            );
                            return (item['name'] ?? 'FY').toString();
                          },
                          onChanged: (value) =>
                              controller.applyFinancialYear(value, lookup),
                        ),
                      ),
                      ReportActionBar(
                        children: <Widget>[
                          ReportPrimaryButton(
                            label: 'Filter',
                            icon: FontAwesomeIcons.sliders.data,
                            onTap: controller.loadReport,
                          ),
                          ReportSecondaryButton(
                            label: 'Excel',
                            icon: FontAwesomeIcons.fileExcel.data,
                            onTap: () => controller.exportExcel(
                              reportName: 'day_book',
                              exportEndpoint: ApiEndpoints.exportDayBookExcel,
                              queryParameters: controller.queryParameters,
                            ),
                          ),
                          ReportSecondaryButton(
                            label: 'PDF',
                            icon: FontAwesomeIcons.filePdf.data,
                            onTap: () => controller.exportPdf(
                              exportEndpoint: ApiEndpoints.exportDayBookPdf,
                              queryParameters: controller.queryParameters,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
            ReportSectionCard(
              title: 'Day Book Entries',
              icon: FontAwesomeIcons.tableList.data,
              iconColor: const Color(0xFF0891B2),
              trailing: report is Map<String, dynamic>
                  ? Text(
                      '${rows.length} lines',
                      style: Theme.of(context).textTheme.labelLarge?.copyWith(
                        color: const Color(0xFF0891B2),
                        fontWeight: FontWeight.w700,
                      ),
                    )
                  : null,
              child: rows.isEmpty
                  ? const Center(child: Padding(
                          padding: EdgeInsets.all(16),
                          child: Text('No posted transactions found.'),
                        ))
                      : _buildDayBookTable(context, report, rows),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Future<void> _pickDate(
    BuildContext context,
    TextEditingController target,
  ) async {
    final initial = AppDateFormatter.parse(target.text) ?? DateTime.now();
    final selected = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      target.text = AppDateFormatter.formatDisplay(selected);
    }
  }

  Widget _buildDayBookTable(
    BuildContext context,
    dynamic reportData,
    List<Map<String, dynamic>> rows,
  ) {
    if (reportData is! Map) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(24),
          child: Text('No posted transactions found.'),
        ),
      );
    }

    final tableRows = <DataRow>[
      ...List<DataRow>.generate(rows.length, (index) {
        final row = rows[index];
        final accountId = _asInt(row['account_id']);
        final partyId = _asInt(row['party_id']);
        final partyName = (row['party_name'] ?? '').toString().trim();
        final serial = row['serial'] ?? (index + 1);
        return DataRow(
          cells: <DataCell>[
            masterTextCell('$serial'),
            masterTextCell(
              controller.formatDate((row['voucher_date'] ?? '').toString()),
            ),
            DataCell(
              Center(
                child: ReportLinkText(
                  (row['voucher_number'] ?? '-').toString(),
                  onTap: _asInt(row['voucher_id']) == null
                      ? null
                      : () => _openVoucherDetail(row),
                ),
              ),
            ),
            DataCell(Center(child: _buildVoucherTypePill(context, row))),
            DataCell(
              Center(
                child: ReportLinkText(
                  (row['account_name'] ?? '-').toString(),
                  maxLines: 3,
                  onTap: accountId == null
                      ? null
                      : () => Get.to(
                            () => LedgerReportScreen(initialAccountId: accountId),
                          ),
                ),
              ),
            ),
            DataCell(
              Center(
                child: partyId == null
                    ? Text(
                        partyName.isEmpty ? '-' : partyName,
                        maxLines: 3,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.center,
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          fontWeight: FontWeight.w500,
                          height: 1.35,
                        ),
                      )
                    : ReportLinkText(
                        partyName.isEmpty ? '-' : partyName,
                        maxLines: 3,
                        onTap: () => Get.to(
                          () => PartyHistoryScreen(partyId: partyId),
                        ),
                      ),
              ),
            ),
            _buildNarrationCell(context, row),
            DataCell(
              Center(
                child: Text(
                  _formatDrCrAmount(row['debit']),
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                    color: Color(0xFF16A34A),
                  ),
                ),
              ),
            ),
            DataCell(
              Center(
                child: Text(
                  _formatDrCrAmount(row['credit']),
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                    color: Color(0xFFEF4444),
                  ),
                ),
              ),
            ),
          ],
        );
      }),
      DataRow(
        color: reportTotalRowColor(context),
        cells: <DataCell>[
          const DataCell(SizedBox.shrink()),
          const DataCell(SizedBox.shrink()),
          const DataCell(SizedBox.shrink()),
          const DataCell(SizedBox.shrink()),
          const DataCell(SizedBox.shrink()),
          const DataCell(SizedBox.shrink()),
          DataCell(
            Center(
              child: Text(
                'Total',
                style: reportTotalRowTextStyle(context),
              ),
            ),
          ),
          DataCell(
            Center(
              child: Text(
                controller.formatCurrency(reportData['total_debit']),
                style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
              ),
            ),
          ),
          DataCell(
            Center(
              child: Text(
                controller.formatCurrency(reportData['total_credit']),
                style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
              ),
            ),
          ),
        ],
      ),
    ];

    final calculatedHeight =
        42.0 + (rows.length * _rowHeight) + _rowHeight;
    final tableHeight = calculatedHeight.clamp(200.0, 620.0);

    return SizedBox(
      height: tableHeight,
      child: MastersTableShell(
        isLoading: false,
        emptyText: 'No posted transactions found.',
        minWidth: 1320,
        dataRowHeight: _rowHeight,
        columns: <DataColumn2>[
          masterColumn(context, '#', size: ColumnSize.S),
          masterColumn(context, 'Date', size: ColumnSize.M),
          masterColumn(context, 'Voucher #', size: ColumnSize.M),
          masterColumn(context, 'Type', size: ColumnSize.S),
          masterColumn(context, 'Particulars', size: ColumnSize.L),
          masterColumn(context, 'Party', size: ColumnSize.M),
          masterColumn(context, 'Narration', size: ColumnSize.L),
          masterColumn(context, 'Debit (₹)', size: ColumnSize.M),
          masterColumn(context, 'Credit (₹)', size: ColumnSize.M),
        ],
        rows: tableRows,
      ),
    );
  }

  Widget _buildVoucherTypePill(BuildContext context, Map<String, dynamic> row) {
    final rawType = (row['voucher_type'] ?? '-').toString().trim();
    if (rawType.isEmpty || rawType == '-') {
      return Text(
        '-',
        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
          fontWeight: FontWeight.w500,
        ),
      );
    }
    final label = rawType[0].toUpperCase() + rawType.substring(1);
    final scheme = Theme.of(context).colorScheme;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: scheme.primary.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: Theme.of(context).textTheme.labelMedium?.copyWith(
          color: scheme.primary,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }

  DataCell _buildNarrationCell(BuildContext context, Map<String, dynamic> row) {
    final narration = (row['narration'] ?? '-').toString();
    final limited = narration.length > 60
        ? '${narration.substring(0, 60)}...'
        : narration;
    final salesInvoiceId = _asInt(row['sales_invoice_id']);
    final purchaseInvoiceId = _asInt(row['purchase_invoice_id']);
    final hasInvoiceLink =
        salesInvoiceId != null || purchaseInvoiceId != null;

    return DataCell(
      Align(
        alignment: Alignment.centerLeft,
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 6),
          child: Wrap(
            spacing: 6,
            runSpacing: 4,
            crossAxisAlignment: WrapCrossAlignment.center,
            children: <Widget>[
              if (salesInvoiceId != null)
                ReportLinkText(
                  'Invoice',
                  onTap: () => _openInvoice('sales', salesInvoiceId),
                ),
              if (purchaseInvoiceId != null)
                ReportLinkText(
                  'Invoice',
                  onTap: () => _openInvoice('purchase', purchaseInvoiceId),
                ),
              if (!hasInvoiceLink || limited != '-')
                Text(
                  limited,
                  maxLines: 3,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                    height: 1.35,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  String _formatDrCrAmount(dynamic value) {
    final amount = double.tryParse(value?.toString() ?? '0') ?? 0;
    if (amount <= 0) {
      return '-';
    }
    return controller.formatCurrency(amount);
  }

  Future<void> _openInvoice(String movementType, int invoiceId) async {
    final record = TransactionRecord(
      kind: movementType == 'sales'
          ? TransactionRecordKind.salesInvoice
          : TransactionRecordKind.purchaseInvoice,
      id: invoiceId,
      number: 'Invoice',
      type: movementType,
      typeLabel: movementType == 'sales' ? 'Sales Invoice' : 'Purchase',
      rawPayload: <String, dynamic>{'id': invoiceId},
    );
    try {
      final detailRecord = await resolveTransactionDetailRecord(record);
      await Get.to<void>(
        () => TransactionDetailScreen(record: detailRecord),
      );
    } catch (_) {
      AppSnackbar.warning(
        'Full invoice details could not be loaded. Showing available data.',
      );
      await Get.to<void>(
        () => TransactionDetailScreen(record: record),
      );
    }
  }

  int? _asInt(dynamic value) => int.tryParse(value?.toString() ?? '');

  Future<void> _openVoucherDetail(Map<String, dynamic> row) async {
    final summaryRecord = _buildVoucherRecord(row);
    final voucherId = _asInt(row['voucher_id']);
    if (voucherId == null) {
      Get.to(() => TransactionDetailScreen(record: summaryRecord));
      return;
    }

    try {
      final response = await AppActionLoader.run(
        () => Get.find<TransactionsRepository>().fetchRecordDetail(
          endpoint: ApiEndpoints.voucherDetail(voucherId),
        ),
        message: 'Loading voucher details...',
      );
      final detail = response['data'];
      final record = detail is Map<String, dynamic>
          ? TransactionRecord.fromVoucher(detail)
          : summaryRecord;
      Get.to(() => TransactionDetailScreen(record: record));
    } catch (_) {
      AppSnackbar.warning(
        'Full voucher details could not be loaded. Showing available data.',
      );
      Get.to(() => TransactionDetailScreen(record: summaryRecord));
    }
  }

  TransactionRecord _buildVoucherRecord(Map<String, dynamic> row) {
    final payload = <String, dynamic>{
      'id': row['voucher_id'],
      'voucher_number': row['voucher_number'],
      'voucher_type': row['voucher_type'],
      'type_label': (row['voucher_type'] ?? '').toString(),
      'voucher_date': row['voucher_date'] ?? controller.fromDateController.text,
      'status': 'posted',
      'party_id': row['party_id'],
      'party': row['party_id'] == null
          ? null
          : <String, dynamic>{
              'id': row['party_id'],
              'name': row['party_name'],
            },
      'narration': row['narration'],
      'total_debit': row['debit'] ?? row['credit'] ?? 0,
    };

    return TransactionRecord.fromVoucher(payload);
  }
}
