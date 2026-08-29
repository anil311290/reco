import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../controllers/reports/aging_summary_report_controller.dart';
import '../masters/history/party_history_screen.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'settlement_details_sheet.dart';
import 'widgets/outstanding_filters_form.dart';
import 'widgets/report_ui_components.dart';

class AgingSummaryReportScreen extends GetView<AgingSummaryReportController> {
  const AgingSummaryReportScreen({super.key});

  static const Color _primaryColor = Color(0xFFEA580C);
  static const Color _receivableColor = Color(0xFFDC2626);
  static const Color _payableColor = Color(0xFF7C3AED);

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: ReportPageTitle(
          title: 'Aging Summary',
          icon: FontAwesomeIcons.hourglassHalf.data,
          color: _primaryColor,
        ),
      ),
      body: Obx(() {
        if (controller.shouldShowInitialLoader) {
          return const ReportLoadingView();
        }

        final summary = controller.summary;
        final rows = controller.rows;
        final receivables =
            summary['receivables'] is Map
                ? Map<String, dynamic>.from(summary['receivables'] as Map)
                : <String, dynamic>{};
        final payables =
            summary['payables'] is Map
                ? Map<String, dynamic>.from(summary['payables'] as Map)
                : <String, dynamic>{};

        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle:
                  'Combined receivables and payables with overdue duration buckets.',
              icon: FontAwesomeIcons.sliders.data,
              iconColor: _primaryColor,
              child: OutstandingFiltersForm(
                controller: controller,
                primaryColor: _primaryColor,
                onFilter: controller.loadReport,
                exportExcel: () {
                  controller.exportExcel(
                    reportName: 'aging_summary',
                    exportEndpoint: ApiEndpoints.exportAgingSummaryExcel,
                    queryParameters: controller.queryParameters,
                  );
                },
                exportPdf: () {
                  controller.exportPdf(
                    exportEndpoint: ApiEndpoints.exportAgingSummaryPdf,
                    queryParameters: controller.queryParameters,
                  );
                },
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: <Widget>[
                Expanded(
                  child: ReportStatCard(
                    label: 'Receivables',
                    value: controller.formatCurrency(
                      summary['receivables_total'],
                    ),
                    note: 'Total open AR',
                    color: _receivableColor,
                    icon: FontAwesomeIcons.handHoldingDollar.data,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: ReportStatCard(
                    label: 'Payables',
                    value: controller.formatCurrency(
                      summary['payables_total'],
                    ),
                    note: 'Total open AP',
                    color: _payableColor,
                    icon: FontAwesomeIcons.wallet.data,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            _BucketSection(
              title: 'Receivables by Age',
              color: _receivableColor,
              buckets: receivables,
              formatCurrency: controller.formatCurrency,
            ),
            const SizedBox(height: 12),
            _BucketSection(
              title: 'Payables by Age',
              color: _payableColor,
              buckets: payables,
              formatCurrency: controller.formatCurrency,
            ),
            const SizedBox(height: 12),
            ReportSectionCard(
              title: 'Aging Detail',
              icon: FontAwesomeIcons.tableList.data,
              iconColor: _primaryColor,
              child: rows.isEmpty
                  ? Padding(
                      padding: const EdgeInsets.all(24),
                      child: Center(
                        child: Text(
                          'No aging rows for selected filters',
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                        ),
                      ),
                    )
                  : _buildTable(context, rows),
            ),
          ],
        );
      }),
    );
  }

  Widget _buildTable(BuildContext context, List<Map<String, dynamic>> rows) {
    final tableRows = List<DataRow>.generate(rows.length, (index) {
      final row = rows[index];
      final party = row['party'] is Map
          ? Map<String, dynamic>.from(row['party'] as Map)
          : <String, dynamic>{};
      final reportType = (row['report_type'] ?? '').toString();
      final isReceivable = reportType.toLowerCase() == 'receivable';
      final invoiceType = isReceivable ? 'sales' : 'purchase';

      return DataRow(
        cells: <DataCell>[
          masterTextCell('${index + 1}'),
          DataCell(
            Center(
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: (isReceivable ? _receivableColor : _payableColor)
                      .withValues(alpha: .12),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  reportType.isEmpty ? '-' : reportType,
                  style: TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 11,
                    color: isReceivable ? _receivableColor : _payableColor,
                  ),
                ),
              ),
            ),
          ),
          DataCell(
            Center(
              child: ReportLinkText(
                _val(row['invoice_number']),
                onTap: row['invoice_id'] == null
                    ? null
                    : () => showInvoiceSettlementDetails(
                          invoiceType: invoiceType,
                          invoiceId: row['invoice_id'],
                          title: _val(row['invoice_number']),
                        ),
              ),
            ),
          ),
          DataCell(
            Center(
              child: ReportLinkText(
                _val(party['name']),
                onTap: party['id'] == null
                    ? null
                    : () {
                        Get.to(() => PartyHistoryScreen(partyId: party['id']));
                      },
              ),
            ),
          ),
          masterTextCell(controller.formatDate(_val(row['invoice_date']))),
          masterTextCell(controller.formatDate(_val(row['due_date']))),
          masterTextCell('${row['billed_days'] ?? '-'}'),
          masterTextCell('${row['due_days'] ?? row['overdue_days'] ?? '-'}'),
          masterTextCell(controller.formatCurrency(row['invoice_total'])),
          DataCell(
            Center(
              child: Text(
                controller.formatCurrency(row['balance']),
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  color: isReceivable ? _receivableColor : _payableColor,
                ),
              ),
            ),
          ),
        ],
      );
    });

    final height = (42.0 + (rows.length * 52.0)).clamp(160.0, 560.0);
    return SizedBox(
      height: height,
      child: MastersTableShell(
        isLoading: false,
        emptyText: 'No aging rows',
        minWidth: 1100,
        columns: <DataColumn2>[
          masterColumn(context, '#', fixedWidth: 48),
          masterColumn(context, 'Type', fixedWidth: 100),
          masterColumn(context, 'Invoice', size: ColumnSize.M),
          masterColumn(context, 'Party', size: ColumnSize.L),
          masterColumn(context, 'Inv Date', size: ColumnSize.S),
          masterColumn(context, 'Due', size: ColumnSize.S),
          masterColumn(context, 'Billed', fixedWidth: 64),
          masterColumn(context, 'Due Days', fixedWidth: 72),
          masterColumn(context, 'Amount', size: ColumnSize.M),
          masterColumn(context, 'Balance', size: ColumnSize.M),
        ],
        rows: tableRows,
      ),
    );
  }

  String _val(dynamic value) {
    final text = value?.toString().trim() ?? '';
    return text.isEmpty ? '-' : text;
  }
}

