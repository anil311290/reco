import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../controllers/reports/debtors_outstanding_report_controller.dart';
import '../masters/history/party_history_screen.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'settlement_details_sheet.dart';
import 'widgets/outstanding_filters_form.dart';
import 'widgets/report_ui_components.dart';

class DebtorsOutstandingReportScreen extends StatefulWidget {
  const DebtorsOutstandingReportScreen({super.key});

  @override
  State<DebtorsOutstandingReportScreen> createState() =>
      _DebtorsOutstandingReportScreenState();
}

class _DebtorsOutstandingReportScreenState
    extends State<DebtorsOutstandingReportScreen> {
  static const Color _primaryColor = Color(0xFFDC2626);
  static const Color _secondaryColor = Color(0xFF2563EB);

  DebtorsOutstandingReportController get controller =>
      Get.find<DebtorsOutstandingReportController>();

  /// Web-style tabs: invoice wise (default) | party wise
  bool _partyWise = false;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: ReportPageTitle(
          title: 'Receivables Outstanding',
          icon: FontAwesomeIcons.handHoldingDollar.data,
          color: _primaryColor,
        ),
      ),
      body: Obx(() {
        if (controller.shouldShowInitialLoader) {
          return const ReportLoadingView();
        }
        final report = controller.reportData['data'];
        final invoiceRows = _getRows(report);
        final partyRows = summarizeOutstandingPartyWise(invoiceRows);
        final total = report is Map
            ? double.tryParse(report['total']?.toString() ?? '0') ?? 0.0
            : 0.0;
        final basisLabel = controller.basis.value == 'billed'
            ? 'Billed Days'
            : 'Due Days';
        final asOf = report is Map
            ? controller.formatDate(report['as_of_date']?.toString() ?? '')
            : controller.formatDate(controller.asOfDateController.text);

        return ListView(
          padding: const EdgeInsets.all(16),
          physics: const AlwaysScrollableScrollPhysics(),
          keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle:
                  'Invoice-wise receivables with billed/due aging and settlements.',
              icon: FontAwesomeIcons.sliders.data,
              iconColor: _primaryColor,
              child: OutstandingFiltersForm(
                controller: controller,
                primaryColor: _primaryColor,
                onFilter: controller.loadReport,
                exportExcel: () {
                  controller.exportExcel(
                    reportName: 'debtors_outstanding',
                    exportEndpoint: ApiEndpoints.exportDebtorsOutstandingExcel,
                    queryParameters: controller.queryParameters,
                  );
                },
                exportPdf: () {
                  controller.exportPdf(
                    exportEndpoint: ApiEndpoints.exportDebtorsOutstandingPdf,
                    queryParameters: controller.queryParameters,
                  );
                },
              ),
            ),
            const SizedBox(height: 6),
            // if (report is Map)
            //   Row(
            //     children: <Widget>[
            //       Expanded(
            //         child: ReportStatCard(
            //           label: 'Total Outstanding',
            //           value: controller.formatCurrency(report['total']),
            //           note: 'Open invoice balances.',
            //           color: _primaryColor,
            //           icon: FontAwesomeIcons.sackDollar.data,
            //         ),
            //       ),
            //       const SizedBox(width: 10),
            //       Expanded(
            //         child: ReportStatCard(
            //           label: 'Invoices',
            //           value: '${invoiceRows.length}',
            //           note: 'Outstanding receivable invoices.',
            //           color: _secondaryColor,
            //           icon: FontAwesomeIcons.fileInvoiceDollar.data,
            //         ),
            //       ),
            //     ],
            //   ),
            if (report is Map) const SizedBox(height: 12),
            ReportSectionCard(
              title: _partyWise ? 'Party Wise' : 'Invoice Wise',
              icon: FontAwesomeIcons.tableList.data,
              iconColor: _primaryColor,
              trailing: Wrap(
                spacing: 6,
                runSpacing: 6,
                crossAxisAlignment: WrapCrossAlignment.center,
                children: <Widget>[
                  _sectionPill(context, 'As of $asOf', _secondaryColor),
                  _sectionPill(context, 'Bucketed by: $basisLabel', _secondaryColor),

                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: <Widget>[
                  OutstandingViewTabs(
                    partyWise: _partyWise,
                    primaryColor: _primaryColor,
                    onChanged: (value) => setState(() => _partyWise = value),
                  ),
                  const SizedBox(height: 12),
                  if (_partyWise)
                    (partyRows.isEmpty
                        ? _empty(theme, 'No outstanding receivables')
                        : _buildPartyTable(context, partyRows, total))
                  else
                    (invoiceRows.isEmpty
                        ? _empty(theme, 'No outstanding receivables')
                        : _buildInvoiceTable(context, invoiceRows, total)),
                ],
              ),
            ),
          ],
        );
      }),
    );
  }

  List<Map<String, dynamic>> _getRows(dynamic report) {
    if (report is! Map || report['debtors'] is! List) {
      return <Map<String, dynamic>>[];
    }
    return (report['debtors'] as List)
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  Widget _empty(ThemeData theme, String text) {
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Center(
        child: Text(
          text,
          textAlign: TextAlign.center,
          style: theme.textTheme.bodyMedium?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
      ),
    );
  }

  Widget _sectionPill(BuildContext context, String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .10),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        text,
        style: Theme.of(context).textTheme.labelMedium?.copyWith(
              color: color,
              fontWeight: FontWeight.w800,
              fontSize: 12,
            ),
      ),
    );
  }

  Widget _buildInvoiceTable(
    BuildContext context,
    List<Map<String, dynamic>> rows,
    double total,
  ) {
    final tableRows = <DataRow>[
      ...List<DataRow>.generate(rows.length, (index) {
        final row = rows[index];
        final party = _party(row);
        final invoiceLabel = row['is_opening_balance'] == true
            ? '${_val(row['invoice_number'])} (OB)'
            : _val(row['invoice_number']);
        return DataRow(
          cells: <DataCell>[
            masterTextCell('${index + 1}'),
            DataCell(
              Center(
                child: ReportLinkText(
                  invoiceLabel,
                  onTap: row['invoice_id'] == null
                      ? null
                      : () => showInvoiceSettlementDetails(
                            invoiceType: 'sales',
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
                      : () => _openPartyHistory(party),
                ),
              ),
            ),
            masterTextCell(controller.formatDate(_val(row['invoice_date']))),
            masterTextCell(controller.formatDate(_val(row['due_date']))),
            masterTextCell('${row['billed_days'] ?? '-'}'),
            masterTextCell('${row['due_days'] ?? row['overdue_days'] ?? '-'}'),
            masterTextCell(controller.formatCurrency(row['invoice_total'])),
            masterTextCell(controller.formatCurrency(row['amount_paid'])),
            DataCell(
              Center(
                child: Text(
                  controller.formatCurrency(row['balance']),
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 13,
                    color: _primaryColor,
                  ),
                ),
              ),
            ),
            masterTextCell(
              controller.ageBucketLabel(row['age_bucket']?.toString()),
            ),
            DataCell(Center(child: overdueBadge(context, row))),
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
          const DataCell(SizedBox.shrink()),
          const DataCell(SizedBox.shrink()),
          DataCell(
            Center(
              child: Text(
                'Total',
                style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
              ),
            ),
          ),
          DataCell(
            Center(
              child: Text(
                controller.formatCurrency(total),
                style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
              ),
            ),
          ),
          const DataCell(SizedBox.shrink()),
          const DataCell(SizedBox.shrink()),
        ],
      ),
    ];

    final height = (42.0 + ((rows.length + 1) * 52.0)).clamp(160.0, 560.0);

    return SizedBox(
      height: height,
      child: MastersTableShell(
        isLoading: false,
        emptyText: 'No outstanding receivables',
        minWidth: 1280,
        columns: <DataColumn2>[
          masterColumn(context, '#', fixedWidth: 48),
          masterColumn(context, 'Invoice No', size: ColumnSize.L),
          masterColumn(context, 'Party', size: ColumnSize.L),
          masterColumn(context, 'Invoice Date', size: ColumnSize.M),
          masterColumn(context, 'Due Date', size: ColumnSize.M),
          masterColumn(context, 'Billed Days', fixedWidth: 72),
          masterColumn(context, 'Due Days', fixedWidth: 72),
          masterColumn(context, 'Amount', size: ColumnSize.M),
          masterColumn(context, 'Paid', size: ColumnSize.M),
          masterColumn(context, 'Balance Dr', size: ColumnSize.M),
          masterColumn(context, 'Bucket', fixedWidth: 72),
          masterColumn(context, 'Status', size: ColumnSize.M),
        ],
        rows: tableRows,
      ),
    );
  }

  Widget _buildPartyTable(
    BuildContext context,
    List<Map<String, dynamic>> rows,
    double total,
  ) {
    final tableRows = <DataRow>[
      ...List<DataRow>.generate(rows.length, (index) {
        final row = rows[index];
        final party = _party(row);
        final code = _val(party['party_code'] ?? party['code']);
        final name = _val(party['name']);
        return DataRow(
          cells: <DataCell>[
            masterTextCell('${index + 1}'),
            DataCell(
              Center(
                child: ReportLinkText(
                  code == '-' ? name : '$name / $code',
                  onTap: party['id'] == null
                      ? null
                      : () => _openPartyHistory(party),
                ),
              ),
            ),
            masterTextCell('${row['invoice_count'] ?? 0}'),
            masterTextCell(controller.formatCurrency(row['invoice_total'])),
            masterTextCell(controller.formatCurrency(row['amount_paid'])),
            DataCell(
              Center(
                child: Text(
                  controller.formatCurrency(row['balance']),
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 13,
                    color: _primaryColor,
                  ),
                ),
              ),
            ),
            masterTextCell(
              row['max_due_days'] == null ? '-' : '${row['max_due_days']}',
            ),
          ],
        );
      }),
      DataRow(
        color: reportTotalRowColor(context),
        cells: <DataCell>[
          const DataCell(SizedBox.shrink()),
          DataCell(
            Center(
              child: Text(
                'Total',
                style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
              ),
            ),
          ),
          const DataCell(SizedBox.shrink()),
          const DataCell(SizedBox.shrink()),
          const DataCell(SizedBox.shrink()),
          DataCell(
            Center(
              child: Text(
                controller.formatCurrency(total),
                style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
              ),
            ),
          ),
          const DataCell(SizedBox.shrink()),
        ],
      ),
    ];

    final height = (42.0 + ((rows.length + 1) * 52.0)).clamp(160.0, 520.0);

    return SizedBox(
      height: height,
      child: MastersTableShell(
        isLoading: false,
        emptyText: 'No outstanding receivables',
        minWidth: 900,
        columns: <DataColumn2>[
          masterColumn(context, '#', fixedWidth: 48),
          masterColumn(context, 'Party', size: ColumnSize.L),
          masterColumn(context, 'Invoices', fixedWidth: 80),
          masterColumn(context, 'Amount', size: ColumnSize.M),
          masterColumn(context, 'Paid', size: ColumnSize.M),
          masterColumn(context, 'Balance Dr', size: ColumnSize.M),
          masterColumn(context, 'Max Due Days', fixedWidth: 100),
        ],
        rows: tableRows,
      ),
    );
  }

  Map<String, dynamic> _party(Map<String, dynamic> row) {
    final party = row['party'];
    if (party is Map) return Map<String, dynamic>.from(party);
    return <String, dynamic>{};
  }

  String _val(dynamic value) {
    final text = value?.toString().trim() ?? '';
    return text.isEmpty ? '-' : text;
  }

  void _openPartyHistory(Map<String, dynamic> party) {
    final partyId = party['id'];
    if (partyId == null || partyId.toString().trim().isEmpty) {
      AppSnackbar.error('Party information is not available.');
      return;
    }
    Get.to(() => PartyHistoryScreen(partyId: partyId));
  }
}
