import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../controllers/reports/balance_sheet_report_controller.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import 'ledger_report_screen.dart';
import 'widgets/report_ui_components.dart';

class BalanceSheetReportScreen extends GetView<BalanceSheetReportController> {
  const BalanceSheetReportScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final lookup = Get.find<ReportLookupController>();
    return Scaffold(
      appBar: AppBar(
        title: const ReportPageTitle(
          title: 'Balance Sheet',
          icon: FontAwesomeIcons.fileInvoiceDollar,
          color: Color(0xFF2563EB),
        ),
      ),
      body: Obx(() {
        final report = controller.reportData['data'];
        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle: 'Choose financial year to review assets and liabilities.',
              icon: FontAwesomeIcons.sliders,
              iconColor: const Color(0xFF2563EB),
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
                          reportName: 'balance_sheet',
                        ),
                      ),
                      ReportSecondaryButton(
                        label: 'PDF',
                        icon: FontAwesomeIcons.filePdf,
                        onTap: () => controller.exportPdf(
                          exportEndpoint: '/export/balance-sheet/pdf',
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
                          label: 'Total Assets',
                          value: controller.formatCurrency(
                            (report['assets'] as Map?)?['total'],
                          ),
                          note: 'Asset side total',
                          color: const Color(0xFF2563EB),
                          icon: FontAwesomeIcons.buildingCircleCheck,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: ReportStatCard(
                          label: 'Liabilities + Equity',
                          value: controller.formatCurrency(report['total_liabilities_equity']),
                          note: 'Source side total',
                          color: const Color(0xFFEF4444),
                          icon: FontAwesomeIcons.scaleBalanced,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: ReportStatCard(
                          label: 'Balance Status',
                          value: (report['is_balanced'] == true) ? 'Balanced' : 'Review',
                          note: (report['is_balanced'] == true)
                              ? 'Assets match liabilities and equity.'
                              : 'Difference exists',
                          color: (report['is_balanced'] == true)
                              ? const Color(0xFF16A34A)
                              : const Color(0xFFF59E0B),
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
            _section(context, 'Assets', report is Map<String, dynamic> ? report['assets'] : null, const Color(0xFF2563EB)),
            const SizedBox(height: 12),
            _section(context, 'Liabilities', report is Map<String, dynamic> ? report['liabilities'] : null, const Color(0xFFEF4444)),
            const SizedBox(height: 12),
            _section(context, 'Equity', report is Map<String, dynamic> ? report['equity'] : null, const Color(0xFF16A34A)),
          ],
        );
      }),
    );
  }

  Widget _section(BuildContext context, String title, dynamic section, Color color) {
    final accounts = section is Map<String, dynamic> && section['accounts'] is List
        ? List<Map<String, dynamic>>.from((section['accounts'] as List).whereType<Map>())
        : <Map<String, dynamic>>[];
    return ReportSectionCard(
      title: title,
      icon: title == 'Assets'
          ? FontAwesomeIcons.building
          : title == 'Liabilities'
              ? FontAwesomeIcons.fileCircleMinus
              : FontAwesomeIcons.chartPie,
      iconColor: color,
      trailing: Text(
        controller.formatCurrency(section is Map<String, dynamic> ? section['total'] : 0),
        style: Theme.of(context).textTheme.labelLarge?.copyWith(
          color: color,
          fontWeight: FontWeight.w700,
        ),
      ),
      child: accounts.isEmpty
          ? Text('No $title accounts found')
          : Column(
              children: accounts.map((item) {
                final account = item['account'];
                final id = account is Map<String, dynamic> ? _asInt(account['id']) : null;
                return ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(account is Map<String, dynamic> ? (account['account_name'] ?? '-').toString() : '-'),
                  trailing: Text(
                    controller.formatCurrency(item['amount']),
                    style: TextStyle(color: color, fontWeight: FontWeight.w700),
                  ),
                  onTap: id == null ? null : () => Get.to(() => LedgerReportScreen(initialAccountId: id)),
                );
              }).toList(),
            ),
    );
  }

  int? _asInt(dynamic value) => int.tryParse(value?.toString() ?? '');
}
