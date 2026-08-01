import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../data/models/transactions/transaction_entities.dart';
import '../../masters/history/party_history_screen.dart';
import '../../masters/widgets/masters_ui_components.dart';
import '../../reports/widgets/report_ui_components.dart';

typedef TransactionActionCallback = Future<void> Function();

class TransactionDetailScreen extends StatelessWidget {
  const TransactionDetailScreen({
    required this.record,
    this.onPost,
    this.onCancel,
    this.onDelete,
    this.onEdit,
    this.onPrint,
    this.onRecordPayment,
    super.key,
  });

  final TransactionRecord record;
  final TransactionActionCallback? onPost;
  final TransactionActionCallback? onCancel;
  final TransactionActionCallback? onDelete;
  final TransactionActionCallback? onEdit;
  final TransactionActionCallback? onPrint;
  final TransactionActionCallback? onRecordPayment;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final invoiceLines = _extractInvoiceLines(record);
    final voucherLines = _extractVoucherLines(record);
    final referenceNumber = _extractReferenceNumber(record);
    final financialYearName = _extractFinancialYearName(record);
    final createdAt = _extractDateTime(record.rawPayload['created_at']);
    final updatedAt = _extractDateTime(record.rawPayload['updated_at']);
    final voucherDebitTotal = _voucherDebitTotal(voucherLines);
    final voucherCreditTotal = _voucherCreditTotal(voucherLines);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          _screenTitle(record),
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: <Widget>[
          if (onPrint != null)
            IconButton(
              tooltip: 'Print',
              onPressed: () => _runAction(onPrint!),
              icon: const Icon(Icons.picture_as_pdf_outlined),
            ),
          if (onEdit != null)
            IconButton(
              tooltip: 'Edit',
              onPressed: () => _runAction(onEdit!),
              icon: const Icon(Icons.edit_outlined),
            ),
          if (onDelete != null)
            IconButton(
              tooltip: 'Delete',
              onPressed: () => _runAction(onDelete!),
              icon: const Icon(Icons.delete_outline_rounded),
            ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(16, 14, 16, 24),
        child: record.kind == TransactionRecordKind.voucher
            ? _VoucherDetailBody(
                record: record,
                voucherLines: voucherLines,
                voucherDebitTotal: voucherDebitTotal,
                voucherCreditTotal: voucherCreditTotal,
                financialYearName: financialYearName,
                onPost: onPost,
                onCancel: onCancel,
                onDelete: onDelete,
                onEdit: onEdit,
                onPrint: onPrint,
              )
            : Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  _HeroCard(record: record),
                  const SizedBox(height: 14),
                  _InvoiceCounterpartyCard(record: record),
                  const SizedBox(height: 12),
                  _SectionCard(
                    title: 'Overview',
                    children: <Widget>[
                      _InfoTile(
                        label: 'Invoice Number',
                        value: _fallback(record.number),
                      ),
                      _InfoTile(label: 'Date', value: _fallback(_shortDate(record.date))),
                      if (record.dueDate.isNotEmpty)
                        _InfoTile(
                          label: 'Due Date',
                          value: _fallback(_shortDate(record.dueDate)),
                        ),
                      _InfoTile(
                        label: record.kind == TransactionRecordKind.purchaseInvoice
                            ? 'Supplier'
                            : 'Customer',
                        value: _fallback(record.partyName),
                      ),
                      if (referenceNumber.isNotEmpty)
                        _InfoTile(
                          label: 'Reference',
                          value: _fallback(referenceNumber),
                        ),
                      if (financialYearName.isNotEmpty)
                        _InfoTile(
                          label: 'Financial Year',
                          value: _fallback(financialYearName),
                        ),
                      _InfoTile(label: 'Status', value: _fallback(record.statusLabel)),
                      _InfoTile(
                        label: 'Sync Status',
                        value: record.isDirty
                            ? 'Pending sync (${record.syncStatus})'
                            : _fallback(record.syncStatus),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  _SectionCard(
                    title: 'Amounts',
                    children: <Widget>[
                      _InfoTile(label: 'Total Amount', value: _currency(record.amount)),
                      if (record.amountPaid > 0)
                        _InfoTile(label: 'Amount Paid', value: _currency(record.amountPaid)),
                      _InfoTile(label: 'Balance Due', value: _currency(record.balanceDue)),
                    ],
                  ),
                  if (invoiceLines.isNotEmpty) ...<Widget>[
                    const SizedBox(height: 12),
                    Text("Line Items",style:  theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),),
                    const SizedBox(height: 12),
                    _InvoiceLinesTable(lines: invoiceLines),
                  ],
                  if (record.supplierReference.isNotEmpty || record.narration.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    _SectionCard(
                      title: 'Notes',
                      children: <Widget>[
                        if (record.supplierReference.isNotEmpty)
                          _InfoTile(
                            label: 'Reference',
                            value: _fallback(record.supplierReference),
                          ),
                        if (record.narration.isNotEmpty)
                          _InfoTile(
                            label: 'Narration / Notes',
                            value: _fallback(record.narration),
                            maxLines: 6,
                          ),
                      ],
                    ),
                  ],
                  if (createdAt.isNotEmpty ||
                      updatedAt.isNotEmpty ||
                      financialYearName.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    _SectionCard(
                      title: 'Status Details',
                      children: <Widget>[
                        if (createdAt.isNotEmpty)
                          _InfoTile(label: 'Created', value: createdAt),
                        if (updatedAt.isNotEmpty)
                          _InfoTile(label: 'Updated', value: updatedAt),
                        if (financialYearName.isNotEmpty)
                          _InfoTile(
                            label: 'Financial Year',
                            value: financialYearName,
                          ),
                      ],
                    ),
                  ],
                  if (_hasActions) ...<Widget>[
                    const SizedBox(height: 16),
                    Text(
                      'Actions',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Wrap(
                      spacing: 10,
                      runSpacing: 10,
                      children: <Widget>[
                        if (onPost != null)
                          _ActionChipButton(
                            label: 'Post',
                            icon: Icons.check_circle_outline_rounded,
                            background: const Color(0xFFDCFCE7),
                            foreground: const Color(0xFF166534),
                            onTap: () => _runAction(onPost!),
                          ),
                        if (onCancel != null)
                          _ActionChipButton(
                            label: 'Cancel',
                            icon: Icons.cancel_outlined,
                            background: const Color(0xFFFFEDD5),
                            foreground: const Color(0xFF9A3412),
                            onTap: () => _runAction(onCancel!),
                          ),
                        if (onDelete != null)
                          _ActionChipButton(
                            label: 'Delete',
                            icon: Icons.delete_outline_rounded,
                            background: scheme.errorContainer,
                            foreground: scheme.onErrorContainer,
                            onTap: () => _runAction(onDelete!),
                          ),
                        if (onEdit != null)
                          _ActionChipButton(
                            label: 'Edit',
                            icon: Icons.edit_outlined,
                            background: const Color(0xFFDBEAFE),
                            foreground: const Color(0xFF1D4ED8),
                            onTap: () => _runAction(onEdit!),
                          ),
                        if (onRecordPayment != null)
                          _ActionChipButton(
                            label: 'Record Payment',
                            icon: Icons.payments_outlined,
                            background: const Color(0xFFDCFCE7),
                            foreground: const Color(0xFF15803D),
                            onTap: () => _runAction(onRecordPayment!),
                          ),
                        if (onPrint != null)
                          _ActionChipButton(
                            label: 'Print',
                            icon: Icons.picture_as_pdf_outlined,
                            background: const Color(0xFFFEE2E2),
                            foreground: const Color(0xFFB91C1C),
                            onTap: () => _runAction(onPrint!),
                          ),
                      ],
                    ),
                  ],
                ],
              ),
      ),
    );
  }

