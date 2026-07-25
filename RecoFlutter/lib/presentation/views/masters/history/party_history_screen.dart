import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../data/models/masters/master_entities.dart';
import '../../../../data/models/transactions/transaction_entities.dart';
import '../../../controllers/masters/party_history_controller.dart';
import '../../../views/transactions/details/transaction_detail_screen.dart';
import '../../../widgets/common/custom_text_field.dart';
import '../widgets/masters_ui_components.dart';

class PartyHistoryScreen extends StatefulWidget {
  const PartyHistoryScreen({
    required this.partyId,
    this.seedParty,
    super.key,
  });

  final int partyId;
  final PartyEntity? seedParty;

  @override
  State<PartyHistoryScreen> createState() => _PartyHistoryScreenState();
}

class _PartyHistoryScreenState extends State<PartyHistoryScreen> {
  late final String _tag;
  late final PartyHistoryController controller;

  @override
  void initState() {
    super.initState();
    _tag = 'party-history-${widget.partyId}';
    controller = Get.put(
      PartyHistoryController(
        Get.find(),
        partyId: widget.partyId,
        seedParty: widget.seedParty,
      ),
      tag: _tag,
    );
  }

  @override
  void dispose() {
    if (Get.isRegistered<PartyHistoryController>(tag: _tag)) {
      Get.delete<PartyHistoryController>(tag: _tag);
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'AR / AP History',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
        ),
      ),
      body: Obx(
        () => Column(
          children: <Widget>[
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
              child: Column(
                children: <Widget>[
                  _HeaderCard(controller: controller),
                  const SizedBox(height: 12),
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: CustomTextField(
                          controller: controller.fromDateController,
                          label: 'From Date',
                          hintText: 'YYYY-MM-DD',
                          readOnly: true,
                          onTap: () => _pickDate(controller.fromDateController),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: CustomTextField(
                          controller: controller.toDateController,
                          label: 'To Date',
                          hintText: 'YYYY-MM-DD',
                          readOnly: true,
                          onTap: () => _pickDate(controller.toDateController),
                        ),
                      ),
                      const SizedBox(width: 10),
                      SizedBox(
                        height: 48,
                        child: FilledButton(
                          onPressed: controller.loadHistory,
                          child: const Text('Apply'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            Expanded(
              child: MasterSectionPadding(
                child: MastersTableShell(
                  isLoading: controller.isLoading.value,
                  emptyText: 'No voucher relation found',
                  minWidth: 1040,
                  columns: <DataColumn2>[
                    masterColumn(context, 'Date'),
                    masterColumn(context, 'Voucher #'),
                    masterColumn(context, 'Type'),
                    masterColumn(context, 'Description', size: ColumnSize.L),
                    masterColumn(context, 'Debit'),
                    masterColumn(context, 'Credit'),
                    masterColumn(context, 'Balance'),
                  ],
                  rows: controller.transactions.map((row) {
                    return DataRow(
                      cells: <DataCell>[
                        masterTextCell(
                          controller.formatDate((row['date'] ?? '').toString()),
                        ),
                        DataCell(
                          InkWell(
                            onTap: () => _openVoucher(row),
                            child: Text(
                              (row['voucher_number'] ?? '-').toString(),
                              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                    color: Theme.of(context).colorScheme.primary,
                                    fontWeight: FontWeight.w600,
                                  ),
                            ),
                          ),
                        ),
                        masterTextCell((row['voucher_type'] ?? '-').toString()),
                        masterTextCell((row['description'] ?? '-').toString()),
                        masterTextCell(
                          _cellAmount(controller, row['debit']),
                        ),
                        masterTextCell(
                          _cellAmount(controller, row['credit']),
                        ),
                        masterTextCell(
                          '${controller.formatCurrency(row['running_balance'])} ${(row['running_type'] ?? '').toString().toUpperCase()}',
                        ),
                      ],
                    );
                  }).toList(),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _cellAmount(PartyHistoryController controller, dynamic value) {
    final amount = value is num ? value.toDouble() : double.tryParse('$value') ?? 0;
    return amount > 0 ? controller.formatCurrency(amount) : '-';
  }

  void _openVoucher(Map<String, dynamic> row) {
    Get.to(
      () => TransactionDetailScreen(
        record: TransactionRecord(
          kind: TransactionRecordKind.voucher,
          rawPayload: row,
          id: row['voucher_id'] as int?,
          number: (row['voucher_number'] ?? '').toString(),
          type: (row['voucher_type'] ?? '').toString(),
          typeLabel: _titleCase((row['voucher_type'] ?? 'voucher').toString()),
          partyName: controller.party.value?.name ?? '',
          date: (row['date'] ?? '').toString(),
          statusLabel: 'Posted',
          amount: (row['debit'] is num ? row['debit'] : row['credit'] ?? 0).toDouble(),
          narration: (row['description'] ?? '').toString(),
        ),
      ),
    );
  }

  Future<void> _pickDate(TextEditingController target) async {
    final selected = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      target.text = selected.toIso8601String().substring(0, 10);
    }
  }

  String _titleCase(String value) => value
      .split('_')
      .where((part) => part.isNotEmpty)
      .map((part) => '${part[0].toUpperCase()}${part.substring(1)}')
      .join(' ');
}

class _HeaderCard extends StatelessWidget {
  const _HeaderCard({required this.controller});

  final PartyHistoryController controller;

  @override
  Widget build(BuildContext context) {
    final party = controller.party.value;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).dividerColor.withValues(alpha: .4)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            party?.name ?? 'Party',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
          ),
          const SizedBox(height: 4),
          Text(
            '${party?.partyCode ?? '-'} • ${(party?.type ?? '').toUpperCase()}',
            style: Theme.of(context).textTheme.bodySmall,
          ),
          const SizedBox(height: 12),
          Row(
            children: <Widget>[
              Expanded(
                child: _StatTile(
                  label: 'Debit',
                  value: controller.formatCurrency(controller.totalDebit.value),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _StatTile(
                  label: 'Credit',
                  value: controller.formatCurrency(controller.totalCredit.value),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _StatTile(
                  label: 'Closing',
                  value:
                      '${controller.formatCurrency(controller.closingBalance.value)} ${controller.closingType.value.toUpperCase()}',
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _StatTile extends StatelessWidget {
  const _StatTile({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(label, style: Theme.of(context).textTheme.bodySmall),
          const SizedBox(height: 4),
          Text(
            value,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
          ),
        ],
      ),
    );
  }
}
