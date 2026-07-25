import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../controllers/reports/report_lookup_controller.dart';
import '../../controllers/reports/trial_balance_report_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'ledger_report_screen.dart';
import 'widgets/report_ui_components.dart';

class TrialBalanceReportScreen extends GetView<TrialBalanceReportController> {
  const TrialBalanceReportScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final lookup = Get.find<ReportLookupController>();
    return Scaffold(
      appBar: AppBar(
        title: const ReportPageTitle(
          title: 'Trial Balance',
          icon: FontAwesomeIcons.scaleBalanced,
          color: Color(0xFFD97706),
        ),
      ),
      body: Obx(() {
        final report = controller.reportData['data'];
        final accounts = report is Map<String, dynamic> && report['accounts'] is List
            ? List<Map<String, dynamic>>.from((report['accounts'] as List).whereType<Map>())
            : <Map<String, dynamic>>[];
        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle: 'Pick financial year to compare debit and credit totals.',
              icon: FontAwesomeIcons.sliders,
              iconColor: const Color(0xFFD97706),
              child: Column(
                children: <Widget>[
                  CustomDropdown<int>(
                    label: 'Financial Year',
                    value: controller.financialYearId.value,
                    items: lookup.financialYears.map((e) => _asInt(e['id'])).whereType<int>().toList(),
                    itemLabelBuilder: (value) {
                      final item = lookup.financialYears.firstWhere(
                        (fy) => _asInt(fy['id']) == value,
                        orElse: () => <String, dynamic>{},
                      );
                      return (item['name'] ?? 'FY').toString();
                    },
                    onChanged: (value) => controller.financialYearId.value = value,
                  ),
                  ReportActionBar(
                    children: <Widget>[
                      ReportPrimaryButton(
                        label: 'Filter',
                        icon: FontAwesomeIcons.filter,
                        onTap: controller.loadReport,
                      ),
                      ReportSecondaryButton(
                        label: 'Excel',
                        icon: FontAwesomeIcons.fileExcel,
                        onTap: () => controller.exportExcel(
                          reportName: 'trial_balance',
                        ),
                      ),
                      ReportSecondaryButton(
                        label: 'PDF',
                        icon: FontAwesomeIcons.filePdf,
                        onTap: () => controller.exportPdf(
                          exportEndpoint: '/export/trial-balance/pdf',
                          queryParameters: controller.queryParameters,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            if (report is Map<String, dynamic>) ...<Widget>[
              Column(
                children: <Widget>[
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: ReportStatCard(
                          label: 'Total Debit',
                          value: controller.formatCurrency(report['total_debit']),
                          note: 'Debit side total',
                          color: const Color(0xFF2563EB),
                          icon: FontAwesomeIcons.arrowTrendUp,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: ReportStatCard(
                          label: 'Total Credit',
                          value: controller.formatCurrency(report['total_credit']),
                          note: 'Credit side total',
                          color: const Color(0xFFF59E0B),
                          icon: FontAwesomeIcons.arrowTrendDown,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: ReportStatCard(
                          label: 'Status',
                          value: (report['is_balanced'] == true) ? 'Balanced' : 'Mismatch',
                          note: (report['is_balanced'] == true)
                              ? 'Trial balance is closed cleanly.'
                              : 'Difference exists',
                          color: (report['is_balanced'] == true)
                              ? const Color(0xFF16A34A)
                              : const Color(0xFFEF4444),
                          icon: (report['is_balanced'] == true)
                              ? FontAwesomeIcons.circleCheck
                              : FontAwesomeIcons.triangleExclamation,
                        ),
                      ),
                      const Spacer(),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 12),
            ],
            ReportSectionCard(
              title: 'Account Balances',
              icon: FontAwesomeIcons.tableList,
              iconColor: const Color(0xFFD97706),
              child: accounts.isEmpty
                  ? const Text('No accounts found')
                  : _buildTrialBalanceTable(context, report, accounts),
            ),
          ],
        );
      }),
    );
  }

  Widget _buildTrialBalanceTable(
    BuildContext context,
    dynamic reportData,
    List<Map<String, dynamic>> accounts,
  ) {
    if (reportData is! Map) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(24),
          child: Text('No accounts found'),
        ),
      );
    }

    final tableRows = <DataRow>[
      ...List<DataRow>.generate(accounts.length, (index) {
        final item = accounts[index];
        final account = item['account'];
        final accountId = account is Map<String, dynamic> ? _asInt(account['id']) : null;
        return DataRow(
          cells: <DataCell>[
            DataCell(Center(
              child: InkWell(
                onTap: accountId == null ? null : () => Get.to(() => LedgerReportScreen(initialAccountId: accountId)),
                child: Text(
                  account is Map<String, dynamic> ? (account['account_code'] ?? '-').toString() : '-',
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 13),
                ),
              ),
            )),
            masterTextCell(account is Map<String, dynamic> ? (account['account_name'] ?? '-').toString() : '-'),
            masterTextCell(account is Map<String, dynamic> ? (account['account_type'] ?? '-').toString() : '-'),
            DataCell(Center(child: Text(controller.formatCurrency(item['debit']), textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)))),
            DataCell(Center(child: Text(controller.formatCurrency(item['credit']), textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)))),
          ],
        );
      }),
    ];

    final calculatedHeight = 42.0 + (accounts.length * 52.0);
    final tableHeight = calculatedHeight.clamp(160.0, 550.0);

    return SizedBox(
      height: tableHeight,
      child: MastersTableShell(
        isLoading: false,
        emptyText: 'No accounts found',
        minWidth: 860,
        columns: <DataColumn2>[
          masterColumn(context, 'Code'),
          masterColumn(context, 'Account Name', size: ColumnSize.L),
          masterColumn(context, 'Type'),
          masterColumn(context, 'Debit'),
          masterColumn(context, 'Credit'),
        ],
        rows: tableRows,
      ),
    );
  }

  int? _asInt(dynamic value) => int.tryParse(value?.toString() ?? '');
}