  bool get _hasActions =>
      onPost != null ||
      onCancel != null ||
      onDelete != null ||
      onEdit != null ||
      onRecordPayment != null ||
      onPrint != null;

  Future<void> _runAction(TransactionActionCallback action) async {
    await action();
    if (Get.isOverlaysOpen) {
      return;
    }
  }

  static String _screenTitle(TransactionRecord record) {
    switch (record.kind) {
      case TransactionRecordKind.voucher:
        return '${record.typeLabel.isEmpty ? 'Voucher' : record.typeLabel} Voucher';
      case TransactionRecordKind.salesInvoice:
        return 'Sales Invoice Details';
      case TransactionRecordKind.purchaseInvoice:
        return 'Purchase Invoice Details';
    }
  }

  static String _currency(double value) => '₹${value.toStringAsFixed(2)}';

  static String _fallback(String value) => value.trim().isEmpty ? '-' : value.trim();

  static String _shortDate(String value) {
    final parsed = DateTime.tryParse(value);
    if (parsed == null) {
      return value.length >= 10 ? value.substring(0, 10) : value;
    }
    const months = <int, String>{
      1: 'Jan',
      2: 'Feb',
      3: 'Mar',
      4: 'Apr',
      5: 'May',
      6: 'Jun',
      7: 'Jul',
      8: 'Aug',
      9: 'Sep',
      10: 'Oct',
      11: 'Nov',
      12: 'Dec',
    };
    return '${parsed.day.toString().padLeft(2, '0')} ${months[parsed.month]} ${parsed.year}';
  }

