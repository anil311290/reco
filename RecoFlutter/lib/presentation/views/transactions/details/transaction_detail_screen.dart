import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/config/api_endpoints.dart';
import '../../../../core/network/api_client.dart';
import '../../../../core/utils/amount_formatter.dart';
import '../../../../core/utils/app_date_formatter.dart';
import '../../../../data/models/transactions/transaction_entities.dart';
import '../../masters/history/party_history_screen.dart';
import '../../masters/widgets/masters_ui_components.dart';
import '../../reports/settlement_details_sheet.dart';
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
        leading: IconButton(
          tooltip: 'Back',
          icon: const Icon(Icons.arrow_back_rounded),
          onPressed: () => Get.back<void>(),
        ),
        title: Text(
          _screenTitle(record),
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: <Widget>[
          if (record.kind == TransactionRecordKind.voucher) ...<Widget>[
            if (onPrint != null)
              IconButton(
                tooltip: 'PDF',
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
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(12, 12, 12, 12),
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
            : _InvoiceDetailBody(
                record: record,
                invoiceLines: invoiceLines,
                referenceNumber: referenceNumber,
                financialYearName: financialYearName,
                createdAt: createdAt,
                updatedAt: updatedAt,
                onPost: onPost,
                onCancel: onCancel,
                onDelete: onDelete,
                onEdit: onEdit,
                onPrint: onPrint,
                onRecordPayment: onRecordPayment,
              ),
      ),
    );
  }

  Future<void> _runAction(TransactionActionCallback action) async {
    await action();
    if (Get.isOverlaysOpen) {
      return;
    }
  }

  static double? _payloadAmount(TransactionRecord record, String key) {
    final value = record.rawPayload[key];
    if (value == null) return null;
    if (value is num) return value.toDouble();
    return double.tryParse(value.toString());
  }

  static String _screenTitle(TransactionRecord record) {
    switch (record.kind) {
      case TransactionRecordKind.voucher:
        return '${record.typeLabel.isEmpty ? 'Voucher' : record.typeLabel} Voucher';
      case TransactionRecordKind.salesInvoice:
        final number = record.number.trim();
        return number.isEmpty ? 'Sales Invoice' : 'Sales Invoice $number';
      case TransactionRecordKind.purchaseInvoice:
        final number = record.number.trim();
        return number.isEmpty ? 'Purchase Invoice' : 'Purchase Invoice $number';
    }
  }

  static String _currency(double value) => '₹${value.toStringAsFixed(2)}';

  static String _fallback(String value) => value.trim().isEmpty ? '-' : value.trim();

  static String _shortDate(String value) {
    return AppDateFormatter.formatDisplay(value);
  }

  static List<Map<String, dynamic>> _extractInvoiceLines(TransactionRecord record) {
    if (record.kind == TransactionRecordKind.voucher) {
      return const <Map<String, dynamic>>[];
    }
    final payload = record.rawPayload;
    final merged = <Map<String, dynamic>>[];

    void addList(dynamic source) {
      if (source is! List) {
        return;
      }
      for (final item in source.whereType<Map>()) {
        merged.add(Map<String, dynamic>.from(item));
      }
    }

    // Prefer unified `lines` (web show page). Fall back to item + service lines.
    if (payload['lines'] is List) {
      addList(payload['lines']);
    } else {
      addList(payload['item_lines']);
      addList(payload['service_lines']);
    }
    return merged;
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
    final formatted = AppDateFormatter.formatDateTime(value, fallback: '');
    if (formatted.trim().isNotEmpty) {
      // Match web `d-M-Y H:i` (24h) when possible.
      final parsed = AppDateFormatter.parse(value);
      if (parsed != null) {
        final hour = parsed.hour.toString().padLeft(2, '0');
        final minute = parsed.minute.toString().padLeft(2, '0');
        return '${AppDateFormatter.formatDisplay(parsed)} $hour:$minute';
      }
      return formatted;
    }
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
        if (_hasVoucherActions) ...<Widget>[
          const SizedBox(height: 16),
          Text(
            'Actions',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
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
                  onTap: () async => onPost!(),
                ),
              if (onCancel != null)
                _ActionChipButton(
                  label: 'Cancel',
                  icon: Icons.cancel_outlined,
                  background: const Color(0xFFFFEDD5),
                  foreground: const Color(0xFF9A3412),
                  onTap: () async => onCancel!(),
                ),
              if (onEdit != null)
                _ActionChipButton(
                  label: 'Edit',
                  icon: Icons.edit_outlined,
                  background: const Color(0xFFDBEAFE),
                  foreground: const Color(0xFF1D4ED8),
                  onTap: () async => onEdit!(),
                ),
              if (onPrint != null)
                _ActionChipButton(
                  label: 'PDF',
                  icon: Icons.picture_as_pdf_outlined,
                  background: const Color(0xFFFEE2E2),
                  foreground: const Color(0xFFB91C1C),
                  onTap: () async => onPrint!(),
                ),
              if (onDelete != null)
                _ActionChipButton(
                  label: 'Delete',
                  icon: Icons.delete_outline_rounded,
                  background: Theme.of(context).colorScheme.errorContainer,
                  foreground: Theme.of(context).colorScheme.onErrorContainer,
                  onTap: () async => onDelete!(),
                ),
            ],
          ),
        ],
        if (record.id != null &&
            (record.type == 'payment' || record.type == 'receipt')) ...<Widget>[
          const SizedBox(height: 16),
          _PaymentSettlementSection(record: record),
        ],
      ],
    );
  }

  bool get _hasVoucherActions =>
      onPost != null ||
      onCancel != null ||
      onDelete != null ||
      onEdit != null ||
      onPrint != null;

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
    final reference = (record.rawPayload['reference_number'] ?? '').toString().trim();
    if (reference.isNotEmpty) {
      items.add(('Reference', reference, null));
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
              masterColumn(context, 'Dr (₹)', size: ColumnSize.M),
              masterColumn(context, 'Cr (₹)', size: ColumnSize.M),
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

class _InvoiceDetailBody extends StatelessWidget {
  const _InvoiceDetailBody({
    required this.record,
    required this.invoiceLines,
    required this.referenceNumber,
    required this.financialYearName,
    required this.createdAt,
    required this.updatedAt,
    this.onPost,
    this.onCancel,
    this.onDelete,
    this.onEdit,
    this.onPrint,
    this.onRecordPayment,
  });

  final TransactionRecord record;
  final List<Map<String, dynamic>> invoiceLines;
  final String referenceNumber;
  final String financialYearName;
  final String createdAt;
  final String updatedAt;
  final TransactionActionCallback? onPost;
  final TransactionActionCallback? onCancel;
  final TransactionActionCallback? onDelete;
  final TransactionActionCallback? onEdit;
  final TransactionActionCallback? onPrint;
  final TransactionActionCallback? onRecordPayment;

  @override
  Widget build(BuildContext context) {
    final isPurchase = record.kind == TransactionRecordKind.purchaseInvoice;
    final counterpartyTitle = isPurchase ? 'Supplier' : 'Bill To';
    final theme = Theme.of(context);
    final party = record.rawPayload['party'];
    final partyAddress = party is Map
        ? (party['address'] ?? '').toString().trim()
        : '';
    final partyCity = party is Map
        ? (party['city'] ?? '').toString().trim()
        : '';
    final partyState = party is Map
        ? (party['state'] ?? '').toString().trim()
        : '';
    final partyGst = party is Map
        ? (party['gstin'] ?? party['gst_number'] ?? '').toString().trim()
        : '';
    final addressLines = <String>[
      if (partyAddress.isNotEmpty) partyAddress,
      if (partyCity.isNotEmpty || partyState.isNotEmpty)
        [partyCity, partyState]
            .where((item) => item.trim().isNotEmpty)
            .join(', '),
      if (partyGst.isNotEmpty) 'GSTIN: $partyGst',
    ];

    final invoiceCard = _InvoiceViewCard(
      record: record,
      counterpartyTitle: counterpartyTitle,
      addressLines: addressLines,
      referenceNumber: referenceNumber,
      invoiceLines: invoiceLines,
    );

    final statusCard = _InvoiceStatusCard(
      record: record,
      financialYearName: financialYearName,
      createdAt: createdAt,
      updatedAt: updatedAt,
    );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        // ── Web-style top action bar ──
        if (_hasTopActions) ...<Widget>[
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: <Widget>[
                if (onPost != null) ...<Widget>[
                  _HeaderActionButton(
                    label: 'Post Invoice',
                    icon: Icons.send_outlined,
                    background: const Color(0xFF2563EB),
                    foreground: Colors.white,
                    onTap: () async => onPost!(),
                  ),
                  const SizedBox(width: 8),
                ],
                if (onRecordPayment != null) ...<Widget>[
                  _HeaderActionButton(
                    label: 'Record Payment',
                    icon: Icons.payments_outlined,
                    background: const Color(0xFF16A34A),
                    foreground: Colors.white,
                    onTap: () async => onRecordPayment!(),
                  ),
                  const SizedBox(width: 8),
                ],
                if (onCancel != null) ...<Widget>[
                  _HeaderActionButton(
                    label: 'Cancel Invoice',
                    icon: Icons.cancel_outlined,
                    background: Colors.transparent,
                    foreground: const Color(0xFFD97706),
                    borderColor: const Color(0xFFD97706),
                    onTap: () async => onCancel!(),
                  ),
                  const SizedBox(width: 8),
                ],
                if (onPrint != null) ...<Widget>[
                  _HeaderActionButton(
                    label: 'PDF',
                    icon: Icons.picture_as_pdf_outlined,
                    background: Colors.transparent,
                    foreground: const Color(0xFFDC2626),
                    borderColor: const Color(0xFFDC2626),
                    onTap: () async => onPrint!(),
                  ),
                  const SizedBox(width: 8),
                ],
                if (onEdit != null) ...<Widget>[
                  _HeaderActionButton(
                    label: 'Edit',
                    icon: Icons.edit_outlined,
                    background: Colors.transparent,
                    foreground: theme.colorScheme.primary,
                    borderColor: theme.colorScheme.primary,
                    onTap: () async => onEdit!(),
                  ),
                  const SizedBox(width: 8),
                ],
                if (onDelete != null)
                  _HeaderActionButton(
                    label: 'Delete',
                    icon: Icons.delete_outline_rounded,
                    background: Colors.transparent,
                    foreground: theme.colorScheme.error,
                    borderColor: theme.colorScheme.error,
                    onTap: () async => onDelete!(),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 14),
        ],

        // ── Main invoice view + status (web: col-8 + col-4) ──
        LayoutBuilder(
          builder: (context, constraints) {
            final wide = constraints.maxWidth >= 900;
            if (wide) {
              return Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Expanded(flex: 8, child: invoiceCard),
                  const SizedBox(width: 14),
                  Expanded(flex: 4, child: statusCard),
                ],
              );
            }
            return Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: <Widget>[
                invoiceCard,
                const SizedBox(height: 12),
                statusCard,
              ],
            );
          },
        ),

        // ── Settlement history ──
        if (record.id != null) ...<Widget>[
          const SizedBox(height: 14),
          _InvoiceSettlementSection(record: record),
        ],
      ],
    );
  }

  bool get _hasTopActions =>
      onPost != null ||
      onCancel != null ||
      onDelete != null ||
      onEdit != null ||
      onPrint != null ||
      onRecordPayment != null;
}

