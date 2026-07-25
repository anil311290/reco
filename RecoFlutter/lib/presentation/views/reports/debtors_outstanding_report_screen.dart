import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../controllers/reports/debtors_outstanding_report_controller.dart';
import '../masters/history/party_history_screen.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'widgets/report_ui_components.dart';

class DebtorsOutstandingReportScreen
    extends GetView<DebtorsOutstandingReportController> {
  const DebtorsOutstandingReportScreen({super.key});

  static const Color _primaryColor = Color(0xFFDC2626);
  static const Color _secondaryColor = Color(0xFF2563EB);

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const ReportPageTitle(
          title: 'Receivables',
          icon: FontAwesomeIcons.handHoldingDollar,
          color: _primaryColor,
        ),
      ),
      body: Obx(() {
        final report = controller.reportData['data'];
        final rows = _getDebtorRows(report);

        final total = report is Map
            ? double.tryParse(
          report['total']?.toString() ?? '0',
        ) ??
            0.0
            : 0.0;

        return ListView(
          padding: const EdgeInsets.all(16),
          physics: const AlwaysScrollableScrollPhysics(),
          keyboardDismissBehavior:
          ScrollViewKeyboardDismissBehavior.onDrag,
          children: <Widget>[
            ReportFilterPanel(
              title: 'Export',
              subtitle: 'Download or share the receivables snapshot.',
              icon: FontAwesomeIcons.fileExport,
              iconColor: _primaryColor,
              child: ReportActionBar(
                children: <Widget>[
                  ReportSecondaryButton(
                    label: 'Excel',
                    icon: FontAwesomeIcons.fileExcel,
                    onTap: () {
                      controller.exportExcel(
                        reportName: 'debtors_outstanding',
                      );
                    },
                  ),
                  ReportSecondaryButton(
                    label: 'PDF',
                    icon: FontAwesomeIcons.filePdf,
                    onTap: () {
                      controller.exportPdf(
                        exportEndpoint:
                        '/export/debtors-outstanding/pdf',
                      );
                    },
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),

            if (report is Map)
              Row(
                children: <Widget>[
                  Expanded(
                    child: ReportStatCard(
                      label: 'Total Outstanding',
                      value: controller.formatCurrency(
                        report['total'],
                      ),
                      note: 'Open receivables',
                      color: _primaryColor,
                      icon: FontAwesomeIcons.sackDollar,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: ReportStatCard(
                      label: 'Debtors Count',
                      value: '${rows.length}',
                      note: 'Customers with balance',
                      color: _secondaryColor,
                      icon: FontAwesomeIcons.users,
                    ),
                  ),
                ],
              ),

            if (report is Map) const SizedBox(height: 12),

            ReportSectionCard(
              title: 'Outstanding Debtors',
              icon: FontAwesomeIcons.tableList,
              iconColor: _primaryColor,
              child: rows.isEmpty
                  ? _buildEmptyState(theme)
                  : _buildDebtorsTable(
                context: context,
                theme: theme,
                rows: rows,
                total: total,
              ),
            ),
          ],
        );
      }),
    );
  }

  List<Map<String, dynamic>> _getDebtorRows(dynamic report) {
    if (report is! Map || report['debtors'] is! List) {
      return <Map<String, dynamic>>[];
    }

    return (report['debtors'] as List)
        .whereType<Map>()
        .map(
          (item) => Map<String, dynamic>.from(item),
    )
        .toList();
  }

  Widget _buildEmptyState(ThemeData theme) {
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Center(
        child: Text(
          'No outstanding receivables',
          textAlign: TextAlign.center,
          style: theme.textTheme.bodyMedium?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
      ),
    );
  }

  Widget _buildDebtorsTable({
    required BuildContext context,
    required ThemeData theme,
    required List<Map<String, dynamic>> rows,
    required double total,
  }) {
    final tableRows = <DataRow>[
      ...List<DataRow>.generate(
        rows.length,
            (index) {
          final row = rows[index];
          final party = _getParty(row);

          return DataRow(
            cells: <DataCell>[
              masterTextCell('${index + 1}'),

              masterTextCell(
                _getValue(party['name']),
              ),

              masterTextCell(
                _getValue(party['mobile']),
              ),

              DataCell(
                Center(
                  child: Tooltip(
                    message: _getValue(party['email']),
                    child: Text(
                      _getValue(party['email']),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                      ),
                    ),
                  ),
                ),
              ),

              DataCell(
                Center(
                  child: Text(
                    controller.formatCurrency(row['balance']),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                      color: _primaryColor,
                    ),
                  ),
                ),
              ),

              DataCell(
                Center(
                  child: MasterActionButton(
                    icon: Icons.receipt_long_outlined,
                    tooltip: 'History',
                    color: _secondaryColor,
                    onTap: () {
                      _openPartyHistory(party);
                    },
                  ),
                ),
              ),
            ],
          );
        },
      ),

      _buildTotalRow(
        theme: theme,
        total: total,
      ),
    ];

    /*
     * MastersTableShell internally Expanded/Flexible use nahi karta,
     * lekin DataTable2 ko bounded height chahiye.
     *
     * 42 = heading height
     * 52 = each data row height
     * rows.length + 1 = debtor rows + total row
     */
    final calculatedHeight =
        42.0 + ((rows.length + 1) * 52.0);

    final tableHeight = calculatedHeight.clamp(
      160.0,
      550.0,
    );

    return SizedBox(
      height: tableHeight,
      child: MastersTableShell(
        isLoading: false,
        emptyText: 'No outstanding receivables',
        minWidth: 900,
        columns: <DataColumn2>[
          masterColumn(
            context,
            '#',
            fixedWidth: 50,
          ),
          masterColumn(
            context,
            'Party',
            size: ColumnSize.L,
          ),
          masterColumn(
            context,
            'Mobile',
            size: ColumnSize.M,
          ),
          masterColumn(
            context,
            'Email',
            size: ColumnSize.L,
          ),
          masterColumn(
            context,
            'Balance (₹) Dr',
            size: ColumnSize.M,
          ),
          masterColumn(
            context,
            'History',
            fixedWidth: 100,
          ),
        ],
        rows: tableRows,
      ),
    );
  }

  Map<String, dynamic> _getParty(
      Map<String, dynamic> row,
      ) {
    final party = row['party'];

    if (party is Map) {
      return Map<String, dynamic>.from(party);
    }

    return <String, dynamic>{};
  }

  String _getValue(dynamic value) {
    final text = value?.toString().trim() ?? '';

    return text.isEmpty ? '-' : text;
  }

  void _openPartyHistory(
      Map<String, dynamic> party,
      ) {
    final partyId = party['id'];

    if (partyId == null ||
        partyId.toString().trim().isEmpty) {
      Get.snackbar(
        'Unable to open history',
        'Party information is not available.',
        snackPosition: SnackPosition.BOTTOM,
      );
      return;
    }

    Get.to(() => PartyHistoryScreen(partyId: partyId));
  }

  DataRow _buildTotalRow({
    required ThemeData theme,
    required double total,
  }) {
    return DataRow(
      color: WidgetStatePropertyAll(
        theme.colorScheme.surfaceContainerHighest.withValues(
          alpha: .25,
        ),
      ),
      cells: <DataCell>[
        const DataCell(
          SizedBox.shrink(),
        ),
        const DataCell(
          SizedBox.shrink(),
        ),
        const DataCell(
          SizedBox.shrink(),
        ),
        DataCell(
          Center(
            child: Text(
              'Total Outstanding',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 13,
                color: theme.colorScheme.onSurface,
              ),
            ),
          ),
        ),
        DataCell(
          Center(
            child: Text(
              controller.formatCurrency(total),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 13,
                color: _primaryColor,
              ),
            ),
          ),
        ),
        const DataCell(
          SizedBox.shrink(),
        ),
      ],
    );
  }
}
