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
    super.key,
  });

  final TransactionRecord record;
  final TransactionActionCallback? onPost;
  final TransactionActionCallback? onCancel;
  final TransactionActionCallback? onDelete;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: Text(
          _screenTitle(record),
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
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
                _InfoTile(label: 'Party', value: _fallback(record.partyName)),
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
            if (_hasActions) ...[
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
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  bool get _hasActions => onPost != null || onCancel != null || onDelete != null;

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
              _StatusPill(label: TransactionDetailScreen._fallback(record.statusLabel), color: accent),
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
