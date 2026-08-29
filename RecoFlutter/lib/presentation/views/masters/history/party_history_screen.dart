import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../data/models/masters/master_entities.dart';
import '../../../../data/models/transactions/transaction_entities.dart';
import '../../../controllers/masters/party_history_controller.dart';
import '../../reports/widgets/report_ui_components.dart';
import '../../../views/transactions/details/transaction_detail_screen.dart';
import '../forms/party_form_sheet.dart';
import '../party_record_payment_screen.dart';
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
          'Party Details',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: <Widget>[
          Obx(() {
            final party = controller.party.value;
            if (party == null || party.id == null) {
              return const SizedBox.shrink();
            }
            return Row(
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                IconButton(
                  tooltip: 'Edit Party',
                  icon: Icon(
                    Icons.edit_outlined,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                  onPressed: () async {
                    final result = await Get.to(
                      () => PartyFormSheet(entity: party),
                    );
                    if (result == true) {
                      await controller.loadHistory(forceRefresh: true);
                    }
                  },
                ),
                IconButton(
                  tooltip: party.type == 'debtor'
                      ? 'Record Receipt'
                      : 'Record Payment',
                  icon: Icon(
                    Icons.payments_outlined,
                    color: party.type == 'debtor'
                        ? const Color(0xFF16A34A)
                        : const Color(0xFFDC2626),
                  ),
                  onPressed: () async {
                    final updated = await openPartyRecordPayment(party);
                    if (updated) {
                      await controller.loadHistory(forceRefresh: true);
                    }
                  },
                ),
              ],
            );
          }),
        ],
      ),
      body: Obx(
        () => ListView(
          padding: const EdgeInsets.all(10),
          children: <Widget>[
            _buildPartyDetailCard(theme),
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

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        color: theme.cardColor,
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
                      '${party.partyCode} • ${_titleCase(party.type)}',
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
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: <Widget>[
              _summaryTile(theme, 'Code', party.partyCode),
              _summaryTile(theme, 'Type', _titleCase(party.type)),
              _summaryTile(theme, 'Mobile', party.mobile.isEmpty ? '—' : party.mobile),
              _summaryTile(theme, 'Email', party.email.isEmpty ? '—' : party.email),
              if (party.gstin.isNotEmpty) _summaryTile(theme, 'GSTIN', party.gstin),
              _summaryTile(
                theme,
                'Status',
                party.isActive ? 'Active' : 'Inactive',
                valueColor: party.isActive
                    ? const Color(0xFF15803D)
                    : theme.colorScheme.onSurfaceVariant,
              ),
              _summaryTile(
                theme,
                'Closing Balance',
                controller.formatCurrency(controller.closingBalance.value),
                trailing: _typeBadge(
                  theme,
                  _shortDrCr(controller.closingType.value),
                  controller.closingType.value.toLowerCase() == 'debit'
                      ? const Color(0xFF2563EB)
                      : const Color(0xFF16A34A),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _summaryTile(
    ThemeData theme,
    String label,
    String value, {
    Color? valueColor,
    Widget? trailing,
  }) {
    return SizedBox(
      width: (MediaQuery.of(context).size.width - 42) / 2,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            label,
            style: theme.textTheme.labelSmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w600,
              fontSize: 10.5,
            ),
          ),
          const SizedBox(height: 3),
          Row(
            children: <Widget>[
              Expanded(
                child: Text(
                  value,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                    color: valueColor,
                  ),
                ),
              ),
              if (trailing != null) ...<Widget>[
                const SizedBox(width: 6),
                trailing,
              ],
            ],
          ),
        ],
      ),
    );
  }

  Widget _typeBadge(ThemeData theme, String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .10),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withValues(alpha: .24)),
      ),
      child: Text(
        text,
        style: theme.textTheme.labelSmall?.copyWith(
          color: color,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }

  Widget _buildHistorySection(ThemeData theme) {
    final tableRows = <DataRow>[
      ...controller.transactions.map(_buildTransactionRow),
      _buildTotalRow(context, theme),
    ];
    final calculatedHeight = 44.0 + (tableRows.length * 56.0);
    final tableHeight = calculatedHeight.clamp(170.0, 520.0);

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
                  'Transaction History',
                  style: theme.textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              Row(
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  _ExportActionChip(
                    label: 'Excel',
                    icon: Icons.table_view_rounded,
                    color: const Color(0xFF15803D),
                    onTap: controller.exportExcel,
                  ),
                  const SizedBox(width: 8),
                  _ExportActionChip(
                    label: 'PDF',
                    icon: Icons.picture_as_pdf_rounded,
                    color: const Color(0xFFDC2626),
                    onTap: controller.exportPdf,
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Dr ${controller.formatCurrency(controller.totalDebit.value)} • '
            'Cr ${controller.formatCurrency(controller.totalCredit.value)} • '
            'Closing ${controller.formatCurrency(controller.closingBalance.value)} '
            '${_shortDrCr(controller.closingType.value)}',
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontSize: 11.5,
            ),
          ),
          const SizedBox(height: 10),
          SizedBox(
            height: tableHeight,
            child: MastersTableShell(
              isLoading: controller.isLoading.value && controller.transactions.isEmpty,
              emptyText: 'No voucher relation found',
              minWidth: 980,
              columns: <DataColumn2>[
                masterColumn(context, 'Date', size: ColumnSize.M),
                masterColumn(context, 'Voucher #', size: ColumnSize.M),
                masterColumn(context, 'Particulars', size: ColumnSize.L),
                masterColumn(context, 'Dr (₹)', size: ColumnSize.M),
                masterColumn(context, 'Cr (₹)', size: ColumnSize.M),
                masterColumn(context, 'Balance (₹)', size: ColumnSize.M),
              ],
              rows: tableRows,
            ),
          ),
          const SizedBox(height: 10),
          Text(
            'Showing ${controller.transactions.isEmpty ? 0 : 1} to ${controller.transactions.length} of ${controller.transactions.length} entries',
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontSize: 11.5,
            ),
          ),
        ],
      ),
    );
  }

  DataRow _buildTransactionRow(Map<String, dynamic> row) {
    final theme = Theme.of(context);
    final debit = _amount(row['debit']);
    final credit = _amount(row['credit']);

    return DataRow(
      cells: <DataCell>[
        masterTextCell(_formatHistoryDate((row['date'] ?? '').toString())),
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
          Text(
            (row['description'] ?? '-').toString(),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
        ),
        DataCell(
          Center(
            child: Text(
              debit > 0 ? _plainAmount(debit) : '—',
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w700,
                color: debit > 0 ? const Color(0xFF111827) : null,
              ),
            ),
          ),
        ),
        DataCell(
          Center(
            child: Text(
              credit > 0 ? _plainAmount(credit) : '—',
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w700,
                color: credit > 0 ? const Color(0xFF111827) : null,
              ),
            ),
          ),
        ),
        DataCell(
          Center(
            child: RichText(
              textAlign: TextAlign.center,
              text: TextSpan(
                style: theme.textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w800,
                  color: theme.colorScheme.onSurface,
                ),
                children: <InlineSpan>[
                  TextSpan(text: _plainAmount(row['running_balance'])),
                  TextSpan(
                    text: ' ${_shortDrCr((row["running_type"] ?? '').toString())}',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w500,
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  DataRow _buildTotalRow(BuildContext context, ThemeData theme) {
    return DataRow(
      color: reportTotalRowColor(context),
      cells: <DataCell>[
        const DataCell(SizedBox.shrink()),
        const DataCell(SizedBox.shrink()),
        DataCell(
          Center(
            child: Text(
              'Total',
              style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
            ),
          ),
        ),
        DataCell(
          Center(
            child: Text(
              _plainAmount(controller.totalDebit.value),
              style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
            ),
          ),
        ),
        DataCell(
          Center(
            child: Text(
              _plainAmount(controller.totalCredit.value),
              style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
            ),
          ),
        ),
        DataCell(
          Center(
            child: Text(
              '${_plainAmount(controller.closingBalance.value)} ${_shortDrCr(controller.closingType.value)}',
              style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
            ),
          ),
        ),
      ],
    );
  }

  double _amount(dynamic value) {
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value?.toString() ?? '') ?? 0;
  }

  String _plainAmount(dynamic value) => _amount(value).toStringAsFixed(2);

  String _shortDrCr(String value) {
    switch (value.trim().toLowerCase()) {
      case 'debit':
      case 'dr':
        return 'Dr';
      case 'credit':
      case 'cr':
        return 'Cr';
      default:
        return value.trim().isEmpty ? '-' : value;
    }
  }

  String _formatHistoryDate(String value) {
    if (value.trim().isEmpty) {
      return '—';
    }
    final parsed = DateTime.tryParse(value);
    if (parsed == null) {
      return controller.formatDate(value);
    }
    const months = <String>[
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'May',
      'Jun',
      'Jul',
      'Aug',
      'Sep',
      'Oct',
      'Nov',
      'Dec',
    ];
    final day = parsed.day.toString().padLeft(2, '0');
    return '$day ${months[parsed.month - 1]} ${parsed.year}';
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

  String _titleCase(String value) => value
      .split('_')
      .where((part) => part.isNotEmpty)
      .map((part) => '${part[0].toUpperCase()}${part.substring(1)}')
      .join(' ');
}

class _ExportActionChip extends StatelessWidget {
  const _ExportActionChip({
    required this.label,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        height: 34,
        padding: const EdgeInsets.symmetric(horizontal: 10),
        decoration: BoxDecoration(
          color: color.withValues(alpha: .10),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: color.withValues(alpha: .24)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            Icon(icon, size: 16, color: color),
            const SizedBox(width: 6),
            Text(
              label,
              style: Theme.of(context).textTheme.labelMedium?.copyWith(
                    color: color,
                    fontWeight: FontWeight.w700,
                  ),
            ),
          ],
        ),
      ),
    );
  }
}
