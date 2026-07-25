import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../controllers/reports/profit_loss_report_controller.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import 'ledger_report_screen.dart';
import 'widgets/report_ui_components.dart';

class ProfitLossReportScreen extends GetView<ProfitLossReportController> {
  const ProfitLossReportScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final lookup = Get.find<ReportLookupController>();
    return Scaffold(
      appBar: AppBar(
        title: const ReportPageTitle(
          title: 'Profit & Loss',
          icon: FontAwesomeIcons.chartLine,
          color: Color(0xFF16A34A),
        ),
      ),
      body: Obx(() {
        final report = controller.reportData['data'];
        final income = report is Map<String, dynamic> ? report['income'] : null;
        final expense = report is Map<String, dynamic> ? report['expense'] : null;
        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle: 'Choose financial year to review profitability.',
              icon: FontAwesomeIcons.sliders,
              iconColor: const Color(0xFF16A34A),
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
                          reportName: 'profit_loss',
                        ),
                      ),
                      ReportSecondaryButton(
                        label: 'PDF',
                        icon: FontAwesomeIcons.filePdf,
                        onTap: () => controller.exportPdf(
                          exportEndpoint: '/export/profit-loss/pdf',
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
                          label: 'Total Income',
                          value: controller.formatCurrency(
                            income is Map<String, dynamic> ? income['total'] : 0,
                          ),
                          note: 'Revenue recorded',
                          color: const Color(0xFF16A34A),
                          icon: FontAwesomeIcons.sackDollar,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: ReportStatCard(
                          label: 'Total Expenses',
                          value: controller.formatCurrency(
                            expense is Map<String, dynamic> ? expense['total'] : 0,
                          ),
                          note: 'Expense heads in period',
                          color: const Color(0xFFEF4444),
                          icon: FontAwesomeIcons.moneyBillTransfer,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: ReportStatCard(
                          label: 'Net Result',
                          value: '${(report['is_profit'] == true) ? '+' : '-'}${controller.formatCurrency((report['net_profit'] as num?)?.abs() ?? 0)}',
                          note: (report['is_profit'] == true) ? 'Profit' : 'Loss',
                          color: (report['is_profit'] == true)
                              ? const Color(0xFF2563EB)
                              : const Color(0xFFF59E0B),
                          icon: (report['is_profit'] == true)
                              ? FontAwesomeIcons.chartSimple
                              : FontAwesomeIcons.chartColumn,
                        ),
                      ),
                      const Spacer(),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 12),
            ],
            _accountSection(
              context,
              title: 'Income',
              color: const Color(0xFF16A34A),
              section: income,
            ),
            const SizedBox(height: 12),
            _accountSection(
              context,
              title: 'Expenses',
              color: const Color(0xFFEF4444),
              section: expense,
            ),
          ],
        );
      }),
    );
  }

  Widget _accountSection(
    BuildContext context, {
    required String title,
    required Color color,
    required dynamic section,
  }) {
    final accounts = section is Map<String, dynamic> && section['accounts'] is List
        ? List<Map<String, dynamic>>.from((section['accounts'] as List).whereType<Map>())
        : <Map<String, dynamic>>[];
    return ReportSectionCard(
      title: title,
      icon: title == 'Income'
          ? FontAwesomeIcons.coins
          : FontAwesomeIcons.fileInvoiceDollar,
      iconColor: color,
      trailing: Text(
        controller.formatCurrency(section is Map<String, dynamic> ? section['total'] : 0),
        style: Theme.of(context).textTheme.labelLarge?.copyWith(
          color: color,
          fontWeight: FontWeight.w700,
        ),
      ),
      child: accounts.isEmpty
          ? Text('No $title recorded')
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