  static List<Map<String, dynamic>> _extractInvoiceLines(TransactionRecord record) {
    if (record.kind == TransactionRecordKind.voucher) {
      return const <Map<String, dynamic>>[];
    }
    final payload = record.rawPayload;
    final lines = payload['lines'] ?? payload['item_lines'] ?? payload['service_lines'];
    if (lines is! List) {
      return const <Map<String, dynamic>>[];
    }
    return lines
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  static List<Map<String, dynamic>> _extractVoucherLines(TransactionRecord record) {
    if (record.kind != TransactionRecordKind.voucher) {
      return const <Map<String, dynamic>>[];
    }
    final payload = record.rawPayload;
    final candidates = <dynamic>[
      payload['lines'],
      payload['voucher_lines'],
      payload['adjustment_rows'],
      payload['payment_rows'],
      payload['entries'],
    ];
    for (final lines in candidates) {
      if (lines is List) {
        return lines
            .whereType<Map>()
            .map((item) => Map<String, dynamic>.from(item))
            .toList();
      }
    }
    return const <Map<String, dynamic>>[];
  }

  static String _extractReferenceNumber(TransactionRecord record) {
    if (record.kind != TransactionRecordKind.salesInvoice) {
      return '';
    }
    return (record.rawPayload['reference_number'] ?? '').toString().trim();
  }

  static String _extractFinancialYearName(TransactionRecord record) {
    final financialYear = record.rawPayload['financial_year'];
    if (financialYear is Map<String, dynamic>) {
      return (financialYear['name'] ?? '').toString().trim();
    }
    final financialYearName =
        (record.rawPayload['financial_year_name'] ?? '').toString().trim();
    return financialYearName;
  }

  static String _extractDateTime(dynamic value) {
    final raw = (value ?? '').toString().trim();
    if (raw.isEmpty) {
      return '';
    }
    final normalized = raw.replaceFirst('T', ' ');
    return normalized.length > 16 ? normalized.substring(0, 16) : normalized;
  }

  static double _voucherDebitTotal(List<Map<String, dynamic>> lines) {
    return lines.fold<double>(0, (sum, line) => sum + _asDouble(line['debit']));
  }

  static double _voucherCreditTotal(List<Map<String, dynamic>> lines) {
    return lines.fold<double>(0, (sum, line) => sum + _asDouble(line['credit']));
  }
}

class _VoucherDetailBody extends StatelessWidget {
  const _VoucherDetailBody({
    required this.record,
    required this.voucherLines,
    required this.voucherDebitTotal,
    required this.voucherCreditTotal,
    required this.financialYearName,
    this.onPost,
    this.onCancel,
    this.onDelete,
    this.onEdit,
    this.onPrint,
  });

  final TransactionRecord record;
  final List<Map<String, dynamic>> voucherLines;
  final double voucherDebitTotal;
  final double voucherCreditTotal;
  final String financialYearName;
  final TransactionActionCallback? onPost;
  final TransactionActionCallback? onCancel;
  final TransactionActionCallback? onDelete;
  final TransactionActionCallback? onEdit;
  final TransactionActionCallback? onPrint;

