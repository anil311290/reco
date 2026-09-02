import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/utils/app_date_formatter.dart';
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
        title:  ReportPageTitle(
          title: 'Trial Balance',
          icon: FontAwesomeIcons.scaleBalanced.data,
          color: Color(0xFFD97706),
        ),
      ),
      body: Obx(() {
        if (controller.shouldShowInitialLoader) {
          return const ReportLoadingView();
        }
        final report = controller.reportData['data'];
        final accounts = report is Map<String, dynamic> && report['accounts'] is List
            ? List<Map<String, dynamic>>.from((report['accounts'] as List).whereType<Map>())
            : <Map<String, dynamic>>[];
        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle: 'Basis for Balance Sheet and Profit & Loss - closing debit/credit balances for each ledger.',
              icon: FontAwesomeIcons.sliders.data,
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
                          reportName: 'trial_balance',
                          exportEndpoint: ApiEndpoints.exportTrialBalanceExcel,
                          queryParameters: controller.queryParameters,
                        ),
                      ),
                      ReportSecondaryButton(
                        label: 'PDF',
                        icon: FontAwesomeIcons.filePdf.data,
                        onTap: () => controller.exportPdf(
                          exportEndpoint: ApiEndpoints.exportTrialBalancePdf,
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
                          label: 'Closing Dr',
                          value: controller.formatCurrency(report['total_debit']),
                          note: 'Must equal closing credit when books tally.',
                          color: const Color(0xFF2563EB),
                          icon: FontAwesomeIcons.arrowTrendUp.data,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: ReportStatCard(
                          label: 'Closing Cr',
                          value: controller.formatCurrency(report['total_credit']),
                          note: 'Closing balances across all ledgers.',
                          color: const Color(0xFFF59E0B),
                          icon: FontAwesomeIcons.arrowTrendDown.data,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),

                ],
              ),
              const SizedBox(height: 12),
            ],
            ReportSectionCard(
              title: 'Account Balances',
              icon: FontAwesomeIcons.tableList.data,
              iconColor: const Color(0xFFD97706),
              trailing: report is Map<String, dynamic>
                  ? _buildSectionMeta(context, report)
                  : null,
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
        final opening = _asDouble(item['opening_balance']);
        final openingType = (item['opening_type'] ?? '').toString();
        final txnDebit = _asDouble(item['transaction_debit'] ?? item['debit']);
        final txnCredit = _asDouble(item['transaction_credit'] ?? item['credit']);
        final closingDebit = _asDouble(item['closing_debit'] ?? item['debit']);
        final closingCredit = _asDouble(item['closing_credit'] ?? item['credit']);
        return DataRow(
          cells: <DataCell>[
            DataCell(
              Center(
                child: ReportLinkText(
                  account is Map<String, dynamic>
                      ? (account['account_code'] ?? '-').toString()
                      : '-',
                  onTap: accountId == null
                      ? null
                      : () => Get.to(
                            () => LedgerReportScreen(initialAccountId: accountId),
                          ),
                ),
              ),
            ),
            DataCell(
              Center(
                child: ReportLinkText(
                  account is Map<String, dynamic>
                      ? (account['account_name'] ?? '-').toString()
                      : '-',
                  onTap: accountId == null
                      ? null
                      : () => Get.to(
                            () => LedgerReportScreen(initialAccountId: accountId),
                          ),
                ),
              ),
            ),
            DataCell(
              Center(
                child: _typeChip(
                  context,
                  (item['type'] ??
                          (account is Map<String, dynamic>
                              ? account['account_type']
                              : null) ??
                          '-')
                      .toString(),
                ),
              ),
            ),
            masterTextCell(
              opening > 0
                  ? '${controller.formatCurrency(opening)} ${openingType == 'credit' ? 'Cr' : 'Dr'}'
                  : '-',
            ),
            DataCell(
              Center(
                child: Text(
                  txnDebit > 0 ? controller.formatCurrency(txnDebit) : '-',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                    color: Color(0xFF2563EB),
                  ),
                ),
              ),
            ),
            DataCell(
              Center(
                child: Text(
                  txnCredit > 0 ? controller.formatCurrency(txnCredit) : '-',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                    color: Color(0xFFF59E0B),
                  ),
                ),
              ),
            ),
            masterTextCell(
              closingDebit > 0
                  ? '${controller.formatCurrency(closingDebit)} Dr'
                  : closingCredit > 0
                      ? '${controller.formatCurrency(closingCredit)} Cr'
                      : '-',
            ),
          ],
        );
      }),
      DataRow(
        color: reportTotalRowColor(context),
        cells: <DataCell>[
          const DataCell(SizedBox.shrink()),
          DataCell(
            Text(
              'Total',
              style: reportTotalRowTextStyle(context),
            ),
          ),
          const DataCell(SizedBox.shrink()),
          DataCell(
            Center(
              child: Text(
                controller.formatCurrency(reportData['total_opening_debit']),
                style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 12),
              ),
            ),
          ),
          DataCell(
            Center(
              child: Text(
                controller.formatCurrency(
                  reportData['total_transaction_debit'] ??
                      reportData['total_debit'],
                ),
                style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 12),
              ),
            ),
          ),
          DataCell(
            Center(
              child: Text(
                controller.formatCurrency(
                  reportData['total_transaction_credit'] ??
                      reportData['total_credit'],
                ),
                style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 12),
              ),
            ),
          ),
          DataCell(
            Center(
              child: Text(
                reportData['is_balanced'] == true
                    ? 'Balanced'
                    : controller.formatCurrency(
                        (_asDouble(reportData['total_debit']) -
                                _asDouble(reportData['total_credit']))
                            .abs(),
                      ),
                style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
              ),
            ),
          ),
        ],
      ),
    ];

    final calculatedHeight = 42.0 + (accounts.length * 52.0);
    final tableHeight = calculatedHeight.clamp(160.0, 550.0);

    return SizedBox(
      height: tableHeight,
      child: MastersTableShell(
        isLoading: false,
        emptyText: 'No accounts found',
        minWidth: 1100,
        columns: <DataColumn2>[
          masterColumn(context, 'Account Code'),
          masterColumn(context, 'Particulars', size: ColumnSize.L),
          masterColumn(context, 'Type'),
          masterColumn(context, 'Opening'),
          masterColumn(context, 'Debit (₹)'),
          masterColumn(context, 'Credit (₹)'),
          masterColumn(context, 'Closing'),
        ],
        rows: tableRows,
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

  Widget _buildSectionMeta(
    BuildContext context,
    Map<String, dynamic> report,
  ) {
    final statusColor = report['is_balanced'] == true
        ? const Color(0xFF16A34A)
        : const Color(0xFFEF4444);
    return Wrap(
      spacing: 8,
      runSpacing: 6,
      alignment: WrapAlignment.start,
      crossAxisAlignment: WrapCrossAlignment.center,
      children: <Widget>[
        _sectionPill(
          context,
          _dateRangeLabel(),
          const Color(0xFF64748B),
        ),
        _sectionPill(
          context,
          report['is_balanced'] == true ? 'Balanced' : 'Review Difference',
          statusColor,
        ),
      ],
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

  double _asDouble(dynamic value) {
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value?.toString() ?? '') ?? 0;
  }

  Widget _typeChip(BuildContext context, String type) {
    final normalized = type.trim().toLowerCase();
    final label = normalized.isEmpty || normalized == '-'
        ? '-'
        : '${normalized[0].toUpperCase()}${normalized.substring(1)}';
    final color = switch (normalized) {
      'asset' => const Color(0xFF2563EB),
      'liability' => const Color(0xFFEF4444),
      'income' => const Color(0xFF059669),
      'expense' => const Color(0xFFF59E0B),
      _ => const Color(0xFF64748B),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        textAlign: TextAlign.center,
        style: Theme.of(context).textTheme.labelSmall?.copyWith(
              color: color,
              fontWeight: FontWeight.w800,
            ),
      ),
    );
  }

  String _dateRangeLabel() {
    return '${controller.formatDate(controller.fromDateController.text)} to ${controller.formatDate(controller.toDateController.text)}';
  }
}
