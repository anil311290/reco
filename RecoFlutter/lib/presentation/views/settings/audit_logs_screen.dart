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

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Audit Logs',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: <Widget>[
          IconButton(
            onPressed: controller.loadLogs,
            icon: const Icon(Icons.refresh_rounded),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: Obx(
        () => RefreshIndicator(
          onRefresh: controller.loadLogs,
          child: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: <Widget>[
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
                sliver: SliverToBoxAdapter(
                  child: _AuditHero(total: controller.total.value),
                ),
              ),
              SliverPadding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                sliver: SliverToBoxAdapter(
                  child: SizedBox(
                    height: 104,
                    child: ListView(
                      scrollDirection: Axis.horizontal,
                      children: <Widget>[
                        AuditStatCard(
                          label: 'Total Logs',
                          value:
                              '${controller.statistics['total_logs'] ?? 0}',
                          icon: FontAwesomeIcons.clockRotateLeft,
                          color: theme.colorScheme.primary,
                        ),
                        const SizedBox(width: 10),
                        AuditStatCard(
                          label: 'Today',
                          value:
                              '${controller.statistics['today_logs'] ?? 0}',
                          icon: FontAwesomeIcons.calendarDay,
                          color: Colors.blue,
                        ),
                        const SizedBox(width: 10),
                        AuditStatCard(
                          label: 'This Month',
                          value:
                              '${controller.statistics['month_logs'] ?? 0}',
                          icon: FontAwesomeIcons.calendar,
                          color: Colors.green,
                        ),
                        const SizedBox(width: 10),
                        AuditStatCard(
                          label: 'Modules',
                          value:
                              '${(controller.statistics['by_module'] as Map?)?.length ?? 0}',
                          icon: FontAwesomeIcons.layerGroup,
                          color: Colors.orange,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                sliver: SliverToBoxAdapter(
                  child: _AuditFilterCard(controller: controller),
                ),
              ),
              if (controller.isLoading.value && controller.logs.isEmpty)
                const SliverFillRemaining(
                  hasScrollBody: false,
                  child: Center(child: CircularProgressIndicator()),
                )
              else if (controller.logs.isEmpty)
                const SliverFillRemaining(
                  hasScrollBody: false,
                  child: Center(child: Text('No audit logs found.')),
                )
              else
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (_, index) {
                        if (index == controller.logs.length) {
                          return Padding(
                            padding: const EdgeInsets.only(top: 12),
                            child: Center(
                              child: controller.isLoadingMore.value
                                  ? const CircularProgressIndicator()
                                  : OutlinedButton(
                                      onPressed: controller.hasMore
                                          ? controller.loadMore
                                          : null,
                                      child: const Text('Load More'),
                                    ),
                            ),
                          );
                        }
                        final item = controller.logs[index];
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: AuditLogCard(
                            log: item,
                            onTap: () => _openDetail(item),
                          ),
                        );
                      },
                      childCount:
                          controller.logs.length + (controller.hasMore ? 1 : 0),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  void _openDetail(Map<String, dynamic> log) {
    final id = int.tryParse(log['id']?.toString() ?? '');
    if (id == null) {
      return;
    }
    Get.to(
      () => AuditLogDetailScreen(
        logId: id,
        initialLog: log,
      ),
      binding: BindingsBuilder(
        () {
          Get.put(
            AuditLogDetailController(Get.find<AuditLogsRepository>()),
          );
        },
      ),
    );
  }
}

class _AuditHero extends StatelessWidget {
  const _AuditHero({required this.total});

  final int total;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final primary = theme.colorScheme.primary;

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: <Color>[
            primary.withValues(alpha: .18),
            theme.colorScheme.secondary.withValues(alpha: .12),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: primary.withValues(alpha: .16)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            'Track every important change across modules.',
            style: theme.textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            '$total audit records available with module, user, action and change history.',
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
        ],
      ),
    );
  }
}

class _AuditFilterCard extends StatelessWidget {
  const _AuditFilterCard({required this.controller});

  final AuditLogsController controller;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(
          color: Theme.of(context).dividerColor.withValues(alpha: .45),
        ),
      ),
      child: Column(
        children: <Widget>[
          CustomTextField(
            label: 'Search',
            controller: controller.searchController,
            hintText: 'Search by description or module',
            prefixIcon: Icons.search_rounded,
          ),
          Obx(
            () => Row(
              children: <Widget>[
                Expanded(
                  child: CustomDropdown<String>(
                    label: 'Action',
                    value: controller.selectedAction.value.isEmpty
                        ? null
                        : controller.selectedAction.value,
                    items: controller.actionOptions,
                    hint: 'All Actions',
                    enableSearch: false,
                    itemLabelBuilder: auditLabelize,
                    onChanged: (value) =>
                        controller.selectedAction.value = value ?? '',
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: CustomDropdown<String>(
                    label: 'Module',
                    value: controller.selectedModule.value.isEmpty
                        ? null
                        : controller.selectedModule.value,
                    items: controller.moduleOptions,
                    hint: 'All Modules',
                    enableSearch: false,
                    itemLabelBuilder: auditLabelize,
                    onChanged: (value) =>
                        controller.selectedModule.value = value ?? '',
                  ),
                ),
              ],
            ),
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
                final user = controller.userOptions.firstWhereOrNull(
                  (item) => item['id'].toString() == id,
                );
                return (user?['name'] ?? 'User').toString();
              },
              onChanged: (value) =>
                  controller.selectedUserId.value = value ?? '',
            ),
          ),
          Row(
            children: <Widget>[
              Expanded(
                child: OutlinedButton(
                  onPressed: () {
                    controller.clearFilters();
                    controller.loadLogs();
                  },
                  child: const Text('Reset'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: FilledButton.icon(
                  onPressed: controller.applyFilters,
                  icon: const Icon(Icons.filter_alt_rounded),
                  label: const Text('Apply'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
