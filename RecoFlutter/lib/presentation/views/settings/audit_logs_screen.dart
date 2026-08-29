import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../data/repositories/settings/audit_logs_repository.dart';
import '../../controllers/settings/audit_log_detail_controller.dart';
import '../../controllers/settings/audit_logs_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import 'audit_log_detail_screen.dart';
import 'widgets/audit_log_ui_components.dart';

class AuditLogsScreen extends GetView<AuditLogsController> {
  const AuditLogsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final primary = theme.colorScheme.primary;

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Audit Logs',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: <Widget>[
          AnimatedRotation(
            turns: controller.refreshTurns.value,
            duration: const Duration(milliseconds: 700),
            child: IconButton(
              onPressed: controller.isRefreshing.value
                  ? null
                  : controller.loadLogs,
              icon: Icon(
                Icons.refresh_rounded,
                color: controller.isRefreshing.value
                    ? theme.colorScheme.primary
                    : null,
              ),
              tooltip: 'Refresh',
            ),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: Obx(
        () => RefreshIndicator(
          onRefresh: controller.loadLogs,
          child: CustomScrollView(
            slivers: <Widget>[
              // ── Compact Stats Bar ──
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                sliver: SliverToBoxAdapter(
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 12,
                    ),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: <Color>[
                          primary.withValues(alpha: .12),
                          primary.withValues(alpha: .04),
                        ],
                      ),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: primary.withValues(alpha: .18),
                      ),
                    ),
                      child: Row(
                        children: <Widget>[
                          Icon(
                            FontAwesomeIcons.clockRotateLeft.data,
                          size: 18,
                          color: primary,
                        ),
                        const SizedBox(width: 10),
                        Text(
                          '${controller.total.value} records',
                          style: theme.textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const Spacer(),
                        if (controller.statistics['today_logs'] != null)
                          _MiniStat(
                            label: 'Today',
                            value:
                                '${controller.statistics['today_logs']}',
                            color: Colors.blue,
                          ),
                        const SizedBox(width: 12),
                        if (controller.statistics['month_logs'] != null)
                          _MiniStat(
                            label: 'Month',
                            value:
                                '${controller.statistics['month_logs']}',
                            color: Colors.green,
                          ),
                        const SizedBox(width: 12),
                        if (controller.statistics['by_module'] is Map)
                          _MiniStat(
                            label: 'Modules',
                            value:
                                '${(controller.statistics['by_module'] as Map).length}',
                            color: Colors.indigo,
                          ),
                      ],
                    ),
                  ),
                ),
              ),

