import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../data/models/transactions/transaction_entities.dart';

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
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            _HeroCard(record: record),
            const SizedBox(height: 14),
            _SectionCard(
              title: 'Overview',
              children: <Widget>[
                _InfoTile(
                  label: record.kind == TransactionRecordKind.voucher
                      ? 'Voucher Number'
                      : 'Invoice Number',
                  value: _fallback(record.number),
                ),
                _InfoTile(label: 'Date', value: _fallback(_shortDate(record.date))),
                if (record.dueDate.isNotEmpty)
                  _InfoTile(
                    label: 'Due Date',
                    value: _fallback(_shortDate(record.dueDate)),
                  ),
                if (record.kind == TransactionRecordKind.voucher)
                  _InfoTile(label: 'Type', value: _fallback(record.typeLabel)),
                _InfoTile(label: 'Party', value: _fallback(record.partyName)),
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
                if (record.balanceDue > 0 || record.kind != TransactionRecordKind.voucher)
                  _InfoTile(label: 'Balance Due', value: _currency(record.balanceDue)),
              ],
            ),
            if (invoiceLines.isNotEmpty) ...<Widget>[
              const SizedBox(height: 12),
              _SectionCard(
                title: 'Invoice Lines',
                children: invoiceLines
                    .asMap()
                    .entries
                    .map(
                      (entry) => _InvoiceLineCard(
                        index: entry.key + 1,
                        line: entry.value,
                      ),
                    )
                    .toList(),
              ),
            ],
            if (voucherLines.isNotEmpty) ...<Widget>[
              const SizedBox(height: 12),
              _SectionCard(
                title: 'Voucher Lines',
                children: <Widget>[
                  ...voucherLines.map((line) => _VoucherLineCard(line: line)),
                  if (voucherLines.isNotEmpty)
                    _VoucherTotalsCard(
                      totalDebit: voucherDebitTotal,
                      totalCredit: voucherCreditTotal,
                    ),
                ],
              ),
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
                  if (financialYearName.isNotEmpty && record.kind != TransactionRecordKind.voucher)
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
        return '${record.typeLabel.isEmpty ? 'Voucher' : record.typeLabel} Details';
      case TransactionRecordKind.salesInvoice:
        return 'Sales Invoice Details';
      case TransactionRecordKind.purchaseInvoice:
        return 'Purchase Invoice Details';
    }
  }

  static String _currency(double value) => 'Rs ${value.toStringAsFixed(2)}';

  static String _fallback(String value) => value.trim().isEmpty ? '-' : value.trim();

  static String _shortDate(String value) =>
      value.length >= 10 ? value.substring(0, 10) : value;

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
    final lines = record.rawPayload['lines'];
    if (lines is! List) {
      return const <Map<String, dynamic>>[];
    }
    return lines
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
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

class _InvoiceLineCard extends StatelessWidget {
  const _InvoiceLineCard({
    required this.index,
    required this.line,
  });

  final int index;
  final Map<String, dynamic> line;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final item = line['item'];
    final account = line['account'];
    final lineType = (line['kind'] ?? line['line_type'] ?? 'item').toString();
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

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerLowest,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: theme.colorScheme.outlineVariant.withValues(alpha: .6),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              Expanded(
                child: Text(
                  '$index. ${title.trim().isEmpty ? 'Line Item' : title}',
                  style: theme.textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              _MiniBadge(
                label: lineType == 'service' ? 'Service' : 'Item',
                color: lineType == 'service'
                    ? const Color(0xFF7C3AED)
                    : const Color(0xFF2563EB),
              ),
            ],
          ),
          if (description.isNotEmpty) ...<Widget>[
            const SizedBox(height: 4),
            Text(
              description,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ],
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: <Widget>[
              _LineMeta(label: 'Qty', value: qty > 0 ? qty.toStringAsFixed(2) : '-'),
              _LineMeta(label: 'Rate', value: 'Rs ${unitPrice.toStringAsFixed(2)}'),
              _LineMeta(label: 'Amount', value: 'Rs ${amount.toStringAsFixed(2)}'),
              _LineMeta(label: 'Tax', value: 'Rs ${tax.toStringAsFixed(2)}'),
              _LineMeta(label: 'Total', value: 'Rs ${total.toStringAsFixed(2)}'),
            ],
          ),
        ],
      ),
    );
  }
}

class _VoucherLineCard extends StatelessWidget {
  const _VoucherLineCard({required this.line});

  final Map<String, dynamic> line;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final account = line['account'];
    final party = line['party'];
    final title = account is Map<String, dynamic>
        ? (account['account_name'] ?? account['name'] ?? '').toString()
        : 'Ledger';
    final subtitle = party is Map<String, dynamic>
        ? (party['name'] ?? '').toString()
        : '';
    final debit = _asDouble(line['debit']);
    final credit = _asDouble(line['credit']);

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerLowest,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: theme.colorScheme.outlineVariant.withValues(alpha: .6),
        ),
      ),
      child: Row(
        children: <Widget>[
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  title,
                  style: theme.textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                if (subtitle.trim().isNotEmpty) ...<Widget>[
                  const SizedBox(height: 3),
                  Text(
                    subtitle,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: <Widget>[
              Text(
                debit > 0 ? 'Dr Rs ${debit.toStringAsFixed(2)}' : 'Dr -',
                style: theme.textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w700,
                  color: const Color(0xFF15803D),
                ),
              ),
              const SizedBox(height: 4),
              Text(
                credit > 0 ? 'Cr Rs ${credit.toStringAsFixed(2)}' : 'Cr -',
                style: theme.textTheme.bodySmall?.copyWith(
                  fontWeight: FontWeight.w700,
                  color: const Color(0xFFB91C1C),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _VoucherTotalsCard extends StatelessWidget {
  const _VoucherTotalsCard({
    required this.totalDebit,
    required this.totalCredit,
  });

  final double totalDebit;
  final double totalCredit;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      margin: const EdgeInsets.only(top: 4),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF23263A),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        children: <Widget>[
          Expanded(
            child: Text(
              'Totals',
              style: theme.textTheme.titleSmall?.copyWith(
                color: Colors.white,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          Text(
            'Dr Rs ${totalDebit.toStringAsFixed(2)}',
            style: theme.textTheme.bodySmall?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(width: 12),
          Text(
            'Cr Rs ${totalCredit.toStringAsFixed(2)}',
            style: theme.textTheme.bodySmall?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _MiniBadge extends StatelessWidget {
  const _MiniBadge({
    required this.label,
    required this.color,
  });

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: Theme.of(context).textTheme.labelSmall?.copyWith(
          color: color,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _LineMeta extends StatelessWidget {
  const _LineMeta({
    required this.label,
    required this.value,
  });

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: theme.colorScheme.outlineVariant.withValues(alpha: .45),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            label,
            style: theme.textTheme.labelSmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 3),
          Text(
            value,
            style: theme.textTheme.bodySmall?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
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