  @override
  Widget build(BuildContext context) {
    final details = _voucherDetailsItems(record, financialYearName);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text(
          record.typeLabel.isEmpty ? 'Voucher' : '${record.typeLabel} Voucher',
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.w800,
                fontSize: 17,
              ),
        ),
        const SizedBox(height: 4),
        Text(
          TransactionDetailScreen._fallback(record.number),
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
                fontSize: 12.5,
              ),
        ),
        const SizedBox(height: 14),
        LayoutBuilder(
          builder: (context, constraints) {
            final stacked = constraints.maxWidth < 900;
            final detailsCard = _SectionCard(
              title: 'Voucher Details',
              children: details
                  .map(
                    (item) => _VoucherDetailItem(
                      label: item.$1,
                      value: item.$2,
                      child: item.$3,
                    ),
                  )
                  .toList(),
            );
            final linesCard = _VoucherLinesTableCard(
              record: record,
              lines: voucherLines,
              totalDebit: voucherDebitTotal,
              totalCredit: voucherCreditTotal,
            );
            if (stacked) {
              return Column(
                children: <Widget>[
                  detailsCard,
                  const SizedBox(height: 12),
                  linesCard,
                ],
              );
            }
            return Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Expanded(flex: 4, child: detailsCard),
                const SizedBox(width: 12),
                Expanded(flex: 8, child: linesCard),
              ],
            );
          },
        ),
      ],
    );
  }

  List<(String, String, Widget?)> _voucherDetailsItems(
    TransactionRecord record,
    String financialYearName,
  ) {
    final items = <(String, String, Widget?)>[
      ('Voucher Number', TransactionDetailScreen._fallback(record.number), null),
      ('Type', TransactionDetailScreen._fallback(record.typeLabel), null),
      ('Date', TransactionDetailScreen._fallback(TransactionDetailScreen._shortDate(record.date)), null),
      (
        'Status',
        '',
        _StatusPill(
          label: TransactionDetailScreen._fallback(record.statusLabel),
          color: _statusColor(record.status, Get.theme.colorScheme),
        ),
      ),
    ];

    final partyWidget = _voucherPartyLink(record);
    if (record.partyName.trim().isNotEmpty || partyWidget != null) {
      items.add((
        'Party',
        TransactionDetailScreen._fallback(record.partyName),
        partyWidget,
      ));
    }
    items.add(('Amount', TransactionDetailScreen._currency(record.amount), null));
    if (financialYearName.trim().isNotEmpty) {
      items.add(('Financial Year', financialYearName, null));
    }
    items.add(('Narration', TransactionDetailScreen._fallback(record.narration), null));
    return items;
  }

  Widget? _voucherPartyLink(TransactionRecord record) {
    final rawParty = record.rawPayload['party'];
    if (rawParty is! Map<String, dynamic>) {
      return null;
    }
    final partyId = int.tryParse(rawParty['id']?.toString() ?? '');
    final partyName = (rawParty['name'] ?? record.partyName).toString().trim();
    if (partyId == null || partyName.isEmpty) {
      return null;
    }
    return InkWell(
      onTap: () => Get.to(() => PartyHistoryScreen(partyId: partyId)),
      child: Text(
        partyName,
        style: Get.theme.textTheme.titleMedium?.copyWith(
          fontWeight: FontWeight.w600,
          fontSize: 14,
          color: Get.theme.colorScheme.primary,
          decoration: TextDecoration.underline,
          decorationColor: Get.theme.colorScheme.primary,
        ),
      ),
    );
  }
}

class _VoucherDetailItem extends StatelessWidget {
  const _VoucherDetailItem({
    required this.label,
    required this.value,
    this.child,
  });

  final String label;
  final String value;
  final Widget? child;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            label,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                  fontSize: 11,
                ),
          ),
          const SizedBox(height: 4),
          child ??
              Text(
                value,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
              ),
        ],
      ),
    );
  }
}

class _VoucherLinesTableCard extends StatelessWidget {
  const _VoucherLinesTableCard({
    required this.record,
    required this.lines,
    required this.totalDebit,
    required this.totalCredit,
  });

