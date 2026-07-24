import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../controllers/reports/bank_book_report_controller.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'widgets/report_ui_components.dart';

class BankBookReportScreen extends GetView<BankBookReportController> {
  const BankBookReportScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final lookup = Get.find<ReportLookupController>();
    return Scaffold(
      appBar: AppBar(
        title: const ReportPageTitle(
          title: 'Bank Book',
          icon: FontAwesomeIcons.buildingColumns,
          color: Color(0xFF2563EB),
        ),
      ),
      body: Obx(() {
        final book = controller.reportData['data'];
        final report = book is Map<String, dynamic> && book['report'] is Map<String, dynamic>
            ? Map<String, dynamic>.from(book['report'] as Map<String, dynamic>)
            : null;
        final selectedAccount = book is Map<String, dynamic> && book['account'] is Map<String, dynamic>
            ? Map<String, dynamic>.from(book['account'] as Map<String, dynamic>)
            : null;
        final message = book is Map<String, dynamic> ? book['message']?.toString() : null;
        final entries = report is Map<String, dynamic> && report['entries'] is List
            ? List<Map<String, dynamic>>.from(
                (report['entries'] as List).whereType<Map>().map(
                      (item) => Map<String, dynamic>.from(item),
                    ),
              )
            : <Map<String, dynamic>>[];
        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle: 'Choose bank account and date range for statement view.',
              icon: FontAwesomeIcons.sliders,
              iconColor: const Color(0xFF2563EB),
              child: Column(
                children: <Widget>[
                  CustomDropdown<int>(
                    label: 'Bank Account',
                    value: controller.accountId.value,
                    items: lookup.bankAccounts
                        .map((item) => _asInt(item['id']))
                        .whereType<int>()
                        .toList(),
                    itemLabelBuilder: (value) {
                      final item = lookup.bankAccounts.firstWhere(
                        (row) => _asInt(row['id']) == value,
                        orElse: () => <String, dynamic>{},
                      );
                      final code = (item['account_code'] ?? '').toString();
                      final name = (item['account_name'] ?? item['text'] ?? 'Bank').toString();
                      return code.isEmpty ? name : '$code - $name';
                    },
                    onChanged: (value) => controller.accountId.value = value,
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
                        icon: FontAwesomeIcons.filter,
                        onTap: controller.loadReport,
                      ),
                      ReportSecondaryButton(
                        label: 'Excel',
                        icon: FontAwesomeIcons.fileExcel,
                        onTap: () => controller.exportExcel(
                          reportName: 'bank_book',
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
            if (message != null && message.isNotEmpty)
              ReportSectionCard(
                title: 'Bank Book Status',
                icon: FontAwesomeIcons.circleInfo,
                iconColor: const Color(0xFF2563EB),
                child: Text(message),
              )
            else if (report != null) ...<Widget>[
              GridView.count(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                crossAxisCount: 2,
                mainAxisSpacing: 10,
                crossAxisSpacing: 10,
                childAspectRatio: 1.35,
                children: <Widget>[
                  if (selectedAccount != null)
                    ReportStatCard(
                      label: 'Bank Account',
                      value: (selectedAccount['account_code'] ?? '-').toString(),
                      note: (selectedAccount['account_name'] ?? 'Bank Account').toString(),
                      color: const Color(0xFF0F766E),
                      icon: FontAwesomeIcons.buildingColumns,
                    ),
                  ReportStatCard(
                    label: 'Opening',
                    value: controller.formatCurrency(
                      (report['opening_balance'] as Map?)?['balance'],
                    ),
                    note: ((report['opening_balance'] as Map?)?['type'] ?? '-')
                        .toString()
                        .toUpperCase(),
                    color: const Color(0xFF2563EB),
                    icon: FontAwesomeIcons.landmark,
                  ),
                  ReportStatCard(
                    label: 'Receipts',
                    value: controller.formatCurrency(report['total_debit']),
                    note: 'Money in',
                    color: const Color(0xFF16A34A),
                    icon: FontAwesomeIcons.arrowDownWideShort,
                  ),
                  ReportStatCard(
                    label: 'Payments',
                    value: controller.formatCurrency(report['total_credit']),
                    note: 'Money out',
                    color: const Color(0xFFEF4444),
                    icon: FontAwesomeIcons.arrowUpWideShort,
                  ),
                  ReportStatCard(
                    label: 'Closing',
                    value: controller.formatCurrency(
                      (report['closing_balance'] as Map?)?['balance'],
                    ),
                    note: ((report['closing_balance'] as Map?)?['type'] ?? '-')
                        .toString()
                        .toUpperCase(),
                    color: const Color(0xFFF59E0B),
                    icon: FontAwesomeIcons.fileCircleCheck,
                  ),
                ],
              ),
              const SizedBox(height: 12),
            ],
            ReportSectionCard(
              title: 'Bank Book Entries',
              icon: FontAwesomeIcons.tableList,
              iconColor: const Color(0xFF2563EB),
              child: entries.isEmpty
                  ? const Text('No entries in this period')
                  : SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: DataTable2(
                        minWidth: 980,
                        columns: <DataColumn2>[
                          masterColumn(context, 'Date'),
                          masterColumn(context, 'Voucher #'),
                          masterColumn(context, 'Particulars', size: ColumnSize.L),
                          masterColumn(context, 'Party'),
                          masterColumn(context, 'Receipts'),
                          masterColumn(context, 'Payments'),
                          masterColumn(context, 'Balance'),
                        ],
                        rows: entries.map((map) {
                          final voucher = map['voucher'] is Map<String, dynamic>
                              ? map['voucher'] as Map<String, dynamic>
                              : <String, dynamic>{};
                          final party = map['party'] is Map<String, dynamic>
                              ? map['party'] as Map<String, dynamic>
                              : <String, dynamic>{};
                          final debit =
                              double.tryParse(map['debit']?.toString() ?? '') ?? 0;
                          final credit =
                              double.tryParse(map['credit']?.toString() ?? '') ?? 0;
                          return DataRow(
                            cells: <DataCell>[
                              masterTextCell(
                                controller.formatDate(
                                  (map['transaction_date'] ?? '').toString(),
                                ),
                              ),
                              masterTextCell(
                                (voucher['voucher_number'] ?? '-').toString(),
                              ),
                              masterTextCell(
                                (map['description'] ?? voucher['narration'] ?? '-')
                                    .toString(),
                              ),
                              masterTextCell((party['name'] ?? '-').toString()),
                              masterTextCell(
                                debit > 0
                                    ? controller.formatCurrency(debit)
                                    : '-',
                              ),
                              masterTextCell(
                                credit > 0
                                    ? controller.formatCurrency(credit)
                                    : '-',
                              ),
                              masterTextCell(
                                '${controller.formatCurrency(map['running_balance'])} ${(map['balance_type'] ?? '').toString().toUpperCase()}',
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

  Future<void> _pickDate(
    BuildContext context,
    TextEditingController controller,
  ) async {
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
