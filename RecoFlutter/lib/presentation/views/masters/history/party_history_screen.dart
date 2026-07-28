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
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'AR / AP History',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      body: Obx(
        () => ListView(
          padding: const EdgeInsets.all(10),
          children: <Widget>[
            _buildPartyDetailCard(theme),
            const SizedBox(height: 12),
            _buildFilterRow(theme),
            const SizedBox(height: 12),
            _buildHistorySection(theme),
          ],
        ),
      ),
    );
  }

  Widget _buildPartyDetailCard(ThemeData theme) {
    final party = controller.party.value;

    if (party == null) {
      return const Center(child: CircularProgressIndicator());
    }

    final isReceivable = party.type.toLowerCase() == 'customer' ||
        party.type.toLowerCase() == 'both';

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        gradient: LinearGradient(
          colors: <Color>[
            theme.cardColor,
            theme.colorScheme.primary.withValues(alpha: .03),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        border: Border.all(
          color: theme.colorScheme.primary.withValues(alpha: .10),
        ),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: theme.colorScheme.shadow.withValues(alpha: .04),
            blurRadius: 14,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      party.name,
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${party.partyCode} • ${party.type.toUpperCase()}',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                        fontSize: 11.5,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 5,
                ),
                decoration: BoxDecoration(
                  color: party.isActive
                      ? const Color(0xFFDCFCE7)
                      : theme.colorScheme.surfaceContainerHighest,
                  borderRadius: BorderRadius.circular(999),
                  border: Border.all(
                    color: party.isActive
                        ? const Color(0xFF86EFAC)
                        : theme.dividerColor.withValues(alpha: .55),
                  ),
                ),
                child: Text(
                  party.isActive ? 'Active' : 'Inactive',
                  style: TextStyle(
                    fontSize: 11.5,
                    fontWeight: FontWeight.w600,
                    color: party.isActive
                        ? const Color(0xFF15803D)
                        : theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 8,
            crossAxisSpacing: 8,
            childAspectRatio: 1.9,
            children: <Widget>[
              _detailTile(theme, 'Code', party.partyCode),
              _detailTile(theme, 'Type', party.type.toUpperCase()),
              _detailTile(theme, 'Mobile', party.mobile.isEmpty ? '-' : party.mobile),
              _detailTile(theme, 'Email', party.email.isEmpty ? '-' : party.email),
              _detailTile(theme, 'City', party.city.isEmpty ? '-' : party.city),
              _detailTile(theme, 'State', party.state.isEmpty ? '-' : party.state),
              _detailTile(
                theme,
                isReceivable ? 'Total Debit' : 'Debit',
                controller.formatCurrency(controller.totalDebit.value),
              ),
              _detailTile(
                theme,
                isReceivable ? 'Total Credit' : 'Credit',
                controller.formatCurrency(controller.totalCredit.value),
              ),
              _detailTile(
                theme,
                'Closing',
                '${controller.formatCurrency(controller.closingBalance.value)} ${controller.closingType.value.toUpperCase()}',
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildFilterRow(ThemeData theme) {
    final accent = theme.colorScheme.primary;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        gradient: LinearGradient(
          colors: <Color>[
            accent.withValues(alpha: .06),
            theme.colorScheme.surface,
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        border: Border.all(color: accent.withValues(alpha: .12)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              Icon(Icons.tune_rounded, size: 16, color: accent),
              const SizedBox(width: 8),
              Text(
                'Filters',
                style: theme.textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Select date range to review linked voucher movement.',
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontSize: 11.5,
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: <Widget>[
              Expanded(
                child: CustomTextField(
                  controller: controller.fromDateController,
                  label: 'From Date',
                  hintText: 'YYYY-MM-DD',
                  readOnly: true,
                  suffixIcon: Icons.calendar_today_outlined,
                  bottomPadding: 0,
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
                  suffixIcon: Icons.calendar_today_outlined,
                  bottomPadding: 0,
                  onTap: () => _pickDate(controller.toDateController),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Align(
            alignment: Alignment.centerLeft,
            child: SizedBox(
              height: 38,
              child: FilledButton.icon(
                onPressed: controller.loadHistory,
                style: FilledButton.styleFrom(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 8,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  textStyle: theme.textTheme.labelLarge?.copyWith(
                    fontWeight: FontWeight.w700,
                    fontSize: 12.5,
                  ),
                ),
                icon: const Icon(Icons.filter_alt_rounded, size: 15),
                label: const Text('Apply'),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHistorySection(ThemeData theme) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        color: theme.cardColor,
        border: Border.all(
          color: theme.dividerColor.withValues(alpha: .18),
        ),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: theme.colorScheme.shadow.withValues(alpha: .03),
            blurRadius: 12,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              Icon(
                Icons.history_rounded,
                size: 18,
                color: theme.colorScheme.primary,
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  'Voucher Movement',
                  style: theme.textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 5,
                ),
                decoration: BoxDecoration(
                  color: theme.colorScheme.primary.withValues(alpha: .08),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  '${controller.transactions.length} entries',
                  style: theme.textTheme.labelSmall?.copyWith(
                    color: theme.colorScheme.primary,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Voucher links, running balance, debit, and credit movement.',
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontSize: 11.5,
            ),
          ),
          const SizedBox(height: 10),
          MastersTableShell(
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
              final debit = _amount(row['debit']);
              final credit = _amount(row['credit']);
              return DataRow(
                cells: <DataCell>[
                  masterTextCell(
                    controller.formatDate((row['date'] ?? '').toString()),
                  ),
                  DataCell(
                    InkWell(
                      onTap: () => _openVoucher(row),
                      borderRadius: BorderRadius.circular(8),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(vertical: 2),
                        child: Text(
                          (row['voucher_number'] ?? '-').toString(),
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: theme.colorScheme.primary,
                            fontWeight: FontWeight.w700,
                            decoration: TextDecoration.underline,
                            decorationColor: theme.colorScheme.primary.withValues(alpha: .45),
                          ),
                        ),
                      ),
                    ),
                  ),
                  DataCell(
                    Center(
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: theme.colorScheme.primary.withValues(alpha: .08),
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(
                          _titleCase((row['voucher_type'] ?? '-').toString()),
                          style: theme.textTheme.labelSmall?.copyWith(
                            color: theme.colorScheme.primary,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ),
                  ),
                  masterTextCell((row['description'] ?? '-').toString()),
                  DataCell(
                    Center(
                      child: Text(
                        debit > 0 ? controller.formatCurrency(debit) : '-',
                        style: theme.textTheme.bodyMedium?.copyWith(
                          fontWeight: FontWeight.w700,
                          color: debit > 0 ? const Color(0xFF2563EB) : null,
                        ),
                      ),
                    ),
                  ),
                  DataCell(
                    Center(
                      child: Text(
                        credit > 0 ? controller.formatCurrency(credit) : '-',
                        style: theme.textTheme.bodyMedium?.copyWith(
                          fontWeight: FontWeight.w700,
                          color: credit > 0 ? const Color(0xFFF59E0B) : null,
                        ),
                      ),
                    ),
                  ),
                  masterTextCell(
                    '${controller.formatCurrency(row['running_balance'])} ${(row['running_type'] ?? '').toString().toUpperCase()}',
                  ),
                ],
              );
            }).toList(),
          ),
        ],
      ),
    );
  }

  Widget _detailTile(ThemeData theme, String label, String value) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: theme.dividerColor.withValues(alpha: .18),
        ),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: theme.colorScheme.shadow.withValues(alpha: .025),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: <Widget>[
          Text(
            label,
            style: theme.textTheme.labelSmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w600,
              fontSize: 10.5,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: theme.textTheme.bodyMedium?.copyWith(
              fontWeight: FontWeight.w700,
              fontSize: 12.5,
            ),
          ),
        ],
      ),
    );
  }

  double _amount(dynamic value) {
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value?.toString() ?? '') ?? 0;
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
          amount: _amount(row['debit']) > 0 ? _amount(row['debit']) : _amount(row['credit']),
          narration: (row['description'] ?? '').toString(),
        ),
      ),
    );
  }

  Future<void> _pickDate(TextEditingController target) async {
    final current = DateTime.tryParse(target.text) ?? DateTime.now();
    final selected = await showDatePicker(
      context: context,
      initialDate: current,
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
