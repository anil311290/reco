import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../controllers/reports/ledger_report_controller.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'widgets/report_ui_components.dart';

class LedgerReportScreen extends StatefulWidget {
  const LedgerReportScreen({super.key, this.initialAccountId});

  final int? initialAccountId;

  @override
  State<LedgerReportScreen> createState() => _LedgerReportScreenState();
}

class _LedgerReportScreenState extends State<LedgerReportScreen> {
  late final LedgerReportController controller;

  @override
  void initState() {
    super.initState();
    controller = Get.find<LedgerReportController>();
    if (widget.initialAccountId != null) {
      controller.accountId.value = widget.initialAccountId;
      controller.loadReport();
    }
  }

  @override
  Widget build(BuildContext context) {
    final lookup = Get.find<ReportLookupController>();
    return Scaffold(
      appBar: AppBar(
        title: const ReportPageTitle(
          title: 'Ledger',
          icon: FontAwesomeIcons.bookOpen,
          color: Color(0xFF475569),
        ),
      ),
      body: Obx(() {
        final report = controller.reportData['data'];
        final entries = report is Map<String, dynamic> && report['entries'] is List
            ? List<Map<String, dynamic>>.from(
                (report['entries'] as List).whereType<Map>(),
              )
            : <Map<String, dynamic>>[];
        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle: 'Select ledger and period to inspect running balances.',
              icon: FontAwesomeIcons.sliders,
              iconColor: const Color(0xFF475569),
              child: Column(
                children: <Widget>[
                  CustomDropdown<int>(
                    label: 'Ledger',
                    value: controller.accountId.value,
                    items: lookup.ledgerAccounts
                        .map((item) => _asInt(item['id']))
                        .whereType<int>()
                        .toList(),
                    itemLabelBuilder: (value) {
                      final item = lookup.ledgerAccounts.firstWhere(
                        (row) => _asInt(row['id']) == value,
                        orElse: () => <String, dynamic>{},
                      );
                      final code = (item['account_code'] ?? '').toString();
                      final name = (item['account_name'] ?? '').toString();
                      return code.isEmpty ? name : '$code - $name';
                    },
                    onChanged: (value) {
                      controller.accountId.value = value;
                      controller.loadReport();
                    },
                  ),
                  ReportDateRangeRow(
                    fromController: controller.fromDateController,
                    toController: controller.toDateController,
                    onFromTap: () => _pickDate(controller.fromDateController),
                    onToTap: () => _pickDate(controller.toDateController),
                  ),
                  const SizedBox(height: 12),
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
                          reportName: 'ledger',
                        ),
                      ),
                      ReportSecondaryButton(
                        label: 'PDF',
                        icon: FontAwesomeIcons.filePdf,
                        onTap: () => controller.exportPdf(
                          exportEndpoint: '/export/ledger/pdf',
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
              Row(
                children: <Widget>[
                  Expanded(
                    child: ReportStatCard(
                      label: 'Opening Balance',
                      value: controller.formatCurrency(
                        (report['opening_balance'] as Map?)?['balance'],
                      ),
                      note: ((report['opening_balance'] as Map?)?['type'] ?? '-')
                          .toString()
                          .toUpperCase(),
                      color: const Color(0xFF2563EB),
                      icon: FontAwesomeIcons.circlePlay,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: ReportStatCard(
                      label: 'Closing Balance',
                      value: controller.formatCurrency(
                        (report['closing_balance'] as Map?)?['balance'],
                      ),
                      note: ((report['closing_balance'] as Map?)?['type'] ?? '-')
                          .toString()
                          .toUpperCase(),
                      color: const Color(0xFF16A34A),
                      icon: FontAwesomeIcons.circleStop,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
            ],
            ReportSectionCard(
              title: 'Ledger Entries',
              icon: FontAwesomeIcons.tableList,
              iconColor: const Color(0xFF475569),
              child: entries.isEmpty && report is! Map
                  ? const Padding(
                      padding: EdgeInsets.all(24),
                      child: Center(child: Text('No entries found')),
                    )
                  : _buildLedgerTable(context, report, entries),
            ),
          ],
        );
      }),
    );
  }

  Widget _buildLedgerTable(
    BuildContext context,
    dynamic reportData,
    List<Map<String, dynamic>> entries,
  ) {
    if (reportData is! Map) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(24),
          child: Text('No entries found'),
        ),
      );
    }

    final tableRows = <DataRow>[
      ...List<DataRow>.generate(entries.length, (index) {
        final entry = entries[index];
        final voucher = entry['voucher'] is Map
            ? Map<String, dynamic>.from(entry['voucher'] as Map)
            : <String, dynamic>{};
        final party = entry['party'] is Map
            ? Map<String, dynamic>.from(entry['party'] as Map)
            : <String, dynamic>{};
        final debit =
            double.tryParse(entry['debit']?.toString() ?? '0') ?? 0;
        final credit =
            double.tryParse(entry['credit']?.toString() ?? '0') ?? 0;

        return DataRow(
          cells: <DataCell>[
            masterTextCell(controller.formatDate((entry['transaction_date'] ?? '').toString())),
            masterTextCell((voucher['voucher_number'] ?? '-').toString()),
            masterTextCell((entry['narration'] ?? entry['description'] ?? '-').toString()),
            masterTextCell((party['name'] ?? '-').toString()),
            DataCell(Center(
              child: Text(
                debit > 0 ? controller.formatCurrency(debit) : '-',
                textAlign: TextAlign.center,
                style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: debit > 0 ? const Color(0xFF2563EB) : null),
              ),
            )),
            DataCell(Center(
              child: Text(
                credit > 0 ? controller.formatCurrency(credit) : '-',
                textAlign: TextAlign.center,
                style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: credit > 0 ? const Color(0xFFEF4444) : null),
              ),
            )),
            masterTextCell('${controller.formatCurrency(entry['running_balance'])} ${(entry['balance_type'] ?? '').toString().toUpperCase()}'),
            DataCell(Center(
              child: MasterActionButton(
                icon: FontAwesomeIcons.clockRotateLeft,
                tooltip: 'View Ledger',
                color: const Color(0xFF475569),
                onTap: () => Get.to(() => LedgerReportScreen()),
              ),
            )),
          ],
        );
      }),
    ];

    final calculatedHeight = 42.0 + (entries.length * 52.0);
    final tableHeight = calculatedHeight.clamp(160.0, 550.0);

    return SizedBox(
      height: tableHeight,
      child: MastersTableShell(
        isLoading: false,
        emptyText: 'No entries found',
        minWidth: 1120,
        columns: <DataColumn2>[
          masterColumn(context, 'Date'),
          masterColumn(context, 'Voucher #'),
          masterColumn(context, 'Particulars', size: ColumnSize.L),
          masterColumn(context, 'Party'),
          masterColumn(context, 'Debit'),
          masterColumn(context, 'Credit'),
          masterColumn(context, 'Balance'),
          masterColumn(context, 'Actions', fixedWidth: 100),
        ],
        rows: tableRows,
      ),
    );
  }

  Future<void> _pickDate(TextEditingController controller) async {
    final selected = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      controller.text = selected.toIso8601String().substring(0, 10);
    }
  }

  int? _asInt(dynamic value) => int.tryParse(value?.toString() ?? '');
}
