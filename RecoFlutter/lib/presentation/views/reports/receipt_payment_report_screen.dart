import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/utils/app_date_formatter.dart';
import '../../controllers/reports/receipt_payment_report_controller.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'ledger_report_screen.dart';
import 'widgets/report_ui_components.dart';

class ReceiptPaymentReportScreen extends GetView<ReceiptPaymentReportController> {
  const ReceiptPaymentReportScreen({super.key});

  static const Color _receiptColor = Color(0xFF16A34A);
  static const Color _paymentColor = Color(0xFFEF4444);

  @override
  Widget build(BuildContext context) {
    final lookup = Get.find<ReportLookupController>();
    return Scaffold(
      appBar: AppBar(
        title: ReportPageTitle(
          title: 'Receipt & Payment',
          icon: FontAwesomeIcons.moneyBillTransfer.data,
          color: Color(0xFF059669),
        ),
      ),
      body: Obx(() {
        if (controller.shouldShowInitialLoader) {
          return const ReportLoadingView();
        }
        final report = _mapOf(controller.reportData['data']);
        final message = report['message']?.toString();
        final receipts = _mapOf(report['receipts']);
        final payments = _mapOf(report['payments']);
        final ledgers = _listOfMaps(report['accounts']);

        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle: 'Every cash, bank, and OD movement of the period grouped head-wise, with opening and closing balances.',
              icon: FontAwesomeIcons.sliders.data,
              iconColor: const Color(0xFF059669),
              child: Column(
                children: <Widget>[
                  CustomDropdown<int>(
                    label: 'Financial Year',
                    value: controller.financialYearId.value,
                    items: lookup.financialYears
                        .map((e) => _asInt(e['id']))
                        .whereType<int>()
                        .toList(),
                    itemLabelBuilder: (value) {
                      final item = lookup.financialYears.firstWhere(
                        (fy) => _asInt(fy['id']) == value,
                        orElse: () => <String, dynamic>{},
                      );
                      return (item['name'] ?? 'FY').toString();
                    },
                    onChanged: (value) => controller.applyFinancialYear(value, lookup),
                  ),
                  ReportDateRangeRow(
                    fromController: controller.fromDateController,
                    toController: controller.toDateController,
                    onFromTap: () => _pickDate(context, controller.fromDateController),
                    onToTap: () => _pickDate(context, controller.toDateController),
                  ),
                  const SizedBox(height: 12),
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
                          reportName: 'receipt_payment',
                          exportEndpoint: ApiEndpoints.exportReceiptPaymentExcel,
                          queryParameters: controller.queryParameters,
                        ),
                      ),
                      ReportSecondaryButton(
                        label: 'PDF',
                        icon: FontAwesomeIcons.filePdf.data,
                        onTap: () => controller.exportPdf(
                          exportEndpoint: ApiEndpoints.exportReceiptPaymentPdf,
                          queryParameters: controller.queryParameters,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            if (message != null && message.isNotEmpty)
              ReportSectionCard(
                title: 'Receipt & Payment unavailable',
                icon: FontAwesomeIcons.circleInfo.data,
                iconColor: const Color(0xFF059669),
                child: Text(message),
              )
            else ...<Widget>[
              Row(
                children: <Widget>[
                  Expanded(
                    child: ReportStatCard(
                      label: 'Opening Balance',
                      value: controller.formatCurrency(report['opening_total']),
                      note: 'Cash, bank, and OD as on ${controller.formatDate((report['date_from'] ?? '').toString())}',
                      color: const Color(0xFF2563EB),
                      icon: FontAwesomeIcons.wallet.data,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: ReportStatCard(
                      label: 'Total Receipts',
                      value: controller.formatCurrency(receipts['total']),
                      note: 'Money received during the period.',
                      color: _receiptColor,
                      icon: FontAwesomeIcons.arrowDownWideShort.data,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                children: <Widget>[
                  Expanded(
                    child: ReportStatCard(
                      label: 'Total Payments',
                      value: controller.formatCurrency(payments['total']),
                      note: 'Money paid during the period.',
                      color: _paymentColor,
                      icon: FontAwesomeIcons.arrowUpWideShort.data,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: ReportStatCard(
                      label: 'Closing Balance',
                      value: controller.formatCurrency(report['closing_total']),
                      note: 'Cash, bank, and OD as on ${controller.formatDate((report['date_to'] ?? '').toString())}',
                      color: const Color(0xFFF59E0B),
                      icon: FontAwesomeIcons.vault.data,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              _headSection(
                context,
                title: 'Receipts',
                icon: FontAwesomeIcons.coins.data,
                color: _receiptColor,
                rows: _rowsOf(receipts),
                leadingLabel: 'Opening Balance b/f',
                leadingAmount: report['opening_total'],
                trailingLabel: null,
                trailingAmount: null,
                total: report['receipts_side_total'],
              ),
              const SizedBox(height: 12),
              _headSection(
                context,
                title: 'Payments',
                icon: FontAwesomeIcons.fileInvoiceDollar.data,
                color: _paymentColor,
                rows: _rowsOf(payments),
                leadingLabel: null,
                leadingAmount: null,
                trailingLabel: 'Closing Balance c/f',
                trailingAmount: report['closing_total'],
                total: report['payments_side_total'],
              ),
              const SizedBox(height: 12),
              ReportSectionCard(
                title: 'Cash / Bank Ledgers',
                icon: FontAwesomeIcons.tableList.data,
                iconColor: const Color(0xFF059669),
                trailing: Wrap(
                  spacing: 8,
                  runSpacing: 6,
                  alignment: WrapAlignment.end,
                  children: <Widget>[
                    _pill(context, _dateRangeLabel(), const Color(0xFF0EA5E9)),
                    _pill(
                      context,
                      report['is_balanced'] == true ? 'Balanced' : 'Not balanced',
                      report['is_balanced'] == true
                          ? const Color(0xFF16A34A)
                          : const Color(0xFFEF4444),
                      icon: report['is_balanced'] == true
                          ? FontAwesomeIcons.circleCheck.data
                          : FontAwesomeIcons.circleExclamation.data,
                    ),
                  ],
                ),
                child: ledgers.isEmpty
                    ? const Text('No cash or bank ledger movement')
                    : _buildLedgerTable(context, ledgers, report),
              ),
            ],
          ],
        );
      }),
    );
  }

  List<Map<String, dynamic>> _rowsOf(Map<String, dynamic> section) =>
      _listOfMaps(section['rows']);

  Widget _headSection(
    BuildContext context, {
    required String title,
    required IconData icon,
    required Color color,
    required List<Map<String, dynamic>> rows,
    required String? leadingLabel,
    required dynamic leadingAmount,
    required String? trailingLabel,
    required dynamic trailingAmount,
    required dynamic total,
  }) {
    return ReportSectionCard(
      title: title,
      icon: icon,
      iconColor: color,
      trailing: Wrap(
        spacing: 8,
        runSpacing: 6,
        alignment: WrapAlignment.end,
        children: <Widget>[
          _pill(context, _dateRangeLabel(), const Color(0xFF0EA5E9)),
          _pill(context, controller.formatCurrency(total), color),
        ],
      ),
      child: Container(
        clipBehavior: Clip.antiAlias,
        decoration: BoxDecoration(
          color: Theme.of(context).cardColor,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(
            color: Theme.of(context).dividerColor.withValues(alpha: .35),
          ),
        ),
        child: Column(
          children: <Widget>[
            if (leadingLabel != null)
              _balanceTile(context, leadingLabel, leadingAmount),
            if (rows.isEmpty)
              ListTile(
                contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                title: Text('No $title in this period'),
              ),
            ...rows.map((row) {
              final account = _mapOf(row['account']);
              final id = _asInt(account['id']);
              return ListTile(
                contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                title: Text(
                  (row['label'] ?? '-').toString(),
                  style: Theme.of(context).textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w700,
                    color: id == null ? null : color,
                    decoration: id == null ? null : TextDecoration.underline,
                    decorationColor: id == null ? null : color.withValues(alpha: .45),
                  ),
                ),
                subtitle: id == null
                    ? null
                    : Text(
                        'Tap to open ledger',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                        ),
                      ),
                trailing: Text(
                  controller.formatCurrency(row['amount']),
                  style: TextStyle(color: color, fontWeight: FontWeight.w700),
                ),
                onTap: id == null
                    ? null
                    : () => Get.to(() => LedgerReportScreen(initialAccountId: id)),
              );
            }),
            if (trailingLabel != null)
              _balanceTile(context, trailingLabel, trailingAmount),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 13),
              color: const Color(0xFF23263A),
              child: Row(
                children: <Widget>[
                  Expanded(
                    child: Text(
                      'Total',
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  Text(
                    controller.formatCurrency(total),
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: color,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _balanceTile(BuildContext context, String label, dynamic amount) {
    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 12),
      tileColor: Theme.of(context).colorScheme.primary.withValues(alpha: .06),
      title: Text(
        label,
        style: Theme.of(context).textTheme.titleSmall?.copyWith(
          fontWeight: FontWeight.w700,
        ),
      ),
      trailing: Text(
        controller.formatCurrency(amount),
        style: const TextStyle(fontWeight: FontWeight.w800),
      ),
    );
  }

  Widget _pill(
    BuildContext context,
    String label,
    Color color, {
    IconData? icon,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .10),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          if (icon != null) ...<Widget>[
            Icon(icon, size: 12, color: color),
            const SizedBox(width: 6),
          ],
          Text(
            label,
            style: Theme.of(context).textTheme.labelMedium?.copyWith(
              color: color,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLedgerTable(
    BuildContext context,
    List<Map<String, dynamic>> ledgers,
    Map<String, dynamic> report,
  ) {
    double sumOf(String key) => ledgers.fold<double>(
          0,
          (total, row) => total + (double.tryParse(row[key]?.toString() ?? '') ?? 0),
        );

    final tableRows = <DataRow>[
      ...ledgers.map((row) {
        final account = _mapOf(row['account']);
        final id = _asInt(account['id']);
        return DataRow(
          cells: <DataCell>[
            masterTextCell((account['account_code'] ?? '-').toString()),
            DataCell(
              Center(
                child: ReportLinkText(
                  (account['account_name'] ?? '-').toString(),
                  onTap: id == null
                      ? null
                      : () => Get.to(() => LedgerReportScreen(initialAccountId: id)),
                ),
              ),
            ),
            masterTextCell(controller.formatCurrency(row['opening'])),
            DataCell(
              Center(
                child: Text(
                  controller.formatCurrency(row['received']),
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                    color: _receiptColor,
                  ),
                ),
              ),
            ),
            DataCell(
              Center(
                child: Text(
                  controller.formatCurrency(row['paid']),
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                    color: _paymentColor,
                  ),
                ),
              ),
            ),
            masterTextCell(controller.formatCurrency(row['closing'])),
          ],
        );
      }),
      DataRow(
        color: reportTotalRowColor(context),
        cells: <DataCell>[
          const DataCell(SizedBox.shrink()),
          DataCell(Text('Total', style: reportTotalRowTextStyle(context))),
          DataCell(Center(child: Text(controller.formatCurrency(report['opening_total']), style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13)))),
          DataCell(Center(child: Text(controller.formatCurrency(sumOf('received')), style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13)))),
          DataCell(Center(child: Text(controller.formatCurrency(sumOf('paid')), style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13)))),
          DataCell(Center(child: Text(controller.formatCurrency(report['closing_total']), style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13)))),
        ],
      ),
    ];

    final calculatedHeight = 42.0 + ((ledgers.length + 1) * 52.0);
    final tableHeight = calculatedHeight.clamp(160.0, 550.0);

    return SizedBox(
      height: tableHeight,
      child: MastersTableShell(
        isLoading: false,
        emptyText: 'No cash or bank ledger movement',
        minWidth: 900,
                columns: <DataColumn2>[
                  masterColumn(context, 'Code'),
                  masterColumn(context, 'Ledger', size: ColumnSize.L),
                  masterColumn(context, 'Opening (₹)'),
                  masterColumn(context, 'Received (₹)'),
                  masterColumn(context, 'Paid (₹)'),
                  masterColumn(context, 'Closing (₹)'),
                ],
                rows: tableRows,
              ),
    );
  }

  Future<void> _pickDate(
    BuildContext context,
    TextEditingController dateController,
  ) async {
    final initial =
        AppDateFormatter.parse(dateController.text) ??
        DateTime.now();
    final selected = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      dateController.text = AppDateFormatter.formatDisplay(selected);
    }
  }

  int? _asInt(dynamic value) => int.tryParse(value?.toString() ?? '');

  Map<String, dynamic> _mapOf(dynamic value) {
    if (value is Map<String, dynamic>) {
      return value;
    }
    if (value is Map) {
      return Map<String, dynamic>.from(value);
    }
    return <String, dynamic>{};
  }

  List<Map<String, dynamic>> _listOfMaps(dynamic value) {
    if (value is! List) {
      return <Map<String, dynamic>>[];
    }
    return value
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList(growable: false);
  }

  String _dateRangeLabel() {
    return '${controller.formatDate(controller.fromDateController.text)} to ${controller.formatDate(controller.toDateController.text)}';
  }
}