              // ── Filters (matching web: Search, Action, Module, User) ──
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
                sliver: SliverToBoxAdapter(
                  child: Column(
                    children: <Widget>[
                      CustomTextField(
                        label: 'Search',
                        controller: controller.searchController,
                        hintText: 'Search by description or module',
                        prefixIcon: Icons.search_rounded,
                        bottomPadding: 8,
                        onFieldSubmitted: (_) => controller.applyFilters(),
                      ),
                      Row(
                        children: <Widget>[
                          Expanded(
                            child: Obx(
                              () => CustomDropdown<String>(
                                label: 'Action',
                                value: controller.selectedAction.value.isEmpty
                                    ? null
                                    : controller.selectedAction.value,
                                items: controller.actionOptions,
                                hint: 'All Actions',
                                enableSearch: false,
                                itemLabelBuilder: auditLabelize,
                                onChanged: (value) {
                                  controller.selectedAction.value =
                                      value ?? '';
                                  controller.applyFilters();
                                },
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Obx(
                              () => CustomDropdown<String>(
                                label: 'Module',
                                value: controller.selectedModule.value.isEmpty
                                    ? null
                                    : controller.selectedModule.value,
                                items: controller.moduleOptions,
                                hint: 'All Modules',
                                enableSearch: false,
                                itemLabelBuilder: auditLabelize,
                                onChanged: (value) {
                                  controller.selectedModule.value =
                                      value ?? '';
                                  controller.applyFilters();
                                },
                              ),
                            ),
                          ),
                        ],
                      ),
                      Obx(
                        () => CustomDropdown<String>(
                          label: 'User',
                          value: controller.selectedUserId.value.isEmpty
                              ? null
                              : controller.selectedUserId.value,
                          items: controller.userOptions
                              .map((item) => item['id'].toString())
                              .toList(),
                          hint: 'All Users',
                          itemLabelBuilder: (id) {
                            final user = controller.userOptions
                                .firstWhereOrNull(
                                  (item) => item['id'].toString() == id,
                                );
                            return (user?['name'] ?? 'User').toString();
                          },
                          onChanged: (value) {
                            controller.selectedUserId.value = value ?? '';
                            controller.applyFilters();
                          },
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              // ── Active filter chips ──
              Obx(() {
                final hasFilters = controller.selectedAction.value.isNotEmpty ||
                    controller.selectedModule.value.isNotEmpty ||
                    controller.selectedUserId.value.isNotEmpty;

                if (!hasFilters) return const SliverToBoxAdapter();

                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 2, 16, 8),
                  sliver: SliverToBoxAdapter(
                    child: Wrap(
                      spacing: 6,
                      runSpacing: 4,
                      children: <Widget>[
                        if (controller.selectedAction.value.isNotEmpty)
                          ActionChip(
                            avatar: const Icon(Icons.close, size: 14),
                            label: Text(
                              auditLabelize(controller.selectedAction.value),
                              style: const TextStyle(fontSize: 11),
                            ),
                            onPressed: () {
                              controller.selectedAction.value = '';
                              controller.applyFilters();
                            },
                            visualDensity: VisualDensity.compact,
                            materialTapTargetSize:
                                MaterialTapTargetSize.shrinkWrap,
                          ),
                        if (controller.selectedModule.value.isNotEmpty)
                          ActionChip(
                            avatar: const Icon(Icons.close, size: 14),
                            label: Text(
                              auditLabelize(controller.selectedModule.value),
                              style: const TextStyle(fontSize: 11),
                            ),
                            onPressed: () {
                              controller.selectedModule.value = '';
                              controller.applyFilters();
                            },
                            visualDensity: VisualDensity.compact,
                            materialTapTargetSize:
                                MaterialTapTargetSize.shrinkWrap,
                          ),
                        if (controller.selectedUserId.value.isNotEmpty)
                          ActionChip(
                            avatar: const Icon(Icons.close, size: 14),
                            label: Text(
                              controller.userOptions
                                      .firstWhereOrNull(
                                        (item) =>
                                            item['id'].toString() ==
                                            controller.selectedUserId.value,
                                      )
                                      ?.let((u) => u['name']?.toString()) ??
                                  'User',
                              style: const TextStyle(fontSize: 11),
                            ),
                            onPressed: () {
                              controller.selectedUserId.value = '';
                              controller.applyFilters();
                            },
                            visualDensity: VisualDensity.compact,
                            materialTapTargetSize:
                                MaterialTapTargetSize.shrinkWrap,
                          ),
                        ActionChip(
                          avatar: const Icon(Icons.clear_all, size: 14),
                          label: const Text(
                            'Clear all',
                            style: TextStyle(fontSize: 11),
                          ),
                          onPressed: () {
                            controller.clearFilters();
                            controller.applyFilters();
                          },
                          visualDensity: VisualDensity.compact,
                          materialTapTargetSize:
                              MaterialTapTargetSize.shrinkWrap,
                        ),
                      ],
                    ),
                  ),
                );
              }),

              // ── Loading / Empty / List ──
              if (controller.isLoading.value && controller.logs.isEmpty)
                const SliverFillRemaining(
                  hasScrollBody: false,
                  child: Center(child: CircularProgressIndicator()),
                )
              else if (controller.logs.isEmpty)
                SliverFillRemaining(
                  hasScrollBody: false,
                  child: Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: <Widget>[
                        Icon(
                          Icons.history_toggle_off_rounded,
                          size: 48,
                          color: theme.colorScheme.onSurfaceVariant
                              .withValues(alpha: .35),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'No audit logs found',
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                        ),
                      ],
                    ),
                  ),
                )
              else
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                  sliver: SliverList.separated(
                    itemCount:
                        controller.logs.length + (controller.hasMore ? 1 : 0),
                    separatorBuilder: (_, _) => const SizedBox(height: 8),
                    itemBuilder: (_, index) {
                      if (index == controller.logs.length) {
                        return Padding(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          child: Center(
                            child: controller.isLoadingMore.value
                                ? const SizedBox(
                                    width: 24,
                                    height: 24,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                    ),
                                  )
                                : OutlinedButton.icon(
                                    onPressed: controller.loadMore,
                                    icon: const Icon(
                                      Icons.expand_more_rounded,
                                      size: 18,
                                    ),
                                    label: const Text('Load More'),
                                    style: OutlinedButton.styleFrom(
                                      visualDensity: VisualDensity.compact,
                                    ),
                                  ),
                          ),
                        );
                      }
                      final item = controller.logs[index];
                      return AuditLogCard(
                        log: item,
                        onTap: () => _openDetail(item),
                      );
                    },
                  ),
                ),

              const SliverPadding(
                padding: EdgeInsets.only(bottom: 32),
              ),
            ],
          ),
        ),
      ),
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
}

// ── Mini stat dot ──
class _MiniStat extends StatelessWidget {
  const _MiniStat({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: <Widget>[
        Container(
          width: 6,
          height: 6,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 4),
        Text(
          '$value $label',
          style: Theme.of(context).textTheme.labelSmall?.copyWith(
            fontWeight: FontWeight.w700,
            color: color,
          ),
        ),
      ],
    );
  }
}

// ── Helper extension ──
extension _Let<T> on T {
  R let<R>(R Function(T it) block) => block(this);
}