  final TransactionRecord record;
  final List<Map<String, dynamic>> lines;
  final double totalDebit;
  final double totalCredit;

  @override
  Widget build(BuildContext context) {
    final tableRows = <DataRow>[
      ...lines.map((line) => _buildLineRow(context, line)),
      if (lines.isNotEmpty) _buildTotalRow(context),
    ];
    final calculatedHeight = 42.0 + (tableRows.length * 56.0);
    final tableHeight = calculatedHeight.clamp(170.0, 520.0);

    return _SectionCard(
      title: 'Voucher Lines',
      children: <Widget>[
        SizedBox(
          height: tableHeight,
          child: MastersTableShell(
            isLoading: false,
            emptyText: 'No voucher lines found.',
            minWidth: 760,
            columns: <DataColumn2>[
              masterColumn(context, 'Particulars', size: ColumnSize.L),
              masterColumn(context, 'Debit (₹)', size: ColumnSize.M),
              masterColumn(context, 'Credit (₹)', size: ColumnSize.M),
            ],
            rows: tableRows,
          ),
        ),
      ],
    );
  }

  DataRow _buildLineRow(BuildContext context, Map<String, dynamic> line) {
    final theme = Theme.of(context);
    final account = line['account'];
    final party = line['party'];
    final accountName = account is Map<String, dynamic>
        ? (account['account_name'] ?? account['name'] ?? '').toString()
        : (line['account_name'] ??
                  line['particulars'] ??
                  line['ledger_name'] ??
                  line['name'] ??
                  '—')
              .toString();
    final partyName = party is Map<String, dynamic>
        ? (party['name'] ?? '').toString()
        : (line['party_name'] ?? '').toString();
    final partyId = party is Map<String, dynamic>
        ? _parsePartyId(party['id'])
        : null;
    final debit = _asDouble(line['debit']);
    final credit = _asDouble(line['credit']);

    return DataRow(
      cells: <DataCell>[
        DataCell(
          Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
                Text(
                  accountName,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                    fontSize: 13.5,
                  ),
                ),
                if (partyName.trim().isNotEmpty) ...<Widget>[
                  const SizedBox(height: 3),
                InkWell(
                  onTap: partyId == null
                      ? null
                      : () => Get.to(() => PartyHistoryScreen(partyId: partyId)),
                  child: Text(
                    partyName,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.primary,
                      decoration: TextDecoration.underline,
                      fontSize: 11.5,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
        DataCell(
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              debit > 0 ? debit.toStringAsFixed(2) : '—',
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w700,
                fontSize: 13.5,
              ),
            ),
          ),
        ),
        DataCell(
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              credit > 0 ? credit.toStringAsFixed(2) : '—',
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w700,
                fontSize: 13.5,
              ),
            ),
          ),
        ),
      ],
    );
  }

  DataRow _buildTotalRow(BuildContext context) {
    return DataRow(
      color: reportTotalRowColor(context),
      cells: <DataCell>[
        DataCell(
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              'Total',
              style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
            ),
          ),
        ),
        DataCell(
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              totalDebit.toStringAsFixed(2),
              style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
            ),
          ),
        ),
        DataCell(
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              totalCredit.toStringAsFixed(2),
              style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
            ),
          ),
        ),
      ],
    );
  }

  int? _parsePartyId(dynamic value) {
    if (value is int) {
      return value;
    }
    return int.tryParse(value?.toString() ?? '');
  }
}

class _HeroCard extends StatelessWidget {
  const _HeroCard({required this.record});

