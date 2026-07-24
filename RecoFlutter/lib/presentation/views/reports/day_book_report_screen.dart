import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../controllers/reports/day_book_report_controller.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'ledger_report_screen.dart';
import 'widgets/report_ui_components.dart';

class DayBookReportScreen extends GetView<DayBookReportController> {
  const DayBookReportScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final lookup = Get.find<ReportLookupController>();
    return Scaffold(
      appBar: AppBar(
        title: const ReportPageTitle(
          title: 'Day Book',
          icon: FontAwesomeIcons.calendarDay,
          color: Color(0xFF0891B2),
        ),
      ),
      body: Obx(
        () {
          final report = controller.reportData['data'];
          final rows = report is Map<String, dynamic> && report['rows'] is List
              ? List<Map<String, dynamic>>.from(
                  (report['rows'] as List).whereType<Map>(),
                )
              : <Map<String, dynamic>>[];
          return RefreshIndicator(
            onRefresh: controller.loadReport,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: <Widget>[
                ReportFilterPanel(
                  title: 'Filters',
                  subtitle: 'Pick date and financial year to refresh the report.',
                  icon: FontAwesomeIcons.sliders,
                  iconColor: const Color(0xFF0891B2),
                  child: Column(
                    children: <Widget>[
                      CustomTextField(
                        label: 'Date',
                        controller: controller.dateController,
                        readOnly: true,
                        suffixIcon: Icons.calendar_today_outlined,
                        onTap: () => _pickDate(context),
                      ),
                      Obx(
                        () => CustomDropdown<int>(
                          label: 'Financial Year',
                          value: controller.financialYearId.value,
                          items: lookup.financialYears
                              .map((item) => _asInt(item['id']))
                              .whereType<int>()
                              .toList(),
                          itemLabelBuilder: (value) {
                            final item = lookup.financialYears.firstWhere(
                              (fy) => _asInt(fy['id']) == value,
                              orElse: () => <String, dynamic>{},
                            );
                            return (item['name'] ?? 'FY').toString();
                          },
                          onChanged: (value) => controller.financialYearId.value = value,
                        ),
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
                              reportName: 'day_book',
                            ),
                          ),
                          ReportSecondaryButton(
                            label: 'PDF',
                            icon: FontAwesomeIcons.filePdf,
                            onTap: () => controller.exportPdf(
                              exportEndpoint: '/export/day-book/pdf',
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
                    crossAxisCount: 2,
                    physics: const NeverScrollableScrollPhysics(),
                    mainAxisSpacing: 10,
                    crossAxisSpacing: 10,
                    childAspectRatio: 1.35,
                    children: <Widget>[
                      ReportStatCard(
                        label: 'Report Date',
                        value: controller.formatDate(controller.dateController.text),
                        note: 'Selected daily book date',
                        color: const Color(0xFF0891B2),
                        icon: FontAwesomeIcons.calendarCheck,
                      ),
                      ReportStatCard(
                        label: 'Vouchers',
                        value: '${rows.length}',
                        note: 'Posted voucher lines',
                        color: const Color(0xFF2563EB),
                        icon: FontAwesomeIcons.receipt,
                      ),
                      ReportStatCard(
                        label: 'Total Debit',
                        value: controller.formatCurrency(report['total_debit']),
                        note: 'Must equal credit',
                        color: const Color(0xFF16A34A),
                        icon: FontAwesomeIcons.arrowTrendUp,
                      ),
                      ReportStatCard(
                        label: 'Total Credit',
                        value: controller.formatCurrency(report['total_credit']),
                        note: 'Must equal debit',
                        color: const Color(0xFFF59E0B),
                        icon: FontAwesomeIcons.arrowTrendDown,
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                ],
                ReportSectionCard(
                  title: 'Day Book Entries',
                  icon: FontAwesomeIcons.tableList,
                  iconColor: const Color(0xFF0891B2),
                  child: rows.isEmpty
                      ? const Center(child: Padding(
                          padding: EdgeInsets.all(16),
                          child: Text('No posted transactions found.'),
                        ))
                      : SingleChildScrollView(
                          scrollDirection: Axis.horizontal,
                          child: DataTable2(
                            minWidth: 1120,
                            columns: <DataColumn2>[
                              masterColumn(context, 'Voucher #', size: ColumnSize.M),
                              masterColumn(context, 'Type', size: ColumnSize.S),
                              masterColumn(context, 'Particulars', size: ColumnSize.L),
                              masterColumn(context, 'Party', size: ColumnSize.M),
                              masterColumn(context, 'Narration', size: ColumnSize.L),
                              masterColumn(context, 'Debit', size: ColumnSize.M),
                              masterColumn(context, 'Credit', size: ColumnSize.M),
                            ],
                            rows: rows.map((row) {
                              final accountId = _asInt(row['account_id']);
                              return DataRow(
                                cells: <DataCell>[
                                  masterTextCell((row['voucher_number'] ?? '-').toString()),
                                  masterTextCell((row['voucher_type'] ?? '-').toString()),
                                  DataCell(
                                    InkWell(
                                      onTap: accountId == null
                                          ? null
                                          : () => Get.to(
                                                () => LedgerReportScreen(
                                                  initialAccountId: accountId,
                                                ),
                                              ),
                                      child: Text((row['account_name'] ?? '-').toString()),
                                    ),
                                  ),
                                  masterTextCell((row['party_name'] ?? '-').toString()),
                                  masterTextCell((row['narration'] ?? '-').toString()),
                                  masterTextCell(controller.formatCurrency(row['debit'])),
                                  masterTextCell(controller.formatCurrency(row['credit'])),
                                ],
                              );
                            }).toList(),
                          ),
                        ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Future<void> _pickDate(BuildContext context) async {
    final initial = DateTime.tryParse(controller.dateController.text) ?? DateTime(2026, 7, 22);
    final selected = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      controller.dateController.text = selected.toIso8601String().substring(0, 10);
    }
  }

  int? _asInt(dynamic value) => int.tryParse(value?.toString() ?? '');
}
