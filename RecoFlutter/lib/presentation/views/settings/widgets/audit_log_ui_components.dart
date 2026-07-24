import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';

class AuditStatCard extends StatelessWidget {
  const AuditStatCard({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
    super.key,
  });

  final String label;
  final String value;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      width: 148,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: theme.dividerColor.withValues(alpha: .45)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: color.withValues(alpha: .12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, size: 16, color: color),
          ),
          const SizedBox(height: 18),
          Text(
            value,
            style: theme.textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

class AuditLogCard extends StatelessWidget {
  const AuditLogCard({
    required this.log,
    required this.onTap,
    super.key,
  });

  final Map<String, dynamic> log;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final action = (log['action'] ?? '').toString();
    final module = (log['module'] ?? '-').toString();
    final user = log['user'] is Map<String, dynamic>
        ? (log['user']['name'] ?? 'System').toString()
        : 'System';
    final description = (log['description'] ?? '').toString();
    final amount = _extractAmount(log);
    final color = _actionColor(action);

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(24),
        child: Ink(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: theme.cardColor,
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: theme.dividerColor.withValues(alpha: .45)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Row(
                children: <Widget>[
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: .12),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Icon(_actionIcon(action), size: 16, color: color),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Text(
                          auditLabelize(action),
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          _formatDate(log['created_at']),
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                        ),
                      ],
                    ),
                  ),
                  _AuditChip(
                    label: auditLabelize(module),
                    color: theme.colorScheme.primary,
                  ),
                ],
              ),
              const SizedBox(height: 14),
              if (description.isNotEmpty)
                Text(
                  description,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.bodyMedium,
                ),
              if (description.isNotEmpty) const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: <Widget>[
                  _AuditChip(
                    label: user,
                    color: Colors.indigo,
                    icon: Icons.person_outline_rounded,
                  ),
                  _AuditChip(
                    label: log['ip_address']?.toString() ?? '-',
                    color: Colors.teal,
                    icon: Icons.lan_rounded,
                  ),
                  if (amount != null)
                    _AuditChip(
                      label: amount,
                      color: Colors.green,
                      icon: Icons.currency_rupee_rounded,
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class AuditDetailSection extends StatelessWidget {
  const AuditDetailSection({
    required this.title,
    required this.values,
    super.key,
  });

  final String title;
  final Map<String, dynamic> values;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: theme.dividerColor.withValues(alpha: .45)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            title,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 14),
          if (values.isEmpty)
            Text(
              'No values recorded.',
              style: theme.textTheme.bodyMedium?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            )
          else
            ...values.entries.map(
              (entry) => Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    SizedBox(
                      width: 118,
                      child: Text(
                        entry.key,
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        _formatValue(entry.value),
                        style: theme.textTheme.bodySmall?.copyWith(
                          height: 1.35,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _AuditChip extends StatelessWidget {
  const _AuditChip({
    required this.label,
    required this.color,
    this.icon,
  });

  final String label;
  final Color color;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          if (icon != null) ...<Widget>[
            Icon(icon, size: 13, color: color),
            const SizedBox(width: 6),
          ],
          Text(
            label,
            style: Theme.of(context).textTheme.labelMedium?.copyWith(
              color: color,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

String auditLabelize(String value) {
  return value
      .replaceAll('_', ' ')
      .split(' ')
      .where((part) => part.isNotEmpty)
      .map((part) => '${part[0].toUpperCase()}${part.substring(1)}')
      .join(' ');
}

String _formatValue(dynamic value) {
  if (value == null) {
    return '-';
  }
  if (value is List || value is Map) {
    return value.toString();
  }
  return value.toString();
}

String _formatDate(dynamic value) {
  final raw = value?.toString();
  if (raw == null || raw.isEmpty) {
    return '-';
  }
  final parsed = DateTime.tryParse(raw);
  if (parsed == null) {
    return raw;
  }
  final local = parsed.toLocal();
  final day = local.day.toString().padLeft(2, '0');
  final month = <String>[
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
  ][local.month - 1];
  final year = local.year.toString();
  final hour24 = local.hour;
  final hour12 = hour24 == 0 ? 12 : (hour24 > 12 ? hour24 - 12 : hour24);
  final minute = local.minute.toString().padLeft(2, '0');
  final meridiem = hour24 >= 12 ? 'PM' : 'AM';
  return '$day $month $year, ${hour12.toString().padLeft(2, '0')}:$minute $meridiem';
}

IconData _actionIcon(String action) {
  switch (action) {
    case 'create':
      return FontAwesomeIcons.plus;
    case 'update':
      return FontAwesomeIcons.penToSquare;
    case 'delete':
      return FontAwesomeIcons.trash;
    case 'login':
      return FontAwesomeIcons.rightToBracket;
    case 'logout':
      return FontAwesomeIcons.rightFromBracket;
    case 'status_change':
      return FontAwesomeIcons.rotate;
    default:
      return FontAwesomeIcons.clockRotateLeft;
  }
}

Color _actionColor(String action) {
  switch (action) {
    case 'create':
      return Colors.green;
    case 'update':
      return Colors.blue;
    case 'delete':
      return Colors.red;
    case 'login':
      return Colors.teal;
    case 'logout':
      return Colors.grey;
    case 'status_change':
      return Colors.orange;
    default:
      return Colors.indigo;
  }
}

String? _extractAmount(Map<String, dynamic> log) {
  final newValues = log['new_values'];
  final oldValues = log['old_values'];
  final dynamic amount =
      newValues is Map ? newValues['opening_balance'] : null;
  final dynamic fallback =
      oldValues is Map ? oldValues['opening_balance'] : null;
  final resolved = amount ?? fallback;
  return resolved?.toString();
}
