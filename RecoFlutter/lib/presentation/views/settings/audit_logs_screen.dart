import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_date_formatter.dart';
import '../../../data/repositories/settings/audit_logs_repository.dart';
import '../../controllers/settings/audit_log_detail_controller.dart';
import '../../controllers/settings/audit_logs_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import '../masters/widgets/masters_ui_components.dart';
import '../reports/widgets/report_ui_components.dart';
import 'audit_log_detail_screen.dart';
import 'widgets/audit_log_ui_components.dart';

class AuditLogsScreen extends GetView<AuditLogsController> {
  const AuditLogsScreen({super.key});

  static const Color _primaryColor = Color(0xFF6366F1);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: ReportPageTitle(
          title: 'Audit Logs',
          icon: FontAwesomeIcons.clockRotateLeft.data,
          color: _primaryColor,
        ),
        actions: <Widget>[
          AnimatedRotation(
            turns: controller.refreshTurns.value,
            duration: const Duration(milliseconds: 700),
            child: IconButton(
              onPressed: controller.isRefreshing.value
                  ? null
                  : controller.loadLogs,
              icon: const Icon(Icons.refresh_rounded),
              tooltip: 'Refresh',
            ),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: Obx(() {
        final stats = controller.statistics;
        final totalLogs = controller.total.value;
        final todayLogs = stats['today_logs']?.toString() ?? '0';
        final monthLogs = stats['month_logs']?.toString() ?? '0';
        final modulesCount = stats['by_module'] is Map
            ? (stats['by_module'] as Map).length
            : 0;

        return RefreshIndicator(
          onRefresh: controller.loadLogs,
          child: ListView(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            children: <Widget>[
              _StatGrid(
                total: totalLogs,
                today: todayLogs,
                month: monthLogs,
                modules: '$modulesCount',
              ),
              const SizedBox(height: 12),
              CompactSearchFilterBar(
                hint: 'Search by description or module...',
                controller: controller.searchController,
                onChanged: (_) => controller.applyFilters(),
                onFilterTap: () => _openFilters(context),
                filterTooltip: 'Audit log filters',
                onExcelTap: () => _export(context, 'excel'),
                onPdfTap: () => _export(context, 'pdf'),
              ),
              const SizedBox(height: 12),
              if (controller.isLoading.value && controller.logs.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 60),
                  child: Center(child: CircularProgressIndicator()),
                )
              else
                _AuditTable(
                  controller: controller,
                  onDetail: _openDetail,
                ),
            ],
          ),
        );
      }),
    );
  }

  void _openDetail(Map<String, dynamic> log) {
    final id = int.tryParse(log['id']?.toString() ?? '');
    if (id == null) return;
    Get.to(
      () => AuditLogDetailScreen(logId: id, initialLog: log),
      binding: BindingsBuilder(() {
        Get.put(AuditLogDetailController(Get.find<AuditLogsRepository>()));
      }),
    );
  }

  void _export(BuildContext context, String format) {
    controller.exportLogs(format);
  }

  void _openFilters(BuildContext context) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _AuditFilterSheet(controller: controller),
    );
  }
}

class _StatGrid extends StatelessWidget {
  const _StatGrid({
    required this.total,
    required this.today,
    required this.month,
    required this.modules,
  });

  final int total;
  final String today;
  final String month;
  final String modules;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final width = constraints.maxWidth;
        final crossAxis = width >= 720 ? 4 : 2;
        final childWidth =
            (width - ((crossAxis - 1) * 20)) / crossAxis;
        return Wrap(
          spacing: 10,
          runSpacing: 10,
          children: <Widget>[
            SizedBox(
              width: childWidth,
              child: AuditStatCard(
                label: 'Total Logs',
                value: '$total',
                icon: FontAwesomeIcons.clockRotateLeft.data,
                color: const Color(0xFF6366F1),
              ),
            ),
            SizedBox(
              width: childWidth,
              child: AuditStatCard(
                label: "Today's Logs",
                value: today,
                icon: FontAwesomeIcons.calendarDay.data,
                color: const Color(0xFF3B82F6),
              ),
            ),
            SizedBox(
              width: childWidth,
              child: AuditStatCard(
                label: 'This Month',
                value: month,
                icon: FontAwesomeIcons.calendar.data,
                color: const Color(0xFF10B981),
              ),
            ),
            SizedBox(
              width: childWidth,
              child: AuditStatCard(
                label: 'Modules Tracked',
                value: modules,
                icon: FontAwesomeIcons.cubes.data,
                color: const Color(0xFFF59E0B),
              ),
            ),
          ],
        );
      },
    );
  }
}

