import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/utils/app_date_formatter.dart';
import '../../controllers/reports/balance_sheet_report_controller.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import 'ledger_report_screen.dart';
import 'widgets/report_ui_components.dart';

class BalanceSheetReportScreen extends GetView<BalanceSheetReportController> {
  const BalanceSheetReportScreen({super.key});

  static const Color _assetColor = Color(0xFF2563EB);
  static const Color _liabilityColor = Color(0xFFEF4444);
  static const Color _equityColor = Color(0xFF16A34A);

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
        if (controller.shouldShowInitialLoader) {
          return const ReportLoadingView();
        }
        final report = controller.reportData['data'];
        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle: 'Review assets, liabilities, and equity in a cleaner statement layout with faster year switching and a clearer balance check.',
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
                    onChanged: (value) => controller.applyFinancialYear(value, lookup),
                  ),

                  ReportDateRangeRow(
                    fromController: controller.fromDateController,
                    toController: controller.toDateController,
                    onFromTap: () => _pickBalanceSheetDate(context, controller.fromDateController),
                    onToTap: () => _pickBalanceSheetDate(context, controller.toDateController),
                  ),
                  const SizedBox(height: 12),
                  ReportActionBar(
                    children: <Widget>[
                      ReportPrimaryButton(
                        label: 'Filter',
                        icon: FontAwesomeIcons.sliders,
                        onTap: controller.loadReport,
                      ),
                      ReportSecondaryButton(
                        label: 'Excel',
                        icon: FontAwesomeIcons.fileExcel,
                        onTap: () => controller.exportExcel(
                          reportName: 'balance_sheet',
                          exportEndpoint: ApiEndpoints.exportBalanceSheetExcel,
                          queryParameters: controller.queryParameters,
                        ),
                      ),
                      ReportSecondaryButton(
                        label: 'PDF',
                        icon: FontAwesomeIcons.filePdf,
                        onTap: () => controller.exportPdf(
                          exportEndpoint: ApiEndpoints.exportBalanceSheetPdf,
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
              _BalanceHeadlineCard(
                assetTotal: controller.formatCurrency(
                  (report['assets'] as Map?)?['total'],
                ),
                sourceTotal: controller.formatCurrency(
                  report['total_liabilities_equity'],
                ),
                isBalanced: report['is_balanced'] == true,
                difference: ((report['total_assets'] ?? 0) is num &&
                        (report['total_liabilities_equity'] ?? 0) is num)
                    ? (((report['total_assets'] ?? 0) as num).toDouble() -
                            ((report['total_liabilities_equity'] ?? 0) as num)
                                .toDouble())
                        .abs()
                    : 0,
              ),
              const SizedBox(height: 14),
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
                          note: 'Current asset-side total for the selected year.',
                          color: _assetColor,
                          icon: FontAwesomeIcons.buildingCircleCheck,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: ReportStatCard(
                          label: 'Liabilities + Equity',
                          value: controller.formatCurrency(report['total_liabilities_equity']),
                          note: 'Combined closing position on the source side.',
                          color: _liabilityColor,
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
                          value: (report['is_balanced'] == true) ? 'Balanced' : 'Review Needed',
                          note: (report['is_balanced'] == true)
                              ? 'Assets match liabilities and equity.'
                              : 'Difference: ${controller.formatCurrency((((report['assets'] as Map?)?['total'] ?? 0) as num).toDouble() - ((report['total_liabilities_equity'] ?? 0) as num).toDouble()).replaceFirst('-', '')}',
                          color: (report['is_balanced'] == true)
                              ? _equityColor
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
            _section(context, 'Assets', report is Map<String, dynamic> ? report['assets'] : null, _assetColor),
            const SizedBox(height: 12),
            _section(context, 'Liabilities', report is Map<String, dynamic> ? report['liabilities'] : null, _liabilityColor),
            const SizedBox(height: 12),
            _section(context, 'Equity', report is Map<String, dynamic> ? report['equity'] : null, _equityColor),
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
      trailing: Wrap(
        spacing: 8,
        runSpacing: 6,
        alignment: WrapAlignment.start,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: <Widget>[
          _sectionPill(
            context,
            _dateRangeLabelForBalanceSheet(controller),
            const Color(0xFF64748B),
          ),
          _sectionPill(
            context,
            controller.formatCurrency(
              section is Map<String, dynamic> ? section['total'] : 0,
            ),
            color,
          ),
        ],
      ),
      child: accounts.isEmpty
          ? _EmptyBalanceSection(
              title: title,
              color: color,
            )
          : Container(
              decoration: BoxDecoration(
                color: Theme.of(context).cardColor,
                borderRadius: BorderRadius.circular(22),
                border: Border.all(
                  color: color.withValues(alpha: .16),
                ),
                boxShadow: <BoxShadow>[
                  BoxShadow(
                    color: color.withValues(alpha: .05),
                    blurRadius: 18,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Column(
                children: <Widget>[
                  Container(
                    padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: <Color>[
                          color.withValues(alpha: .10),
                          color.withValues(alpha: .03),
                        ],
                      ),
                      borderRadius: const BorderRadius.vertical(
                        top: Radius.circular(21),
                      ),
                    ),
                    child: Row(
                      children: <Widget>[
                        Container(
                          width: 34,
                          height: 34,
                          decoration: BoxDecoration(
                            color: color.withValues(alpha: .12),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Icon(
                            title == 'Assets'
                                ? FontAwesomeIcons.building
                                : title == 'Liabilities'
                                    ? FontAwesomeIcons.fileCircleMinus
                                    : FontAwesomeIcons.chartPie,
                            size: 15,
                            color: color,
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: <Widget>[
                              Text(
                                '$title Ledger Group',
                                style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                '${accounts.length} account${accounts.length == 1 ? '' : 's'} linked',
                                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                                ),
                              ),
                              if (accounts.isNotEmpty) ...<Widget>[
                                const SizedBox(height: 2),
                                Text(
                                  'Tap any account to open its ledger report.',
                                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                    color: color.withValues(alpha: .88),
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 7,
                          ),
                          decoration: BoxDecoration(
                            color: color.withValues(alpha: .10),
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            controller.formatCurrency(
                              section is Map<String, dynamic> ? section['total'] : 0,
                            ),
                            style: Theme.of(context).textTheme.labelLarge?.copyWith(
                              color: color,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  ...accounts.map((item) {
                    final account = item['account'];
                    final id = account is Map<String, dynamic> ? _asInt(account['id']) : null;
                    final amount = controller.formatCurrency(item['amount']);
                    final accountName = account is Map<String, dynamic>
                        ? (account['account_name'] ?? '-').toString()
                        : '-';
                    final accountType = account is Map<String, dynamic>
                        ? (account['account_type'] ?? title).toString()
                        : title;
                    return Container(
                      margin: const EdgeInsets.fromLTRB(12, 0, 12, 10),
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.surface.withValues(alpha: .82),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: Theme.of(context).dividerColor.withValues(alpha: .16),
                        ),
                      ),
                      child: ListTile(
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 14,
                          vertical: 2,
                        ),
                        title: Row(
                          children: <Widget>[
                            Expanded(
                              child: Text(
                                accountName,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.w700,
                                  color: id == null ? null : color,
                                  decoration: id == null
                                      ? null
                                      : TextDecoration.underline,
                                  decorationColor: id == null
                                      ? null
                                      : color.withValues(alpha: .45),
                                ),
                              ),
                            ),
                            if (id != null) ...<Widget>[
                              const SizedBox(width: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 4,
                                ),
                                decoration: BoxDecoration(
                                  color: color.withValues(alpha: .10),
                                  borderRadius: BorderRadius.circular(999),
                                ),
                                child: Text(
                                  'Open',
                                  style: Theme.of(context).textTheme.labelSmall?.copyWith(
                                    color: color,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                              ),
                            ],
                          ],
                        ),
                        subtitle: Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: Row(
                            children: <Widget>[
                              Expanded(
                                child: Text(
                                  id == null
                                      ? '${accountType[0].toUpperCase()}${accountType.substring(1)} ledger'
                                      : 'Tap to open ${accountType[0].toUpperCase()}${accountType.substring(1)} ledger',
                                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        trailing: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: <Widget>[
                            Text(
                              amount,
                              style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                color: color,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Icon(
                              Icons.arrow_outward_rounded,
                              size: 14,
                              color: color.withValues(alpha: .75),
                            ),
                          ],
                        ),
                        onTap: id == null
                            ? null
                            : () => Get.to(
                                  () => LedgerReportScreen(initialAccountId: id),
                                ),
                      ),
                    );
                  }),
                  Container(
                    margin: const EdgeInsets.fromLTRB(12, 0, 12, 12),
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: .08),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Row(
                      children: <Widget>[
                        Expanded(
                          child: Text(
                            'Total $title',
                            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ),
                        Text(
                          controller.formatCurrency(
                            section is Map<String, dynamic> ? section['total'] : 0,
                          ),
                          style: TextStyle(color: color, fontWeight: FontWeight.w800),
                        ),
                      ],
                    ),
                  ),
                ],
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
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: Theme.of(context).textTheme.labelMedium?.copyWith(
          color: color,
          fontWeight: FontWeight.w800,
          fontSize: 12,
        ),
      ),
    );
  }

int? _asInt(dynamic value) => int.tryParse(value?.toString() ?? '');

String _dateRangeLabelForBalanceSheet(BalanceSheetReportController controller) {
  return '${controller.formatDate(controller.fromDateController.text)} to ${controller.formatDate(controller.toDateController.text)}';
}
}

class _BalanceHeadlineCard extends StatelessWidget {
  const _BalanceHeadlineCard({
    required this.assetTotal,
    required this.sourceTotal,
    required this.isBalanced,
    required this.difference,
  });

  final String assetTotal;
  final String sourceTotal;
  final bool isBalanced;
  final double difference;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final accent = isBalanced
        ? const Color(0xFF16A34A)
        : const Color(0xFFF59E0B);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: LinearGradient(
          colors: <Color>[
            const Color(0xFFEEF4FF),
            accent.withValues(alpha: .08),
            Colors.white,
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        border: Border.all(color: accent.withValues(alpha: .18)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: accent.withValues(alpha: .12),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(
                  isBalanced
                      ? FontAwesomeIcons.shieldHeart
                      : FontAwesomeIcons.scaleBalanced,
                  size: 18,
                  color: accent,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      'Financial Position',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      isBalanced
                          ? 'Assets match liabilities and equity.'
                          : 'Difference detected between both sides.',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                        height: 1.3,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                decoration: BoxDecoration(
                  color: accent.withValues(alpha: .11),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  isBalanced ? 'Balanced' : 'Not Balanced',
                  style: theme.textTheme.labelLarge?.copyWith(
                    color: accent,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: <Widget>[
              Expanded(
                child: _MiniMetricCard(
                  label: 'Assets',
                  value: assetTotal,
                  color: const Color(0xFF2563EB),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _MiniMetricCard(
                  label: 'Liabilities + Equity',
                  value: sourceTotal,
                  color: const Color(0xFFEF4444),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            isBalanced
                ? 'Difference: 0.00'
                : 'Please review linked ledgers and closing balances.',
            style: theme.textTheme.bodySmall?.copyWith(
              color: accent,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }

}

class _MiniMetricCard extends StatelessWidget {
  const _MiniMetricCard({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .06),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: color.withValues(alpha: .14)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            label,
            style: Theme.of(context).textTheme.labelLarge?.copyWith(
              color: color,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }
}

Future<void> _pickBalanceSheetDate(
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

class _EmptyBalanceSection extends StatelessWidget {
  const _EmptyBalanceSection({
    required this.title,
    required this.color,
  });

  final String title;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .05),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: color.withValues(alpha: .14)),
      ),
      child: Column(
        children: <Widget>[
          Icon(
            Icons.inbox_outlined,
            color: color,
            size: 22,
          ),
          const SizedBox(height: 10),
          Text(
            'No $title accounts found',
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'This section will populate when related ledger accounts are available.',
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
              height: 1.35,
            ),
          ),
        ],
      ),
    );
  }
}
