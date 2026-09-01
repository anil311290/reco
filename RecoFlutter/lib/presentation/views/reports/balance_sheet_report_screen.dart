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
  static const String _suspenseCode = '1000';

  @override
  Widget build(BuildContext context) {
    final lookup = Get.find<ReportLookupController>();
    return Scaffold(
      appBar: AppBar(
        title: ReportPageTitle(
          title: 'Balance Sheet',
          icon: FontAwesomeIcons.fileInvoiceDollar.data,
          color: _assetColor,
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
              subtitle:
                  'Review assets, liabilities, and equity in a cleaner statement layout with faster year switching and a clearer balance check.',
              icon: FontAwesomeIcons.sliders.data,
              iconColor: _assetColor,
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
                    onChanged: (value) =>
                        controller.applyFinancialYear(value, lookup),
                  ),
                  CustomTextField(
                    label: 'As of Date',
                    controller: controller.asOfDateController,
                    readOnly: true,
                    suffixIcon: Icons.edit_calendar_rounded,
                    onTap: () => _pickBalanceSheetDate(
                      context,
                      controller.asOfDateController,
                    ),
                    bottomPadding: 12,
                  ),
                  ReportActionBar(
                    children: <Widget>[
                      ReportPrimaryButton(
                        label: 'Apply',
                        icon: FontAwesomeIcons.filter.data,
                        onTap: controller.loadReport,
                      ),
                      ReportSecondaryButton(
                        label: 'Excel',
                        icon: FontAwesomeIcons.fileExcel.data,
                        onTap: () => controller.exportExcel(
                          reportName: 'balance_sheet',
                          exportEndpoint: ApiEndpoints.exportBalanceSheetExcel,
                          queryParameters: controller.queryParameters,
                        ),
                      ),
                      ReportSecondaryButton(
                        label: 'PDF',
                        icon: FontAwesomeIcons.filePdf.data,
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
            if (report is! Map<String, dynamic>)
              ReportSectionCard(
                title: 'No financial year found',
                icon: FontAwesomeIcons.calendarXmark.data,
                iconColor: _assetColor,
                child: const Padding(
                  padding: EdgeInsets.all(16),
                  child: Text(
                    'Create a financial year first to generate a balance sheet.',
                  ),
                ),
              )
            else ...<Widget>[
              ReportSectionCard(
                title: 'Balance Sheet',
                icon: FontAwesomeIcons.tableColumns.data,
                iconColor: _assetColor,
                trailing: _pill(
                  context,
                  'As of ${controller.formatDate(controller.asOfDateController.text)}',
                  const Color(0xFF0EA5E9),
                ),
                child: Column(
                  children: <Widget>[
                    _buildSideTable(
                      context,
                      title: 'Liabilities',
                      color: _liabilityColor,
                      rows: _buildLiabilityRows(report),
                      totalLabel: 'Total Liabilities',
                      totalAmount: _asDouble(report['total_liabilities_equity']),
                      emptyText:
                          'No liabilities, equity, or assets recorded',
                    ),
                    const SizedBox(height: 16),
                    _buildSideTable(
                      context,
                      title: 'Assets',
                      color: _assetColor,
                      rows: _buildAssetRows(report),
                      totalLabel: 'Total Assets',
                      totalAmount: _asDouble(
                        (report['assets'] as Map?)?['total'],
                      ),
                      emptyText:
                          'No liabilities, equity, or assets recorded',
                    ),
                  ],
                ),
              ),
            ],
          ],
        );
      }),
    );
  }

  List<Map<String, dynamic>> _buildLiabilityRows(
    Map<String, dynamic> report,
  ) {
    final rows = <Map<String, dynamic>>[];

    final equity = report['equity'];
    if (equity is Map<String, dynamic> && equity['accounts'] is List) {
      for (final item in (equity['accounts'] as List).whereType<Map>()) {
        rows.add(_rowFromAccountItem(item));
      }
    }

    rows.add(<String, dynamic>{
      'account': null,
      'label': 'Net Profit / Loss',
      'amount': _asDouble(
        equity is Map<String, dynamic> ? equity['net_profit'] : 0,
      ),
    });

    final liabilities = report['liabilities'];
    if (liabilities is Map<String, dynamic> && liabilities['accounts'] is List) {
      for (final item in (liabilities['accounts'] as List).whereType<Map>()) {
        rows.add(_rowFromAccountItem(item));
      }
    }

    return rows;
  }

  List<Map<String, dynamic>> _buildAssetRows(Map<String, dynamic> report) {
    final assets = report['assets'];
    if (assets is! Map<String, dynamic> || assets['accounts'] is! List) {
      return <Map<String, dynamic>>[];
    }
    return (assets['accounts'] as List)
        .whereType<Map>()
        .map(_rowFromAccountItem)
        .toList();
  }

  Map<String, dynamic> _rowFromAccountItem(Map<dynamic, dynamic> item) {
    final account = item['account'];
    final label = _accountLabel(account);
    return <String, dynamic>{
      'account': account,
      'label': label,
      'amount': _asDouble(item['amount']),
    };
  }

  String _accountLabel(dynamic account) {
    if (account is! Map) {
      return '-';
    }
    final code = (account['account_code'] ?? '').toString();
    if (code == _suspenseCode) {
      return 'Opening balance difference';
    }
    return (account['account_name'] ?? '-').toString();
  }

  bool _isLinkableAccount(dynamic account) {
    if (account is! Map) {
      return false;
    }
    return (account['account_code'] ?? '').toString() != _suspenseCode;
  }

  Widget _buildSideTable(
    BuildContext context, {
    required String title,
    required Color color,
    required List<Map<String, dynamic>> rows,
    required String totalLabel,
    required double totalAmount,
    required String emptyText,
  }) {
    if (rows.isEmpty) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          _sideHeader(context, title, color),
          const SizedBox(height: 10),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 24),
            child: Center(
              child: Text(
                emptyText,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
            ),
          ),
        ],
      );
    }

    final tableRows = rows;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        _sideHeader(context, title, color),
        const SizedBox(height: 10),
        Container(
          width: double.infinity,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(10),
            border: Border.all(
              color: Theme.of(context).dividerColor.withValues(alpha: .55),
            ),
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: Column(
              children: <Widget>[
                _tableHeaderRow(context, title, color),
                ...tableRows.map(
                  (row) => _tableDataRow(context, row),
                ),
                _tableTotalRow(
                  context,
                  totalLabel: totalLabel,
                  totalAmount: totalAmount,
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _tableHeaderRow(BuildContext context, String title, Color color) {
    final headerStyle = Theme.of(context).textTheme.labelMedium?.copyWith(
      fontWeight: FontWeight.w800,
      color: color,
    );
    return Container(
      color: Theme.of(context)
          .colorScheme
          .surfaceContainerHighest
          .withValues(alpha: .45),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      child: Row(
        children: <Widget>[
          Expanded(
            child: Text(title, textAlign: TextAlign.center, style: headerStyle),
          ),
          SizedBox(
            width: 132,
            child: Text('Amount', textAlign: TextAlign.center, style: headerStyle),
          ),
        ],
      ),
    );
  }

  Widget _tableDataRow(BuildContext context, Map<String, dynamic> row) {
    final account = row['account'];
    final label = (row['label'] ?? '-').toString();
    final accountId =
        account is Map<String, dynamic> ? _asInt(account['id']) : null;
    final isBalancing = account == null;
    final canLink =
        !isBalancing && _isLinkableAccount(account) && accountId != null;

    return Container(
      decoration: BoxDecoration(
        border: Border(
          top: BorderSide(
            color: Theme.of(context).dividerColor.withValues(alpha: .35),
          ),
        ),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Expanded(
            child: Center(
              child: canLink
                  ? ReportLinkText(
                      label,
                      onTap: () => Get.to(
                        () => LedgerReportScreen(initialAccountId: accountId),
                      ),
                    )
                  : Text(
                      label,
                      textAlign: TextAlign.center,
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
            ),
          ),
          SizedBox(
            width: 132,
            child: Text(
              controller.formatCurrency(row['amount']),
              textAlign: TextAlign.center,
              style: TextStyle(
                fontWeight: isBalancing ? FontWeight.w800 : FontWeight.w700,
                fontSize: 13,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _tableTotalRow(
    BuildContext context, {
    required String totalLabel,
    required double totalAmount,
  }) {
    final totalStyle = reportTotalRowTextStyle(context);
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
      color: const Color(0xFF23263A),
      child: Row(
        children: <Widget>[
          Expanded(
            child: Text(
              totalLabel,
              textAlign: TextAlign.center,
              style: totalStyle,
            ),
          ),
          SizedBox(
            width: 132,
            child: Text(
              controller.formatCurrency(totalAmount),
              textAlign: TextAlign.center,
              style: totalStyle?.copyWith(fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }

  Widget _sideHeader(BuildContext context, String title, Color color) {
    return Text(
      title,
      style: Theme.of(context).textTheme.titleSmall?.copyWith(
        color: color,
        fontWeight: FontWeight.w800,
        letterSpacing: .2,
      ),
    );
  }

  Widget _pill(BuildContext context, String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .10),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: Theme.of(context).textTheme.labelMedium?.copyWith(
          color: color,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }

  int? _asInt(dynamic value) => int.tryParse(value?.toString() ?? '');

  double _asDouble(dynamic value) {
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value?.toString() ?? '') ?? 0;
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