class _InvoiceViewCard extends StatelessWidget {
  const _InvoiceViewCard({
    required this.record,
    required this.counterpartyTitle,
    required this.addressLines,
    required this.referenceNumber,
    required this.invoiceLines,
  });

  final TransactionRecord record;
  final String counterpartyTitle;
  final List<String> addressLines;
  final String referenceNumber;
  final List<Map<String, dynamic>> invoiceLines;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final isPurchase = record.kind == TransactionRecordKind.purchaseInvoice;
    final notes = record.narration.trim();

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: scheme.outlineVariant.withValues(alpha: .7),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          // Bill To / Supplier + Invoice Details
          LayoutBuilder(
            builder: (context, constraints) {
              final stacked = constraints.maxWidth < 520;
              final billTo = Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    counterpartyTitle,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: scheme.onSurfaceVariant,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    TransactionDetailScreen._fallback(record.partyName),
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  if (addressLines.isNotEmpty) ...<Widget>[
                    const SizedBox(height: 4),
                    ...addressLines.map(
                      (line) => Padding(
                        padding: const EdgeInsets.only(bottom: 2),
                        child: Text(
                          line,
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: scheme.onSurfaceVariant,
                          ),
                        ),
                      ),
                    ),
                  ],
                ],
              );

              final details = Column(
                crossAxisAlignment:
                    stacked ? CrossAxisAlignment.start : CrossAxisAlignment.end,
                children: <Widget>[
                  Text(
                    'Invoice Details',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: scheme.onSurfaceVariant,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 6),
                  _InvoiceMetaLine(
                    label: 'Invoice #:',
                    value: TransactionDetailScreen._fallback(record.number),
                    valueColor: scheme.primary,
                    alignEnd: !stacked,
                  ),
                  if (isPurchase && record.supplierReference.isNotEmpty)
                    _InvoiceMetaLine(
                      label: 'Supplier Ref:',
                      value: record.supplierReference,
                      alignEnd: !stacked,
                    ),
                  if (record.date.isNotEmpty)
                    _InvoiceMetaLine(
                      label: 'Date:',
                      value: TransactionDetailScreen._shortDate(record.date),
                      alignEnd: !stacked,
                    ),
                  if (record.dueDate.isNotEmpty)
                    _InvoiceMetaLine(
                      label: 'Due Date:',
                      value: TransactionDetailScreen._shortDate(record.dueDate),
                      alignEnd: !stacked,
                    ),
                  if (!isPurchase && referenceNumber.isNotEmpty)
                    _InvoiceMetaLine(
                      label: 'Ref:',
                      value: referenceNumber,
                      alignEnd: !stacked,
                    ),
                ],
              );

              if (stacked) {
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    billTo,
                    const SizedBox(height: 16),
                    details,
                  ],
                );
              }
              return Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Expanded(child: billTo),
                  const SizedBox(width: 16),
                  Expanded(child: details),
                ],
              );
            },
          ),

          const SizedBox(height: 18),

          // Line items
          if (invoiceLines.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 16),
              child: Text(
                'No line items.',
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: scheme.onSurfaceVariant,
                ),
              ),
            )
          else
            _InvoiceLinesTable(lines: invoiceLines),

          const SizedBox(height: 16),

          // Notes + Amounts (web footer of invoice card)
          LayoutBuilder(
            builder: (context, constraints) {
              final stacked = constraints.maxWidth < 520;
              final notesBlock = notes.isEmpty
                  ? const SizedBox.shrink()
                  : Text.rich(
                      TextSpan(
                        children: <InlineSpan>[
                          TextSpan(
                            text: 'Notes: ',
                            style: theme.textTheme.bodyMedium?.copyWith(
                              fontWeight: FontWeight.w700,
                              color: scheme.onSurfaceVariant,
                            ),
                          ),
                          TextSpan(
                            text: notes,
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: scheme.onSurfaceVariant,
                            ),
                          ),
                        ],
                      ),
                    );
              final amounts = _InvoiceAmountsBlock(record: record);
              if (stacked) {
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: <Widget>[
                    if (notes.isNotEmpty) ...<Widget>[
                      notesBlock,
                      const SizedBox(height: 14),
                    ],
                    amounts,
                  ],
                );
              }
              return Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Expanded(child: notesBlock),
                  const SizedBox(width: 16),
                  SizedBox(width: 220, child: amounts),
                ],
              );
            },
          ),
        ],
      ),
    );
  }
}

