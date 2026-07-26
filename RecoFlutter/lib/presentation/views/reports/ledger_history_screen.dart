import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/network/api_client.dart';
import '../../controllers/reports/ledger_history_controller.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'widgets/report_ui_components.dart';

class LedgerHistoryScreen extends StatefulWidget {
  const LedgerHistoryScreen({
    required this.ledgerEntryId,
    super.key,
  });

  final int ledgerEntryId;

  @override
  State<LedgerHistoryScreen> createState() => _LedgerHistoryScreenState();
}

class _LedgerHistoryScreenState extends State<LedgerHistoryScreen> {
  late final LedgerHistoryController controller;
  late final String tag;

  @override
  void initState() {
    super.initState();
    tag = 'ledger-history-${widget.ledgerEntryId}';
    controller = Get.put(
      LedgerHistoryController(Get.find<ApiClient>(), widget.ledgerEntryId),
      tag: tag,
    );
  }

  @override
  void dispose() {
    if (Get.isRegistered<LedgerHistoryController>(tag: tag)) {
      Get.delete<LedgerHistoryController>(tag: tag);
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Ledger History'),
      ),
      body: Obx(() {
        if (controller.isLoading.value) {
          return const Center(child: CircularProgressIndicator());
        }

        final rows = controller.history;
        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportSectionCard(
              title: 'History for Ledger Entry #${widget.ledgerEntryId}',
              icon: FontAwesomeIcons.clockRotateLeft,
              iconColor: const Color(0xFF475569),
              child: rows.isEmpty
                  ? const Padding(
                      padding: EdgeInsets.all(24),
                      child: Center(
                        child: Text('No history found for this ledger entry.'),
                      ),
                    )
                  : SizedBox(
                      height: (42.0 + (rows.length * 52.0)).clamp(180.0, 520.0),
                      child: MastersTableShell(
                        isLoading: false,
                        emptyText: 'No history found for this ledger entry.',
                        minWidth: 920,
                        columns: <DataColumn2>[
                          masterColumn(context, 'Date', size: ColumnSize.M),
                          masterColumn(context, 'Party', size: ColumnSize.L),
                          masterColumn(context, 'Reference', size: ColumnSize.M),
                          masterColumn(context, 'Notes', size: ColumnSize.L),
                          masterColumn(context, 'Created By', size: ColumnSize.M),
                        ],
                        rows: rows.map((item) {
                          final party = item['party'] is Map<String, dynamic>
                              ? Map<String, dynamic>.from(item['party'] as Map<String, dynamic>)
                              : <String, dynamic>{};
                          final referenceType = (item['reference_type'] ?? '-').toString();
                          final referenceId = (item['reference_id'] ?? '').toString();
                          return DataRow(
                            cells: <DataCell>[
                              masterTextCell(_formatDateTime((item['created_at'] ?? '').toString())),
                              masterTextCell((party['name'] ?? 'N/A').toString()),
                              masterTextCell(
                                referenceId.isEmpty ? referenceType : '$referenceType #$referenceId',
                              ),
                              masterTextCell((item['notes'] ?? '-').toString()),
                              masterTextCell((item['created_by'] ?? 'System').toString()),
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

  String _formatDateTime(String value) {
    if (value.length >= 16) {
      return value.substring(0, 16).replaceFirst('T', ' ');
    }
    return value;
  }
}
