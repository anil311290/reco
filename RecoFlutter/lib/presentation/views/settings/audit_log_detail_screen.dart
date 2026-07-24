import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../controllers/settings/audit_log_detail_controller.dart';
import 'widgets/audit_log_ui_components.dart';

class AuditLogDetailScreen extends StatefulWidget {
  const AuditLogDetailScreen({
    required this.logId,
    required this.initialLog,
    super.key,
  });

  final int logId;
  final Map<String, dynamic> initialLog;

  @override
  State<AuditLogDetailScreen> createState() => _AuditLogDetailScreenState();
}

class _AuditLogDetailScreenState extends State<AuditLogDetailScreen> {
  late final AuditLogDetailController controller;

  @override
  void initState() {
    super.initState();
    controller = Get.find<AuditLogDetailController>();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      controller.load(widget.logId, initialLog: widget.initialLog);
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Audit Log Detail',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      body: Obx(() {
        final log = controller.log.value;
        if (log == null && controller.isLoading.value) {
          return const Center(child: CircularProgressIndicator());
        }
        if (log == null) {
          return const SizedBox.shrink();
        }

        final oldValues = log['old_values'] is Map
            ? Map<String, dynamic>.from(log['old_values'] as Map)
            : <String, dynamic>{};
        final newValues = log['new_values'] is Map
            ? Map<String, dynamic>.from(log['new_values'] as Map)
            : <String, dynamic>{};
        final user = log['user'] is Map
            ? (log['user']['name'] ?? 'System').toString()
            : 'System';

        return RefreshIndicator(
          onRefresh: () => controller.load(widget.logId, initialLog: log),
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: <Widget>[
              Container(
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: theme.cardColor,
                  borderRadius: BorderRadius.circular(24),
                  border: Border.all(
                    color: theme.dividerColor.withValues(alpha: .45),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      'Audit Record #${log['id'] ?? '-'}',
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: <Widget>[
                        _AuditDetailChip(
                          label: auditLabelize((log['action'] ?? '').toString()),
                        ),
                        _AuditDetailChip(
                          label: auditLabelize((log['module'] ?? '').toString()),
                        ),
                        _AuditDetailChip(label: user),
                        _AuditDetailChip(
                          label: 'Record ID: ${log['record_id'] ?? '-'}',
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Text(
                      (log['description'] ?? 'No description provided.')
                          .toString(),
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              AuditDetailSection(title: 'Old Values', values: oldValues),
              const SizedBox(height: 16),
              AuditDetailSection(title: 'New Values', values: newValues),
            ],
          ),
        );
      }),
    );
  }
}

class _AuditDetailChip extends StatelessWidget {
  const _AuditDetailChip({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.primary.withValues(alpha: .08),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: Theme.of(context).textTheme.labelMedium?.copyWith(
          fontWeight: FontWeight.w700,
          color: Theme.of(context).colorScheme.primary,
        ),
      ),
    );
  }
}
