import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/utils/app_date_formatter.dart';
import '../../controllers/reports/creditors_outstanding_report_controller.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import '../masters/history/party_history_screen.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'widgets/report_ui_components.dart';

class CreditorsOutstandingReportScreen
    extends GetView<CreditorsOutstandingReportController> {
  const CreditorsOutstandingReportScreen({super.key});

  static const Color _primaryColor = Color(0xFFF59E0B);
  static const Color _secondaryColor = Color(0xFF2563EB);

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lookup = Get.find<ReportLookupController>();

    return Scaffold(
      appBar: AppBar(
        title: const ReportPageTitle(
          title: 'Payables Outstanding',
          icon: FontAwesomeIcons.wallet,
          color: _primaryColor,
        ),
      ),
      body: Obx(() {
        if (controller.shouldShowInitialLoader) {
          return const ReportLoadingView();
        }
        final report = controller.reportData['data'];

        final rows = _getCreditorRows(report);

        final total = report is Map
            ? double.tryParse(report['total']?.toString() ?? '0') ?? 0.0
            : 0.0;

        return ListView(
          padding: const EdgeInsets.all(16),
          physics: const AlwaysScrollableScrollPhysics(),
          keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle: 'Creditors with credit balances on their linked ledgers (Accounts Payable).',
              icon: FontAwesomeIcons.sliders,
              iconColor: _primaryColor,
              child: Column(
                children: <Widget>[
                  CustomDropdown<int>(
                    label: 'Financial Year',
                    value: controller.financialYearId.value,
                    items: lookup.financialYears
                        .map((item) => _asInt(item['id']))
                        .whereType<int>()
                        .toList(),
                    itemLabelBuilder: (value) {
                      final item = lookup.financialYears.firstWhere(
                        (row) => _asInt(row['id']) == value,
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
                        icon: FontAwesomeIcons.sliders,
                        onTap: controller.loadReport,
                      ),
                      ReportSecondaryButton(
                        label: 'Excel',
                        icon: FontAwesomeIcons.fileExcel,
                        onTap: () {
                          controller.exportExcel(
                            reportName: 'creditors_outstanding',
                            exportEndpoint: ApiEndpoints.exportCreditorsOutstandingExcel,
                            queryParameters: controller.queryParameters,
                          );
                        },
                      ),
                      ReportSecondaryButton(
                        label: 'PDF',
                        icon: FontAwesomeIcons.filePdf,
                        onTap: () {
                          controller.exportPdf(
                            exportEndpoint: ApiEndpoints.exportCreditorsOutstandingPdf,
                            queryParameters: controller.queryParameters,
                          );
                        },
                      ),
                    ],
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
                      value: controller.formatCurrency(report['total']),
                      note: 'Open payables across all creditors.',
                      color: _primaryColor,
                      icon: FontAwesomeIcons.moneyBills,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: ReportStatCard(
                      label: 'Creditors Count',
                      value: '${rows.length}',
                      note: 'Suppliers with outstanding balances.',
                      color: _secondaryColor,
                      icon: FontAwesomeIcons.users,
                    ),
                  ),
                ],
              ),
            if (report is Map) const SizedBox(height: 12),
            ReportSectionCard(
              title: 'Outstanding Creditors',
              icon: FontAwesomeIcons.tableList,
              iconColor: _primaryColor,
              trailing: Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: <Widget>[
                  Text(
                    _dateRangeLabel(),
                    style: Theme.of(context).textTheme.labelMedium?.copyWith(
                      color: const Color(0xFF64748B),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    controller.formatCurrency(total),
                    style: Theme.of(context).textTheme.labelLarge?.copyWith(
                      color: _primaryColor,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
              child: rows.isEmpty
                  ? _buildEmptyState(theme)
                  : _buildCreditorsTable(
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

  String _dateRangeLabel() {
    return '${controller.formatDate(controller.fromDateController.text)} to ${controller.formatDate(controller.toDateController.text)}';
  }

  List<Map<String, dynamic>> _getCreditorRows(dynamic report) {
    if (report is! Map || report['creditors'] is! List) {
      return <Map<String, dynamic>>[];
    }

    return (report['creditors'] as List)
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  Widget _buildEmptyState(ThemeData theme) {
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Center(
        child: Text(
          'No outstanding payables',
          textAlign: TextAlign.center,
          style: theme.textTheme.bodyMedium?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
      ),
    );
  }

  Widget _buildCreditorsTable({
    required BuildContext context,
    required ThemeData theme,
    required List<Map<String, dynamic>> rows,
    required double total,
  }) {
    final tableRows = <DataRow>[
      ...List<DataRow>.generate(rows.length, (index) {
        final row = rows[index];
        final party = _getParty(row);

        return DataRow(
          cells: <DataCell>[
            masterTextCell('${index + 1}'),
            DataCell(
              Center(
                child: ReportLinkText(
                  _getValue(party['name']),
                  onTap: party['id'] == null
                      ? null
                      : () => Get.to(
                            () => PartyHistoryScreen(partyId: party['id'] as int),
                          ),
                ),
              ),
            ),
            masterTextCell(_getValue(party['mobile'])),
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
                    final partyId = party['id'];
                    if (partyId != null) {
                      Get.to(() => PartyHistoryScreen(partyId: partyId));
                    }
                  },
                ),
              ),
            ),
          ],
        );
      }),
      _buildTotalRow(context: context, theme: theme, total: total),
    ];

    final calculatedHeight = 42.0 + ((rows.length + 1) * 52.0);
    final tableHeight = calculatedHeight.clamp(160.0, 550.0);

    return SizedBox(
      height: tableHeight,
      child: MastersTableShell(
        isLoading: false,
        emptyText: 'No outstanding payables',
        minWidth: 900,
        columns: <DataColumn2>[
          masterColumn(context, '#', fixedWidth: 50),
          masterColumn(context, 'Party', size: ColumnSize.L),
          masterColumn(context, 'Mobile', size: ColumnSize.M),
          masterColumn(context, 'Email', size: ColumnSize.L),
          masterColumn(context, 'Balance (₹) Cr', size: ColumnSize.M),
          masterColumn(context, 'History', fixedWidth: 100),
        ],
        rows: tableRows,
      ),
    );
  }

  DataRow _buildTotalRow({
    required BuildContext context,
    required ThemeData theme,
    required double total,
  }) {
    return DataRow(
      color: reportTotalRowColor(context),
      cells: <DataCell>[
        const DataCell(SizedBox.shrink()),
        const DataCell(SizedBox.shrink()),
        const DataCell(SizedBox.shrink()),
        DataCell(
          Center(
            child: Text(
              'Total Outstanding',
              style: reportTotalRowTextStyle(context)?.copyWith(
                fontSize: 13,
              ),
            ),
          ),
        ),
        DataCell(
          Center(
            child: Text(
              controller.formatCurrency(total),
              style: reportTotalRowTextStyle(context)?.copyWith(
                fontSize: 13,
              ),
            ),
          ),
        ),
        const DataCell(SizedBox.shrink()),
      ],
    );
  }

  Map<String, dynamic> _getParty(Map<String, dynamic> row) {
    final party = row['party'];
    if (party is Map<String, dynamic>) return party;
    return <String, dynamic>{};
  }

  String _getValue(dynamic value) {
    final text = value?.toString().trim() ?? '';
    return text.isEmpty ? '-' : text;
  }
}