class _InvoiceMetaLine extends StatelessWidget {
  const _InvoiceMetaLine({
    required this.label,
    required this.value,
    this.valueColor,
    this.alignEnd = false,
  });

  final String label;
  final String value;
  final Color? valueColor;
  final bool alignEnd;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Align(
        alignment: alignEnd ? Alignment.centerRight : Alignment.centerLeft,
        child: Text.rich(
          TextSpan(
            children: <InlineSpan>[
              TextSpan(
                text: '$label ',
                style: theme.textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
              TextSpan(
                text: value,
                style: theme.textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                  color: valueColor,
                ),
              ),
            ],
          ),
          textAlign: alignEnd ? TextAlign.right : TextAlign.left,
        ),
      ),
    );
  }
}

class _InvoiceStatusCard extends StatelessWidget {
  const _InvoiceStatusCard({
    required this.record,
    required this.financialYearName,
    required this.createdAt,
    required this.updatedAt,
  });

  final TransactionRecord record;
  final String financialYearName;
  final String createdAt;
  final String updatedAt;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final status = record.statusLabel.trim().isEmpty
        ? (record.status.trim().isEmpty
            ? '—'
            : _titleCaseStatus(record.status))
        : record.statusLabel.trim();
    final color = _statusColor(record.status, theme.colorScheme);
    final overdue = _isOverdue(record);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: theme.colorScheme.outlineVariant.withValues(alpha: .7),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            'Invoice Status',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            decoration: BoxDecoration(
              color: color,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              status,
              style: theme.textTheme.titleSmall?.copyWith(
                fontWeight: FontWeight.w800,
                color: Colors.white,
              ),
            ),
          ),
          if (overdue) ...<Widget>[
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFFEE2E2),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Row(
                children: <Widget>[
                  const Icon(
                    Icons.warning_amber_rounded,
                    color: Color(0xFFB91C1C),
                    size: 18,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'This invoice is overdue',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: const Color(0xFFB91C1C),
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 14),
          Divider(color: theme.dividerColor.withValues(alpha: .5)),
          const SizedBox(height: 10),
          if (createdAt.isNotEmpty)
            _StatusMetaLine(label: 'Created', value: createdAt),
          if (updatedAt.isNotEmpty)
            _StatusMetaLine(label: 'Updated', value: updatedAt),
          if (financialYearName.isNotEmpty)
            _StatusMetaLine(label: 'Financial Year', value: financialYearName),
        ],
      ),
    );
  }

  static bool _isOverdue(TransactionRecord record) {
    if (record.status.toLowerCase() == 'cancelled' ||
        record.status.toLowerCase() == 'paid' ||
        record.balanceDue <= 0) {
      return false;
    }
    final due = AppDateFormatter.parse(record.dueDate);
    if (due == null) {
      return record.status.toLowerCase() == 'overdue';
    }
    final today = DateTime.now();
    final dueDay = DateTime(due.year, due.month, due.day);
    final todayDay = DateTime(today.year, today.month, today.day);
    return todayDay.isAfter(dueDay);
  }

  static String _titleCaseStatus(String value) {
    if (value.isEmpty) return value;
    return value[0].toUpperCase() + value.substring(1);
  }
}