class _BucketSection extends StatelessWidget {
  const _BucketSection({
    required this.title,
    required this.color,
    required this.buckets,
    required this.formatCurrency,
  });

  final String title;
  final Color color;
  final Map<String, dynamic> buckets;
  final String Function(dynamic) formatCurrency;

  static const _order = <String>[
    'current',
    '1_30',
    '31_60',
    '61_90',
    '91_plus',
  ];

  @override
  Widget build(BuildContext context) {
    return ReportSectionCard(
      title: title,
      icon: FontAwesomeIcons.chartColumn.data,
      iconColor: color,
      child: LayoutBuilder(
        builder: (context, constraints) {
          final crossAxisCount = constraints.maxWidth >= 700
              ? 5
              : constraints.maxWidth >= 440
                  ? 3
                  : 2;
          return GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: crossAxisCount,
              crossAxisSpacing: 8,
              mainAxisSpacing: 8,
              mainAxisExtent: 88,
            ),
            itemCount: _order.length,
            itemBuilder: (context, index) {
              final key = _order[index];
              final bucket = buckets[key] is Map
                  ? Map<String, dynamic>.from(buckets[key] as Map)
                  : <String, dynamic>{};
              return Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: .06),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: color.withValues(alpha: .14)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      (bucket['label'] ?? key).toString(),
                      style: Theme.of(context).textTheme.labelMedium?.copyWith(
                            color: color,
                            fontWeight: FontWeight.w800,
                          ),
                    ),
                    const Spacer(),
                    Text(
                      '${bucket['count'] ?? 0} inv',
                      style: Theme.of(context).textTheme.labelSmall?.copyWith(
                            color: Theme.of(context)
                                .colorScheme
                                .onSurfaceVariant,
                          ),
                    ),
                    Text(
                      formatCurrency(bucket['amount']),
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.w800,
                          ),
                    ),
                  ],
                ),
              );
            },
          );
        },
      ),
    );
  }
}