  final TransactionRecord record;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final accent = _statusColor(record.status, theme.colorScheme);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: <Color>[
            accent.withValues(alpha: .16),
            theme.colorScheme.primary.withValues(alpha: .08),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: accent.withValues(alpha: .20)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: .7),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(
                  _iconForRecord(record),
                  color: accent,
                  size: 22,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      record.partyName.isEmpty ? record.typeLabel : record.partyName,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      record.number.isEmpty ? record.typeLabel : record.number,
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
              _StatusPill(
                label: TransactionDetailScreen._fallback(record.statusLabel),
                color: accent,
              ),
            ],
          ),
          const SizedBox(height: 14),
          Text(
            TransactionDetailScreen._currency(record.amount),
            style: theme.textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.w800,
              color: accent,
            ),
          ),
        ],
      ),
    );
  }

  static IconData _iconForRecord(TransactionRecord record) {
    switch (record.kind) {
      case TransactionRecordKind.voucher:
        switch (record.type) {
          case 'payment':
            return Icons.arrow_upward_rounded;
          case 'receipt':
            return Icons.arrow_downward_rounded;
          case 'journal':
          case 'adjustment':
            return Icons.menu_book_rounded;
          default:
            return Icons.receipt_long_rounded;
        }
      case TransactionRecordKind.salesInvoice:
        return Icons.description_outlined;
      case TransactionRecordKind.purchaseInvoice:
        return Icons.inventory_2_outlined;
    }
  }
}

class _InvoiceCounterpartyCard extends StatelessWidget {
  const _InvoiceCounterpartyCard({required this.record});

  final TransactionRecord record;

  @override
  Widget build(BuildContext context) {
    final payload = record.rawPayload;
    final party = payload['party'];
    final partyAddress = party is Map<String, dynamic>
        ? (party['address'] ?? '').toString().trim()
        : '';
    final partyCity = party is Map<String, dynamic>
        ? (party['city'] ?? '').toString().trim()
        : '';
    final partyState = party is Map<String, dynamic>
        ? (party['state'] ?? '').toString().trim()
        : '';
    final gst = party is Map<String, dynamic>
        ? (party['gstin'] ?? party['gst_number'] ?? '').toString().trim()
        : '';

    final counterpartyTitle = record.kind == TransactionRecordKind.purchaseInvoice
        ? 'Supplier'
        : 'Bill To';

    return _SectionCard(
      title: counterpartyTitle,
      children: <Widget>[
        _InfoTile(
          label: counterpartyTitle,
          value: TransactionDetailScreen._fallback(record.partyName),
        ),
        if (partyAddress.isNotEmpty)
          _InfoTile(label: 'Address', value: partyAddress, maxLines: 4),
        if (partyCity.isNotEmpty || partyState.isNotEmpty)
          _InfoTile(
            label: 'City / State',
            value: [partyCity, partyState]
                .where((item) => item.trim().isNotEmpty)
                .join(', '),
          ),
        if (gst.isNotEmpty) _InfoTile(label: 'GSTIN', value: gst),
      ],
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({
    required this.title,
    required this.children,
  });

  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Theme.of(context).colorScheme.outlineVariant.withValues(alpha: .7),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            title,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 12),
          ...children,
        ],
      ),
    );
  }
}

class _InfoTile extends StatelessWidget {
  const _InfoTile({
    required this.label,
    required this.value,
    this.maxLines = 2,
  });

