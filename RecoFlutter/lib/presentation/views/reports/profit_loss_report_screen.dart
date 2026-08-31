import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/utils/app_date_formatter.dart';
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
        title: ReportPageTitle(
          title: 'Profit & Loss Statement',
          icon: FontAwesomeIcons.chartLine.data,
          color: Color(0xFF16A34A),
        ),
      ),
      body: Obx(() {
        if (controller.shouldShowInitialLoader) {
          return const ReportLoadingView();
        }
        final report = controller.reportData['data'];
        final income = report is Map<String, dynamic> ? report['income'] : null;
        final expense = report is Map<String, dynamic> ? report['expense'] : null;
        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle: 'Track income, expenses, and final profitability with a cleaner statement layout built for fast management review.',
              icon: FontAwesomeIcons.sliders.data,
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
                          reportName: 'profit_loss',
                          exportEndpoint: ApiEndpoints.exportProfitLossExcel,
                          queryParameters: controller.queryParameters,
                        ),
                      ),
                      ReportSecondaryButton(
                        label: 'PDF',
                        icon: FontAwesomeIcons.filePdf.data,
                        onTap: () => controller.exportPdf(
                          exportEndpoint: ApiEndpoints.exportProfitLossPdf,
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
              _summaryCards(context, report, income, expense),
              const SizedBox(height: 12),
              LayoutBuilder(
                builder: (BuildContext context, BoxConstraints constraints) {
                  final compact = constraints.maxWidth < 720;
                  final sections = <Widget>[
                    _accountSection(
                      context,
                      title: 'Expenses',
                      color: const Color(0xFFEF4444),
                      section: expense,
                    ),
                    _accountSection(
                      context,
                      title: 'Income',
                      color: const Color(0xFF16A34A),
                      section: income,
                    ),
                  ];
                  if (compact) {
                    return Column(
                      children: <Widget>[
                        sections.first,
                        const SizedBox(height: 12),
                        sections.last,
                      ],
                    );
                  }
                  return Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Expanded(child: sections.first),
                      const SizedBox(width: 12),
                      Expanded(child: sections.last),
                    ],
                  );
                },
              ),
              const SizedBox(height: 12),
              _netResultBar(context, report),
            ],
          ],
        );
      }),
    );
  }

  Widget _summaryCards(
    BuildContext context,
    Map<String, dynamic> report,
    dynamic income,
    dynamic expense,
  ) {
    final isProfit = report['is_profit'] == true;
    final netProfit = _asDouble(report['net_profit']).abs();
    final cards = <Widget>[
      ReportStatCard(
        label: 'Total Income',
        value: controller.formatCurrency(
          income is Map<String, dynamic> ? income['total'] : 0,
        ),
        note: 'Revenue and income recorded in the selected financial year.',
        color: const Color(0xFF16A34A),
        icon: FontAwesomeIcons.sackDollar.data,
      ),
      ReportStatCard(
        label: 'Total Expenses',
        value: controller.formatCurrency(
          expense is Map<String, dynamic> ? expense['total'] : 0,
        ),
        note: 'All expense heads included in this period.',
        color: const Color(0xFFEF4444),
        icon: FontAwesomeIcons.moneyBillTransfer.data,
      ),
      ReportStatCard(
        label: 'Net Result',
        value: '${isProfit ? '+' : '-'}${controller.formatCurrency(netProfit)}',
        note: isProfit
            ? 'Profit recorded for this period.'
            : 'Loss recorded for this period.',
        color: isProfit ? const Color(0xFF2563EB) : const Color(0xFFF59E0B),
        icon: isProfit ? FontAwesomeIcons.chartSimple.data : FontAwesomeIcons.chartColumn.data,
      ),
    ];

    return LayoutBuilder(
      builder: (BuildContext context, BoxConstraints constraints) {
        final compact = constraints.maxWidth < 720;
        if (compact) {
          return Column(
            children: <Widget>[
              Row(
                children: <Widget>[
                  Expanded(child: cards[0]),
                  const SizedBox(width: 10),
                  Expanded(child: cards[1]),
                ],
              ),
              const SizedBox(height: 10),
              cards[2],
            ],
          );
        }
        return Row(
          children: <Widget>[
            Expanded(child: cards[0]),
            const SizedBox(width: 12),
            Expanded(child: cards[1]),
            const SizedBox(width: 12),
            Expanded(child: cards[2]),
          ],
        );
      },
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
    final theme = Theme.of(context);
    final total = section is Map<String, dynamic> ? section['total'] : 0;
    return ReportSectionCard(
      title: title,
      icon: title == 'Income'
          ? FontAwesomeIcons.circleArrowDown.data
          : FontAwesomeIcons.circleArrowUp.data,
      iconColor: color,
      trailing: Wrap(
        spacing: 8,
        runSpacing: 6,
        alignment: WrapAlignment.end,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: <Widget>[
          _pill(context, _dateRangeLabel(), const Color(0xFF0EA5E9)),
          _pill(context, controller.formatCurrency(total), color),
        ],
      ),
      child: Container(
        clipBehavior: Clip.antiAlias,
        decoration: BoxDecoration(
          color: theme.cardColor,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(
            color: theme.dividerColor.withValues(alpha: .35),
          ),
        ),
        child: Column(
          children: <Widget>[
            if (accounts.isEmpty)
              SizedBox(
                width: double.infinity,
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 18),
                  child: Text(
                    title == 'Income'
                        ? 'No income recorded'
                        : 'No expenses recorded',
                    textAlign: TextAlign.center,
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ),
              )
            else
              ...accounts.map((item) => _accountRow(context, item, color)),
            _totalRow(context, title, total, color),
          ],
        ),
      ),
    );
  }

  Widget _accountRow(
    BuildContext context,
    Map<String, dynamic> item,
    Color color,
  ) {
    final theme = Theme.of(context);
    final account = item['account'];
    final id = account is Map<String, dynamic> ? _asInt(account['id']) : null;
    final name = account is Map<String, dynamic>
        ? (account['account_name'] ?? '-').toString()
        : (item['label'] ?? '-').toString();
    return InkWell(
      onTap: id == null ? null : () => Get.to(() => LedgerReportScreen(initialAccountId: id)),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        decoration: BoxDecoration(
          border: Border(
            bottom: BorderSide(
              color: theme.dividerColor.withValues(alpha: .20),
            ),
          ),
        ),
        child: Row(
          children: <Widget>[
            Expanded(
              child: Text(
                name,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: id == null ? theme.colorScheme.onSurface : color,
                  fontWeight: FontWeight.w800,
                  decoration: id == null ? null : TextDecoration.underline,
                  decorationColor: color.withValues(alpha: .45),
                ),
              ),
            ),
            const SizedBox(width: 10),
            Text(
              controller.formatCurrency(item['amount']),
              style: theme.textTheme.bodyMedium?.copyWith(
                color: color,
                fontWeight: FontWeight.w800,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _totalRow(
    BuildContext context,
    String title,
    dynamic total,
    Color color,
  ) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 13),
      color: const Color(0xFF23263A),
      child: Row(
        children: <Widget>[
          Expanded(
            child: Text(
              'Total $title',
              style: theme.textTheme.bodyMedium?.copyWith(
                color: Colors.white,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          Text(
            controller.formatCurrency(total),
            style: theme.textTheme.bodyMedium?.copyWith(
              color: title == 'Income' ? const Color(0xFF22C55E) : const Color(0xFFFF5A6A),
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }

  Widget _netResultBar(BuildContext context, Map<String, dynamic> report) {
    final theme = Theme.of(context);
    final isProfit = report['is_profit'] == true;
    final color = isProfit ? const Color(0xFF16A34A) : const Color(0xFFEF4444);
    final label = isProfit ? 'Net Profit' : 'Net Loss';
    final amount = '${isProfit ? '+' : '-'}${controller.formatCurrency(_asDouble(report['net_profit']).abs())}';
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(
          color: theme.dividerColor.withValues(alpha: .20),
        ),
      ),
      child: Wrap(
        spacing: 10,
        runSpacing: 10,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: <Widget>[
          _pill(
            context,
            '$label: $amount',
            const Color(0xFF6366F1),
            icon: FontAwesomeIcons.calculator.data,
          ),
          _pill(
            context,
            isProfit ? 'Profitable' : 'Loss Position',
            color,
            icon: isProfit
                ? FontAwesomeIcons.circleCheck.data
                : FontAwesomeIcons.circleExclamation.data,
          ),
        ],
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
      padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
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

  int? _asInt(dynamic value) => int.tryParse(value?.toString() ?? '');

  double _asDouble(dynamic value) {
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value?.toString() ?? '') ?? 0;
  }

  String _dateRangeLabel() {
    return '${controller.formatDate(controller.fromDateController.text)} to ${controller.formatDate(controller.toDateController.text)}';
  }
}
