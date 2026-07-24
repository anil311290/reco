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
                    onChanged: (value) => controller.accountId.value = value,
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
              GridView.count(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                crossAxisCount: 2,
                mainAxisSpacing: 10,
                crossAxisSpacing: 10,
                childAspectRatio: 1.35,
                children: <Widget>[
                  ReportStatCard(
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
                  ReportStatCard(
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
                ],
              ),
              const SizedBox(height: 12),
            ],
            ReportSectionCard(
              title: 'Ledger Entries',
              icon: FontAwesomeIcons.tableList,
              iconColor: const Color(0xFF475569),
              child: entries.isEmpty
                  ? const Text('No entries found')
                  : SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: DataTable2(
                        minWidth: 1120,
                        columns: <DataColumn2>[
                          masterColumn(context, 'Date'),
                          masterColumn(context, 'Voucher #'),
                          masterColumn(context, 'Particulars', size: ColumnSize.L),
                          masterColumn(context, 'Party'),
                          masterColumn(context, 'Debit'),
                          masterColumn(context, 'Credit'),
                          masterColumn(context, 'Balance'),
                        ],
                        rows: entries.map((entry) {
                          final voucher = entry['voucher'];
                          final party = entry['party'];
                          return DataRow(
                            cells: <DataCell>[
                              masterTextCell(controller.formatDate((entry['transaction_date'] ?? '').toString())),
                              masterTextCell(
                                voucher is Map<String, dynamic>
                                    ? (voucher['voucher_number'] ?? '-').toString()
                                    : '-',
                              ),
                              masterTextCell((entry['narration'] ?? entry['description'] ?? '-').toString()),
                              masterTextCell(
                                party is Map<String, dynamic>
                                    ? (party['name'] ?? '-').toString()
                                    : '-',
                              ),
                              masterTextCell(
                                ((entry['debit'] ?? 0) as num) > 0
                                    ? controller.formatCurrency(entry['debit'])
                                    : '-',
                              ),
                              masterTextCell(
                                ((entry['credit'] ?? 0) as num) > 0
                                    ? controller.formatCurrency(entry['credit'])
                                    : '-',
                              ),
                              masterTextCell(
                                '${controller.formatCurrency(entry['running_balance'])} ${(entry['balance_type'] ?? '').toString().toUpperCase()}',
                              ),
                            ],
                          );
                        }).toList(),
                      ),
                    ),
            ),
          ],
        );
      }),
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
