import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/utils/app_date_formatter.dart';
import '../../controllers/reports/profit_loss_report_controller.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'ledger_report_screen.dart';
import 'stock_value_register_screen.dart';
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
          color: const Color(0xFF16A34A),
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
                  'Track income, expenses, and final profitability with a cleaner statement layout built for fast management review.',
              icon: FontAwesomeIcons.sliders.data,
              iconColor: const Color(0xFF16A34A),
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
                  ReportDateRangeRow(
                    fromController: controller.fromDateController,
                    toController: controller.toDateController,
                    onFromTap: () => _pickDate(
                      context,
                      controller.fromDateController,
                    ),
                    onToTap: () => _pickDate(
                      context,
                      controller.toDateController,
                    ),
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
            if (report is! Map<String, dynamic>)
              ReportSectionCard(
                title: 'No financial year found',
                icon: FontAwesomeIcons.calendarXmark.data,
                iconColor: const Color(0xFF16A34A),
                child: const Padding(
                  padding: EdgeInsets.all(16),
                  child: Text(
                    'Create a financial year first to view profit and loss.',
                  ),
                ),
              )
            else ...<Widget>[
              ReportSectionCard(
                title: 'Profit & Loss Account',
                icon: FontAwesomeIcons.book.data,
                iconColor: const Color(0xFF16A34A),
                trailing: Wrap(
                  spacing: 8,
                  runSpacing: 6,
                  crossAxisAlignment: WrapCrossAlignment.center,
                  children: <Widget>[
                    _pill(
                      context,
                      _dateRangeLabel(),
                      const Color(0xFF0EA5E9),
                    ),
                    _pill(
                      context,
                      report['is_profit'] == true
                          ? 'Profitable'
                          : 'Loss Position',
                      report['is_profit'] == true
                          ? const Color(0xFF16A34A)
                          : const Color(0xFFEF4444),
                      icon: report['is_profit'] == true
                          ? FontAwesomeIcons.circleCheck.data
                          : FontAwesomeIcons.circleExclamation.data,
                    ),
                    OutlinedButton.icon(
                      onPressed: controller.financialYearId.value == null
                          ? null
                          : () async {
                              await Get.to<void>(
                                () => StockValueRegisterScreen(
                                  financialYearId:
                                      controller.financialYearId.value!,
                                  fromDate: AppDateFormatter.toApiDate(
                                    controller.fromDateController.text,
                                  ),
                                  toDate: AppDateFormatter.toApiDate(
                                    controller.toDateController.text,
                                  ),
                                ),
                              );
                              await controller.loadReport();
                            },
                      icon: const Icon(Icons.edit_outlined, size: 16),
                      label: const Text('Edit Stock Values'),
                    ),
                  ],
                ),
                child: _buildProfitLossTable(context, report),
              ),
              const SizedBox(height: 12),
              _netResultBar(context, report),
              if (_stockRegister(report).isNotEmpty) ...<Widget>[
                const SizedBox(height: 12),
                _buildStockRegisterSection(context, report),
              ],
            ],
          ],
        );
      }),
    );
  }

  Widget _buildProfitLossTable(
    BuildContext context,
    Map<String, dynamic> report,
  ) {
    final expenseRows = _accountRows(report['expense']);
    final incomeRows = _accountRows(report['income']);
    final isProfit = report['is_profit'] == true;
    final balancingAmount = _asDouble(report['net_profit']).abs();

    if (isProfit) {
      expenseRows.add(<String, dynamic>{
        'label': 'Net Profit',
        'amount': balancingAmount,
      });
    } else {
      incomeRows.add(<String, dynamic>{
        'label': 'Net Loss',
        'amount': balancingAmount,
      });
    }

    final rowCount = expenseRows.length > incomeRows.length
        ? expenseRows.length
        : incomeRows.length;

    if (rowCount == 0) {
      return const Padding(
        padding: EdgeInsets.all(24),
        child: Center(child: Text('No income or expenses recorded')),
      );
    }

    final totalExpense = expenseRows.fold<double>(
      0,
      (sum, row) => sum + _asDouble(row['amount']),
    );
    final totalIncome = incomeRows.fold<double>(
      0,
      (sum, row) => sum + _asDouble(row['amount']),
    );

    final tableRows = <DataRow>[
      ...List<DataRow>.generate(rowCount, (index) {
        final expense = index < expenseRows.length ? expenseRows[index] : null;
        final income = index < incomeRows.length ? incomeRows[index] : null;
        return DataRow(
          cells: <DataCell>[
            _sideNameCell(context, expense, isExpenseSide: true),
            _sideAmountCell(context, expense, isExpenseSide: true),
            _sideNameCell(context, income, isExpenseSide: false),
            _sideAmountCell(context, income, isExpenseSide: false),
          ],
        );
      }),
      DataRow(
        color: reportTotalRowColor(context),
        cells: <DataCell>[
          DataCell(
            Text('Total', style: reportTotalRowTextStyle(context)),
          ),
          DataCell(
            Center(
              child: Text(
                controller.formatCurrency(totalExpense),
                style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
              ),
            ),
          ),
          DataCell(
            Text('Total', style: reportTotalRowTextStyle(context)),
          ),
          DataCell(
            Center(
              child: Text(
                controller.formatCurrency(totalIncome),
                style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
              ),
            ),
          ),
        ],
      ),
    ];

    return SizedBox(
      height: (42.0 + (tableRows.length * 52.0)).clamp(180.0, 620.0),
      child: MastersTableShell(
        isLoading: false,
        emptyText: 'No income or expenses recorded',
        minWidth: 920,
        columns: <DataColumn2>[
          masterColumn(context, 'Expenses', size: ColumnSize.L),
          masterColumn(context, 'Amount', size: ColumnSize.S),
          masterColumn(context, 'Income', size: ColumnSize.L),
          masterColumn(context, 'Amount', size: ColumnSize.S),
        ],
        rows: tableRows,
      ),
    );
  }

  Widget _buildStockRegisterSection(
    BuildContext context,
    Map<String, dynamic> report,
  ) {
    final stock = report['stock'] is Map<String, dynamic>
        ? Map<String, dynamic>.from(report['stock'] as Map<String, dynamic>)
        : <String, dynamic>{};
    final register = _stockRegister(report);

    return ReportSectionCard(
      title: 'Stock Value Register',
      icon: FontAwesomeIcons.boxesStacked.data,
      iconColor: const Color(0xFF2563EB),
      trailing: _pill(context, 'User-entered values', const Color(0xFF64748B)),
      child: Column(
        children: <Widget>[
          SizedBox(
            height: (42.0 + (register.length * 52.0)).clamp(160.0, 360.0),
            child: MastersTableShell(
              isLoading: false,
              emptyText: 'No stock value entries found.',
              minWidth: 640,
              columns: <DataColumn2>[
                masterColumn(context, 'Date', size: ColumnSize.M),
                masterColumn(context, 'Stock Value (₹)', size: ColumnSize.M),
                masterColumn(context, 'Remarks', size: ColumnSize.L),
              ],
              rows: register.map((row) {
                return DataRow(
                  cells: <DataCell>[
                    masterTextCell(
                      controller.formatDate(
                        (row['valuation_date'] ?? '').toString(),
                      ),
                    ),
                    masterTextCell(
                      controller.formatCurrency(row['stock_value']),
                      fontWeight: FontWeight.w700,
                    ),
                    masterTextCell(
                      (row['remarks'] ?? '-').toString(),
                      fontWeight: FontWeight.w500,
                    ),
                  ],
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: 10),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
            decoration: BoxDecoration(
              color: const Color(0xFF23263A),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              children: <Widget>[
                Expanded(
                  child: Text(
                    'Opening / Closing Value',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: Colors.white,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                Text(
                  '${controller.formatCurrency(stock['opening_value'])} / ${controller.formatCurrency(stock['closing_value'])}',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(width: 12),
                TextButton(
                  onPressed: controller.financialYearId.value == null
                      ? null
                      : () async {
                          await Get.to<void>(
                            () => StockValueRegisterScreen(
                              financialYearId:
                                  controller.financialYearId.value!,
                              fromDate: AppDateFormatter.toApiDate(
                                controller.fromDateController.text,
                              ),
                              toDate: AppDateFormatter.toApiDate(
                                controller.toDateController.text,
                              ),
                            ),
                          );
                          await controller.loadReport();
                        },
                  child: const Text('Edit register'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  DataCell _sideNameCell(
    BuildContext context,
    Map<String, dynamic>? row, {
    required bool isExpenseSide,
  }) {
    if (row == null) {
      return const DataCell(SizedBox.shrink());
    }

    final account = row['account'];
    final accountId =
        account is Map<String, dynamic> ? _asInt(account['id']) : null;
    final label = account is Map<String, dynamic>
        ? (account['account_name'] ?? '-').toString()
        : (row['label'] ?? '-').toString();
    final isBalancing = account == null && row['label'] != null;

    if (accountId != null) {
      return DataCell(
        Center(
          child: ReportLinkText(
            label,
            onTap: () => Get.to(
              () => LedgerReportScreen(initialAccountId: accountId),
            ),
          ),
        ),
      );
    }

    return DataCell(
      Center(
        child: Text(
          label,
          textAlign: TextAlign.center,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
            fontWeight: FontWeight.w800,
            color: isBalancing
                ? (isExpenseSide
                    ? const Color(0xFF16A34A)
                    : const Color(0xFFEF4444))
                : null,
          ),
        ),
      ),
    );
  }

  DataCell _sideAmountCell(
    BuildContext context,
    Map<String, dynamic>? row, {
    required bool isExpenseSide,
  }) {
    if (row == null) {
      return const DataCell(SizedBox.shrink());
    }

    final account = row['account'];
    final isBalancing = account == null && row['label'] != null;
    final amount = _asDouble(row['amount']);
    final color = isBalancing
        ? (isExpenseSide ? const Color(0xFF16A34A) : const Color(0xFFEF4444))
        : (isExpenseSide ? const Color(0xFFEF4444) : const Color(0xFF16A34A));

    return DataCell(
      Center(
        child: Text(
          controller.formatCurrency(amount),
          textAlign: TextAlign.center,
          style: TextStyle(
            color: color,
            fontWeight: FontWeight.w700,
            fontSize: 13,
          ),
        ),
      ),
    );
  }

  Widget _netResultBar(BuildContext context, Map<String, dynamic> report) {
    final isProfit = report['is_profit'] == true;
    final color = isProfit ? const Color(0xFF16A34A) : const Color(0xFFEF4444);
    final label = isProfit ? 'Net Profit' : 'Net Loss';
    final amount =
        '${isProfit ? '+' : '-'}${controller.formatCurrency(_asDouble(report['net_profit']).abs())}';

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(
          color: Theme.of(context).dividerColor.withValues(alpha: .20),
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

  List<Map<String, dynamic>> _accountRows(dynamic section) {
    if (section is! Map<String, dynamic> || section['accounts'] is! List) {
      return <Map<String, dynamic>>[];
    }
    return List<Map<String, dynamic>>.from(
      (section['accounts'] as List).whereType<Map>(),
    );
  }

  List<Map<String, dynamic>> _stockRegister(Map<String, dynamic> report) {
    final stock = report['stock'];
    if (stock is! Map<String, dynamic> || stock['register'] is! List) {
      return <Map<String, dynamic>>[];
    }
    return List<Map<String, dynamic>>.from(
      (stock['register'] as List).whereType<Map>().map((row) {
        final entry = row['entry'];
        if (entry is Map<String, dynamic>) {
          return <String, dynamic>{
            ...row,
            'id': entry['id'],
          };
        }
        return row;
      }),
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
