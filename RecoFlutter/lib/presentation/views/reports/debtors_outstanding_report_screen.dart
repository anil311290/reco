import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../controllers/reports/debtors_outstanding_report_controller.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'widgets/report_ui_components.dart';

class DebtorsOutstandingReportScreen
    extends GetView<DebtorsOutstandingReportController> {
  const DebtorsOutstandingReportScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const ReportPageTitle(
          title: 'Receivables',
          icon: FontAwesomeIcons.handHoldingDollar,
          color: Color(0xFFDC2626),
        ),
      ),
      body: Obx(() {
        final report = controller.reportData['data'];
        final rows = report is Map<String, dynamic> && report['debtors'] is List
            ? List<Map<String, dynamic>>.from((report['debtors'] as List).whereType<Map>())
            : <Map<String, dynamic>>[];
        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Export',
              subtitle: 'Download or share the receivables snapshot.',
              icon: FontAwesomeIcons.fileExport,
              iconColor: const Color(0xFFDC2626),
              child: ReportActionBar(
                children: <Widget>[
                  ReportSecondaryButton(
                    label: 'Excel',
                    icon: FontAwesomeIcons.fileExcel,
                    onTap: () => controller.exportExcel(
                      reportName: 'debtors_outstanding',
                    ),
                  ),
                  ReportSecondaryButton(
                    label: 'PDF',
                    icon: FontAwesomeIcons.filePdf,
                    onTap: () => controller.exportPdf(
                      exportEndpoint: '/export/debtors-outstanding/pdf',
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            if (report is Map<String, dynamic>)
              GridView.count(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                crossAxisCount: 2,
                mainAxisSpacing: 10,
                crossAxisSpacing: 10,
                childAspectRatio: 1.35,
                children: <Widget>[
                  ReportStatCard(
                    label: 'Total Outstanding',
                    value: controller.formatCurrency(report['total']),
                    note: 'Open receivables',
                    color: const Color(0xFFDC2626),
                    icon: FontAwesomeIcons.sackDollar,
                  ),
                  ReportStatCard(
                    label: 'Debtors Count',
                    value: '${rows.length}',
                    note: 'Customers with balance',
                    color: const Color(0xFF2563EB),
                    icon: FontAwesomeIcons.users,
                  ),
                ],
              ),
            const SizedBox(height: 12),
            ReportSectionCard(
              title: 'Outstanding Debtors',
              icon: FontAwesomeIcons.tableList,
              iconColor: const Color(0xFFDC2626),
              child: rows.isEmpty
                  ? const Text('No outstanding receivables')
                  : SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: DataTable2(
                        minWidth: 900,
                        columns: <DataColumn2>[
                          masterColumn(context, '#', fixedWidth: 50),
                          masterColumn(context, 'Party', size: ColumnSize.L),
                          masterColumn(context, 'Mobile'),
                          masterColumn(context, 'Email', size: ColumnSize.L),
                          masterColumn(context, 'Balance'),
                        ],
                        rows: List<DataRow>.generate(rows.length, (index) {
                          final row = rows[index];
                          final party = row['party'];
                          return DataRow(
                            cells: <DataCell>[
                              masterTextCell('${index + 1}'),
                              masterTextCell(party is Map<String, dynamic> ? (party['name'] ?? '-').toString() : '-'),
                              masterTextCell(party is Map<String, dynamic> ? (party['mobile'] ?? '-').toString() : '-'),
                              masterTextCell(party is Map<String, dynamic> ? (party['email'] ?? '-').toString() : '-'),
                              masterTextCell(controller.formatCurrency(row['balance'])),
                            ],
                          );
                        }),
                      ),
                    ),
            ),
          ],
        );
      }),
    );
  }
}