  final String label;
  final String value;
  final int maxLines;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          SizedBox(
            width: 110,
            child: Text(
              label,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              value,
              maxLines: maxLines,
              overflow: TextOverflow.ellipsis,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatusPill extends StatelessWidget {
  const _StatusPill({
    required this.label,
    required this.color,
  });

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: Theme.of(context).textTheme.labelMedium?.copyWith(
          color: color,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _ActionChipButton extends StatelessWidget {
  const _ActionChipButton({
    required this.label,
    required this.icon,
    required this.background,
    required this.foreground,
    required this.onTap,
  });

  final String label;
  final IconData icon;
  final Color background;
  final Color foreground;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
        decoration: BoxDecoration(
          color: background,
          borderRadius: BorderRadius.circular(14),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            Icon(icon, size: 18, color: foreground),
            const SizedBox(width: 8),
            Text(
              label,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: foreground,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _InvoiceLinesTable extends StatelessWidget {
  const _InvoiceLinesTable({required this.lines});

  final List<Map<String, dynamic>> lines;

  @override
  Widget build(BuildContext context) {
    final rows = <DataRow>[
      ...lines.asMap().entries.map(
            (entry) => _buildRow(context, entry.key + 1, entry.value),
          ),
      if (lines.isNotEmpty) _buildTotalRow(context),
    ];
    final tableHeight = (42.0 + (rows.length * 56.0)).clamp(180.0, 520.0);

    return SizedBox(
      height: tableHeight,
      child: MastersTableShell(
        isLoading: false,
        emptyText: 'No invoice lines found.',
        minWidth: 980,
        columns: <DataColumn2>[
          masterColumn(context, '#', fixedWidth: 48, size: ColumnSize.S),
          masterColumn(context, 'Item / Service', size: ColumnSize.L),
          masterColumn(context, 'Description', size: ColumnSize.L),
          masterColumn(context, 'Qty', size: ColumnSize.S),
          masterColumn(context, 'Rate', size: ColumnSize.M),
          masterColumn(context, 'Amount', size: ColumnSize.M),
          masterColumn(context, 'Tax', size: ColumnSize.M),
          masterColumn(context, 'Total', size: ColumnSize.M),
        ],
        rows: rows,
      ),
    );
  }

  DataRow _buildRow(
    BuildContext context,
    int index,
    Map<String, dynamic> line,
  ) {
    final theme = Theme.of(context);
    final item = line['item'];
    final account = line['account'];
    final title = item is Map<String, dynamic>
        ? (item['name'] ?? '').toString()
        : account is Map<String, dynamic>
            ? (account['account_name'] ?? account['name'] ?? '').toString()
            : '';
    final description = (line['description'] ?? '').toString().trim();
    final qty = _asDouble(line['quantity']);
    final unitPrice = _asDouble(line['unit_price']);
    final amount = _asDouble(line['amount']);
    final tax = _asDouble(line['tax_amount']);
    final total = _asDouble(line['total']);

    return DataRow(
      cells: <DataCell>[
        masterTextCell('$index'),
        DataCell(
          Text(
            title.trim().isEmpty ? 'Line Item' : title,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: theme.textTheme.bodyMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
        DataCell(
          Text(
            description.isEmpty ? '-' : description,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: theme.textTheme.bodyMedium,
          ),
        ),
        DataCell(
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              qty > 0 ? qty.toStringAsFixed(2) : '-',
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ),
        DataCell(
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              '₹${unitPrice.toStringAsFixed(2)}',
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ),
        DataCell(
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              '₹${amount.toStringAsFixed(2)}',
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ),
        DataCell(
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              '₹${tax.toStringAsFixed(2)}',
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ),
        DataCell(
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              '₹${total.toStringAsFixed(2)}',
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ),
      ],
    );
  }

  DataRow _buildTotalRow(BuildContext context) {
    final totalAmount = lines.fold<double>(
      0,
      (sum, line) => sum + _asDouble(line['amount']),
    );
    final totalTax = lines.fold<double>(
      0,
      (sum, line) => sum + _asDouble(line['tax_amount']),
    );
    final grandTotal = lines.fold<double>(
      0,
      (sum, line) => sum + _asDouble(line['total']),
    );

    return DataRow(
      color: reportTotalRowColor(context),
      cells: <DataCell>[
        const DataCell(SizedBox.shrink()),
        const DataCell(SizedBox.shrink()),
        DataCell(
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              'Total',
              style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
            ),
          ),
        ),
        const DataCell(SizedBox.shrink()),
        const DataCell(SizedBox.shrink()),
        DataCell(
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              'Rs ${totalAmount.toStringAsFixed(2)}',
              style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
            ),
          ),
        ),
        DataCell(
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              'Rs ${totalTax.toStringAsFixed(2)}',
              style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
            ),
          ),
        ),
        DataCell(
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              'Rs ${grandTotal.toStringAsFixed(2)}',
              style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13),
            ),
          ),
        ),
      ],
    );
  }
}

double _asDouble(dynamic value) {
  if (value is num) {
    return value.toDouble();
  }
  return double.tryParse(value?.toString() ?? '') ?? 0;
}

Color _statusColor(String status, ColorScheme scheme) {
  switch (status.toLowerCase()) {
    case 'posted':
    case 'paid':
      return const Color(0xFF15803D);
    case 'draft':
    case 'partial':
    case 'verified':
    case 'sent':
      return const Color(0xFFD97706);
    case 'cancelled':
    case 'overdue':
      return scheme.error;
    default:
      return scheme.primary;
  }
}