class _AuditFilterSheet extends StatefulWidget {
  const _AuditFilterSheet({required this.controller});

  final AuditLogsController controller;

  @override
  State<_AuditFilterSheet> createState() => _AuditFilterSheetState();
}

class _AuditFilterSheetState extends State<_AuditFilterSheet> {
  late String _action;
  late String _module;
  late String _userId;

  @override
  void initState() {
    super.initState();
    _action = widget.controller.selectedAction.value;
    _module = widget.controller.selectedModule.value;
    _userId = widget.controller.selectedUserId.value;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Material(
        color: theme.cardColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
        child: SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    margin: const EdgeInsets.only(bottom: 12),
                    decoration: BoxDecoration(
                      color: theme.dividerColor,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                Text(
                  'Filters',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Filter audit logs by action, module, or user.',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
                const SizedBox(height: 14),
                CustomDropdown<String>(
                  label: 'Action',
                  value: _action.isEmpty ? null : _action,
                  items: widget.controller.actionOptions,
                  hint: 'All Actions',
                  enableSearch: false,
                  itemLabelBuilder: auditLabelize,
                  onChanged: (value) => setState(() => _action = value ?? ''),
                ),
                const SizedBox(height: 12),
                CustomDropdown<String>(
                  label: 'Module',
                  value: _module.isEmpty ? null : _module,
                  items: widget.controller.moduleOptions,
                  hint: 'All Modules',
                  enableSearch: false,
                  itemLabelBuilder: auditLabelize,
                  onChanged: (value) => setState(() => _module = value ?? ''),
                ),
                const SizedBox(height: 12),
                CustomDropdown<String>(
                  label: 'User',
                  value: _userId.isEmpty ? null : _userId,
                  items: widget.controller.userOptions
                      .map((item) => item['id'].toString())
                      .toList(),
                  hint: 'All Users',
                  itemLabelBuilder: (id) {
                    final user = widget.controller.userOptions.firstWhereOrNull(
                      (item) => item['id'].toString() == id,
                    );
                    return (user?['name'] ?? 'User').toString();
                  },
                  onChanged: (value) => setState(() => _userId = value ?? ''),
                ),
                const SizedBox(height: 16),
                Row(
                  children: <Widget>[
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () {
                          widget.controller.clearFilters();
                          if (mounted) Navigator.of(context).pop();
                        },
                        style: OutlinedButton.styleFrom(
                          minimumSize: const Size.fromHeight(45),
                          side: BorderSide(
                            color: Theme.of(context).colorScheme.primary,
                            width: 1.2,
                          ),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: const Text(
                          'Clear',
                          style: TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: FilledButton(
                        onPressed: () {
                          widget.controller.selectedAction.value = _action;
                          widget.controller.selectedModule.value = _module;
                          widget.controller.selectedUserId.value = _userId;
                          widget.controller.applyFilters();
                          if (mounted) Navigator.of(context).pop();
                        },
                        style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(45),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: const Text(
                          'Apply',
                          style: TextStyle(fontWeight: FontWeight.w800),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _AuditTable extends StatelessWidget {
  const _AuditTable({
    required this.controller,
    required this.onDetail,
  });

  final AuditLogsController controller;
  final ValueChanged<Map<String, dynamic>> onDetail;

  @override
  Widget build(BuildContext context) {
    final logs = controller.logs;
    final theme = Theme.of(context);
    final loadedCount = logs.length;
    final totalCount = controller.total.value;

    final tableRows = <DataRow>[
      ...List<DataRow>.generate(logs.length, (index) {
        final log = logs[index];
        final action = (log['action'] ?? '').toString();
        final module = (log['module'] ?? '-').toString();
        final userName = log['user'] is Map
            ? (log['user']['name'] ?? 'System').toString()
            : 'System';
        final description = (log['description'] ?? '').toString();
        final ip = (log['ip_address'] ?? '').toString();
        final amount = _extractAmount(log);
        final actionColor = _actionColor(action);

        return DataRow(
          cells: <DataCell>[
            masterTextCell('${index + 1}'),
            masterTextCell(_formatDateTime(log['created_at'])),
            masterTextCell(userName),
            DataCell(
              Center(
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 3,
                  ),
                  decoration: BoxDecoration(
                    color: actionColor.withValues(alpha: .14),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    auditLabelize(action),
                    style: TextStyle(
                      color: actionColor,
                      fontWeight: FontWeight.w800,
                      fontSize: 11,
                    ),
                  ),
                ),
              ),
            ),
            DataCell(
              Center(
                child: Text(
                  auditLabelize(module),
                  style: theme.textTheme.bodyMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
            DataCell(
              Center(
                child: Text(
                  amount ?? '-',
                  style: theme.textTheme.bodyMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ),
            DataCell(
              Center(
                child: Text(
                  description.isEmpty ? '-' : description,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ),
            ),
            masterTextCell(ip.isEmpty ? '-' : ip),
            DataCell(
              Center(
                child: MasterActionButton(
                  icon: Icons.visibility_outlined,
                  tooltip: 'View details',
                  color: const Color(0xFF6366F1),
                  onTap: () => onDetail(log),
                ),
              ),
            ),
          ],
        );
      }),
    ];

    final from = totalCount == 0 ? 0 : 1;
    final to = loadedCount;
    final height = (42.0 + (loadedCount * 52.0)).clamp(160.0, 560.0);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: <Widget>[
        Padding(
          padding: const EdgeInsets.only(bottom: 8),
          child: Text(
            'Showing $from to $to of $totalCount entries',
            style: theme.textTheme.labelMedium?.copyWith(
              fontWeight: FontWeight.w600,
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
        ),
        SizedBox(
          height: height,
          child: MastersTableShell(
            isLoading: controller.isLoading.value,
            emptyText: 'No audit logs found',
            minWidth: 1280,
            columns: <DataColumn2>[
              masterColumn(context, '#', fixedWidth: 52),
              masterColumn(context, 'Date/Time', size: ColumnSize.M),
              masterColumn(context, 'User', size: ColumnSize.M),
              masterColumn(context, 'Action', fixedWidth: 110),
              masterColumn(context, 'Module', size: ColumnSize.M),
              masterColumn(context, 'Amount', size: ColumnSize.M),
              masterColumn(context, 'Description', size: ColumnSize.L),
              masterColumn(context, 'IP Address', size: ColumnSize.M),
              masterColumn(context, 'Details', fixedWidth: 88),
            ],
            rows: tableRows,
          ),
        ),
        if (controller.hasMore)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 12),
            child: Center(
              child: controller.isLoadingMore.value
                  ? const SizedBox(
                      width: 24,
                      height: 24,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : OutlinedButton.icon(
                      onPressed: controller.loadMore,
                      icon: const Icon(Icons.expand_more_rounded, size: 18),
                      label: const Text('Load More'),
                    ),
            ),
          ),
      ],
    );
  }
}

String _formatDateTime(dynamic value) {
  final raw = value?.toString();
  if (raw == null || raw.isEmpty) return '-';
  return AppDateFormatter.formatDateTime(raw, fallback: raw);
}

String? _extractAmount(Map<String, dynamic> log) {
  final newValues = log['new_values'];
  final oldValues = log['old_values'];
  final dynamic fromNew =
      newValues is Map ? newValues['opening_balance'] : null;
  final dynamic fromOld =
      oldValues is Map ? oldValues['opening_balance'] : null;
  final dynamic amountField = log['amount'];
  final resolved = amountField ?? fromNew ?? fromOld;
  if (resolved == null) return null;
  final text = resolved.toString();
  if (text.isEmpty || text == 'null' || text == '-') return null;
  return text;
}

Color _actionColor(String action) {
  switch (action) {
    case 'create':
      return const Color(0xFF10B981);
    case 'update':
      return const Color(0xFF3B82F6);
    case 'delete':
      return const Color(0xFFEF4444);
    case 'login':
      return const Color(0xFF14B8A6);
    case 'logout':
      return const Color(0xFF6B7280);
    case 'status_change':
      return const Color(0xFFF59E0B);
    default:
      return const Color(0xFF6366F1);
  }
}