class _StatusMetaLine extends StatelessWidget {
  const _StatusMetaLine({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text.rich(
        TextSpan(
          children: <InlineSpan>[
            TextSpan(
              text: '$label: ',
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
            TextSpan(
              text: value,
              style: theme.textTheme.bodyMedium,
            ),
          ],
        ),
      ),
    );
  }
}

class _InvoiceAmountsBlock extends StatelessWidget {
  const _InvoiceAmountsBlock({required this.record});

  final TransactionRecord record;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final subtotal =
        TransactionDetailScreen._payloadAmount(record, 'subtotal') ??
            record.amount;
    final discount =
        TransactionDetailScreen._payloadAmount(record, 'discount_amount') ?? 0;
    final tax =
        TransactionDetailScreen._payloadAmount(record, 'tax_amount') ?? 0;

    Widget row(
      String label,
      String value, {
      Color? valueColor,
      bool bold = false,
      double fontSize = 14,
    }) {
      return Padding(
        padding: const EdgeInsets.only(bottom: 8),
        child: Row(
          children: <Widget>[
            Expanded(
              child: Text(
                label,
                style: theme.textTheme.bodyMedium?.copyWith(
                  fontWeight: bold ? FontWeight.w800 : FontWeight.w600,
                  fontSize: fontSize,
                ),
              ),
            ),
            Text(
              value,
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: bold ? FontWeight.w800 : FontWeight.w700,
                color: valueColor,
                fontSize: fontSize,
              ),
            ),
          ],
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: <Widget>[
        row('Subtotal', TransactionDetailScreen._currency(subtotal)),
        if (discount > 0)
          row(
            'Discount',
            '-${TransactionDetailScreen._currency(discount)}',
            valueColor: scheme.error,
          ),
        row('Tax', TransactionDetailScreen._currency(tax)),
        row(
          'Total',
          TransactionDetailScreen._currency(record.amount),
          valueColor: scheme.primary,
          bold: true,
          fontSize: 18,
        ),
        row(
          'Paid',
          TransactionDetailScreen._currency(record.amountPaid),
          valueColor: const Color(0xFF16A34A),
        ),
        if (record.balanceDue > 0)
          row(
            'Balance Due',
            TransactionDetailScreen._currency(record.balanceDue),
            valueColor: scheme.error,
            bold: true,
          ),
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
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(12),
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

class _HeaderActionButton extends StatelessWidget {
  const _HeaderActionButton({
    required this.label,
    required this.icon,
    required this.background,
    required this.foreground,
    required this.onTap,
    this.borderColor,
  });

  final String label;
  final IconData icon;
  final Color background;
  final Color foreground;
  final Color? borderColor;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: background,
      borderRadius: BorderRadius.circular(10),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(10),
            border: borderColor == null
                ? null
                : Border.all(color: borderColor!),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              Icon(icon, size: 16, color: foreground),
              const SizedBox(width: 6),
              Text(
                label,
                style: TextStyle(
                  color: foreground,
                  fontWeight: FontWeight.w700,
                  fontSize: 13,
                ),
              ),
            ],
          ),
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
    ];
    final tableHeight = (42.0 + (rows.length * 58.0)).clamp(100.0, 480.0);

    return SizedBox(
      height: tableHeight,
      child: MastersTableShell(
        isLoading: false,
        emptyText: 'No invoice lines found.',
        minWidth: 860,
        columns: <DataColumn2>[
          masterColumn(context, '#', fixedWidth: 48, size: ColumnSize.S),
          masterColumn(context, 'Description', size: ColumnSize.L),
          masterColumn(context, 'Qty', size: ColumnSize.S),
          masterColumn(context, 'Unit Price', size: ColumnSize.M),
          masterColumn(context, 'Disc %', size: ColumnSize.S),
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
    final itemName = item is Map
        ? (item['name'] ?? '').toString().trim()
        : '';
    final accountName = account is Map
        ? (account['account_name'] ?? account['name'] ?? '').toString().trim()
        : '';
    final description = (line['description'] ?? '').toString().trim();
    final title = itemName.isNotEmpty
        ? itemName
        : accountName.isNotEmpty
            ? accountName
            : (description.isNotEmpty ? description : 'Line Item');
    final subtitle = description.isNotEmpty &&
            description.toLowerCase() != title.toLowerCase()
        ? description
        : '';
    final qty = _asDouble(line['quantity']);
    // Service lines may only have amount (treated as unit price / total base).
    final unitPrice = line.containsKey('unit_price')
        ? _asDouble(line['unit_price'])
        : _asDouble(line['amount']);
    final discount = _asDouble(
      line['discount_percentage'] ?? line['discount'] ?? line['discount_percent'],
    );
    final tax = _asDouble(line['tax_amount']);
    final total = line.containsKey('total')
        ? _asDouble(line['total'])
        : (_asDouble(line['amount']) + tax);

    return DataRow(
      cells: <DataCell>[
        masterTextCell('$index'),
        DataCell(
          Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.center,
              children: <Widget>[
                Text(
                  title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.center,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                if (subtitle.isNotEmpty) ...<Widget>[
                  const SizedBox(height: 2),
                  Text(
                    subtitle,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
        DataCell(
          Center(
            child: Text(
              qty > 0
                  ? qty.toStringAsFixed(2)
                  : (line.containsKey('quantity') ? '0.00' : '-'),
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ),
        DataCell(
          Center(
            child: Text(
              '₹${unitPrice.toStringAsFixed(2)}',
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ),
        DataCell(
          Center(
            child: Text(
              '${discount.toStringAsFixed(2)}%',
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ),
        DataCell(
          Center(
            child: Text(
              '₹${tax.toStringAsFixed(2)}',
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ),
        DataCell(
          Center(
            child: Text(
              '₹${total.toStringAsFixed(2)}',
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _InvoiceSettlementSection extends StatefulWidget {
  const _InvoiceSettlementSection({required this.record});

  final TransactionRecord record;

  @override
  State<_InvoiceSettlementSection> createState() =>
      _InvoiceSettlementSectionState();
}

class _InvoiceSettlementSectionState extends State<_InvoiceSettlementSection> {
  bool _loading = true;
  List<Map<String, dynamic>> _rows = <Map<String, dynamic>>[];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (widget.record.id == null) {
      setState(() => _loading = false);
      return;
    }
    try {
      final invoiceType =
          widget.record.kind == TransactionRecordKind.purchaseInvoice
              ? 'purchase'
              : 'sales';
      final response = await Get.find<ApiClient>().get<Map<String, dynamic>>(
        ApiEndpoints.reportsInvoiceSettlementDetails,
        queryParameters: <String, dynamic>{
          'invoice_type': invoiceType,
          'invoice_id': widget.record.id,
        },
      );
      final data = response.data?['data'];
      final list = <Map<String, dynamic>>[];
      if (data is Map && data['settlements'] is List) {
        for (final item in (data['settlements'] as List).whereType<Map>()) {
          list.add(Map<String, dynamic>.from(item));
        }
      }
      if (mounted) {
        setState(() {
          _rows = list;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return _SectionCard(
      title: 'Settlement History',
      children: <Widget>[
        if (_loading)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 12),
            child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
          )
        else if (_rows.isEmpty)
          Text(
            'No settlements yet.',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
          )
        else
          ..._rows.map((row) {
            final voucher = (row['voucher_number'] ?? '-').toString();
            final amount = AmountFormatter.currency(
              row['amount_settled'] ?? row['amount'] ?? 0,
            );
            final date = AppDateFormatter.formatDisplay(
              (row['voucher_date'] ?? '').toString(),
            );
            final status = (row['status'] ?? '').toString();
            return ListTile(
              contentPadding: EdgeInsets.zero,
              dense: true,
              title: Text(
                voucher,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                      color: Theme.of(context).colorScheme.primary,
                    ),
              ),
              subtitle: Text(
                [if (date.isNotEmpty && date != '-') date, if (status.isNotEmpty) status]
                    .join(' · '),
              ),
              trailing: Text(
                amount,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
              ),
              onTap: row['voucher_id'] == null
                  ? null
                  : () => showPaymentSettlementDetails(
                        voucherId: row['voucher_id'],
                        title: voucher,
                      ),
            );
          }),
      ],
    );
  }
}

class _PaymentSettlementSection extends StatefulWidget {
  const _PaymentSettlementSection({required this.record});

  final TransactionRecord record;

  @override
  State<_PaymentSettlementSection> createState() =>
      _PaymentSettlementSectionState();
}

class _PaymentSettlementSectionState extends State<_PaymentSettlementSection> {
  bool _loading = true;
  List<Map<String, dynamic>> _rows = <Map<String, dynamic>>[];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (widget.record.id == null) {
      setState(() => _loading = false);
      return;
    }
    try {
      final response = await Get.find<ApiClient>().get<Map<String, dynamic>>(
        ApiEndpoints.reportsPaymentSettlementDetails,
        queryParameters: <String, dynamic>{'voucher_id': widget.record.id},
      );
      final data = response.data?['data'];
      final list = <Map<String, dynamic>>[];
      if (data is Map && data['invoices_settled'] is List) {
        for (final item in (data['invoices_settled'] as List).whereType<Map>()) {
          list.add(Map<String, dynamic>.from(item));
        }
      }
      if (mounted) {
        setState(() {
          _rows = list;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return _SectionCard(
      title: 'Invoices Settled',
      children: <Widget>[
        if (_loading)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 12),
            child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
          )
        else if (_rows.isEmpty)
          Text(
            'No invoice mappings for this voucher.',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
          )
        else
          ..._rows.map((row) {
            final invoice = (row['invoice_number'] ?? '-').toString();
            final amount = AmountFormatter.currency(
              row['amount_settled'] ?? row['amount_allocated'] ?? 0,
            );
            final type = (row['invoice_type'] ?? '').toString();
            final status = (row['status'] ?? '').toString();
            return ListTile(
              contentPadding: EdgeInsets.zero,
              dense: true,
              title: Text(
                invoice,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                      color: Theme.of(context).colorScheme.primary,
                    ),
              ),
              subtitle: Text(
                [if (type.isNotEmpty) type, if (status.isNotEmpty) status]
                    .join(' · '),
              ),
              trailing: Text(
                amount,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
              ),
              onTap: row['invoice_id'] == null
                  ? null
                  : () => showInvoiceSettlementDetails(
                        invoiceType: (row['invoice_type'] ?? 'sales').toString(),
                        invoiceId: row['invoice_id'],
                        title: invoice,
                      ),
            );
          }),
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
      return const Color(0xFF16A34A);
    case 'sent':
    case 'credit_note':
      return const Color(0xFF0EA5E9);
    case 'draft':
      return const Color(0xFF6B7280);
    case 'partial':
    case 'verified':
      return const Color(0xFFD97706);
    case 'cancelled':
    case 'overdue':
      return scheme.error;
    default:
      return scheme.primary;
  }
}
