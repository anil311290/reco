import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../controllers/settings/support_ticket_chat_controller.dart';
import '../../controllers/settings/support_ticket_create_controller.dart';
import '../../controllers/settings/support_tickets_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import 'support_ticket_chat_screen.dart';
import 'support_ticket_create_screen.dart';
import 'widgets/support_ticket_ui_components.dart';

class SupportTicketsScreen extends GetView<SupportTicketsController> {
  const SupportTicketsScreen({super.key});

  static const List<String> _statuses = <String>[
    '',
    'open',
    'in_progress',
    'waiting_on_customer',
    'resolved',
    'closed',
  ];

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Help & Support',
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
                  : controller.loadTickets,
              icon: Icon(
                Icons.refresh_rounded,
                color: controller.isRefreshing.value
                    ? theme.colorScheme.primary
                    : null,
              ),
            ),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: Obx(
        () => RefreshIndicator(
          onRefresh: controller.loadTickets,
          child: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: <Widget>[
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
                sliver: SliverToBoxAdapter(
                  child: SupportHeroCard(
                    onCreateTicket: _openCreateTicket,
                  ),
                ),
              ),
              SliverPadding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                sliver: SliverToBoxAdapter(
                  child: SupportStatsRow(
                    items: <SupportStatItem>[
                      SupportStatItem(
                        label: 'All Tickets',
                        value: controller.stats['total'] ?? 0,
                        icon: FontAwesomeIcons.inbox.data,
                        isActive: controller.selectedStatus.value.isEmpty,
                        onTap: () => controller.applyStatus(''),
                      ),
                      SupportStatItem(
                        label: 'Open',
                        value: controller.stats['open'] ?? 0,
                        icon: FontAwesomeIcons.envelopeOpen.data,
                        color: theme.colorScheme.primary,
                        isActive: controller.selectedStatus.value == 'open',
                        onTap: () => controller.applyStatus('open'),
                      ),
                      SupportStatItem(
                        label: 'In Progress',
                        value: controller.stats['in_progress'] ?? 0,
                        icon: FontAwesomeIcons.arrowsRotate.data,
                        color: Colors.amber.shade700,
                        isActive:
                            controller.selectedStatus.value == 'in_progress',
                        onTap: () => controller.applyStatus('in_progress'),
                      ),
                      SupportStatItem(
                        label: 'Awaiting Reply',
                        value: controller.stats['waiting_on_customer'] ?? 0,
                        icon: FontAwesomeIcons.hourglassHalf.data,
                        color: Colors.lightBlue.shade700,
                        isActive:
                            controller.selectedStatus.value ==
                            'waiting_on_customer',
                        onTap: () =>
                            controller.applyStatus('waiting_on_customer'),
                      ),
                      SupportStatItem(
                        label: 'Resolved',
                        value: controller.stats['resolved'] ?? 0,
                        icon: FontAwesomeIcons.circleCheck.data,
                        color: Colors.green.shade700,
                        isActive:
                            controller.selectedStatus.value == 'resolved' ||
                            controller.selectedStatus.value == 'closed',
                        onTap: () => controller.applyStatus('resolved'),
                      ),
                    ],
                  ),
                ),
              ),
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 2),
                sliver: SliverToBoxAdapter(
                  child: Row(
                    children: <Widget>[
                      Expanded(
                        child: Obx(
                          () => Text(
                            '${controller.visibleTickets.length} ticket${controller.visibleTickets.length == 1 ? '' : 's'}'
                            '${controller.selectedStatus.value.isEmpty ? '' : ' • ${_statusHeading(controller.selectedStatus.value)}'}',
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ),
                      if (controller.selectedStatus.value.isNotEmpty)
                        TextButton(
                          onPressed: () => controller.applyStatus(''),
                          child: const Text('Reset'),
                        ),
                    ],
                  ),
                ),
              ),
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                sliver: SliverToBoxAdapter(
                  child: Row(
                    children: <Widget>[
                      Expanded(
                        child: CustomTextField(
                          label: 'Search ticket',
                          controller: controller.searchController,
                          hintText: 'Search by subject, category, ticket no.',
                          prefixIcon: Icons.search_rounded,
                          onChanged: controller.applySearch,
                        ),
                      ),
                      const SizedBox(width: 10),
                      SizedBox(
                        width: 138,
                        child: CustomDropdown<String>(
                          label: 'Status',
                          value: controller.selectedStatus.value,
                          items: _statuses,
                          hint: 'All',
                          enableSearch: false,
                          itemLabelBuilder: (status) =>
                              _statusLabel(status, controller.stats),
                          onChanged: (value) =>
                              controller.applyStatus(value ?? ''),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              if (controller.isLoading.value && controller.visibleTickets.isEmpty)
                const SliverFillRemaining(
                  hasScrollBody: false,
                  child: Center(child: CircularProgressIndicator()),
                )
              else if (controller.visibleTickets.isEmpty)
                SliverFillRemaining(
                  hasScrollBody: false,
                  child: SupportEmptyState(
                    onCreateTicket: _openCreateTicket,
                  ),
                )
              else
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 4, 16, 90),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (_, index) {
                        if (index >= controller.visibleTickets.length) {
                          controller.loadMore();
                          return Padding(
                            padding: const EdgeInsets.only(top: 8, bottom: 16),
                            child: Center(
                              child: controller.isLoadingMore.value
                                  ? const SizedBox(
                                      width: 24,
                                      height: 24,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                      ),
                                    )
                                  : Text(
                                      'Showing ${controller.visibleTickets.length} of ${controller.total.value}. Scroll for more.',
                                      style: theme.textTheme.bodySmall?.copyWith(
                                            color: theme.colorScheme.onSurfaceVariant,
                                            fontWeight: FontWeight.w600,
                                          ),
                                    ),
                            ),
                          );
                        }
                        final ticket = controller.visibleTickets[index];
                        return Padding(
                          padding: EdgeInsets.only(
                            bottom: index ==
                                    controller.visibleTickets.length - 1 &&
                                !controller.hasMore
                                ? 0
                                : 12,
                          ),
                          child: SupportTicketCard(
                            ticket: ticket,
                            onTap: () => _openChat(ticket),
                          ),
                        );
                      },
                      childCount: controller.visibleTickets.length +
                          (controller.hasMore || controller.isLoadingMore.value
                              ? 1
                              : 0),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _openCreateTicket() async {
    final ticket = await Get.to<Map<String, dynamic>>(
      () => const SupportTicketCreateScreen(),
      binding: BindingsBuilder(
        () {
          Get.put(
            SupportTicketCreateController(Get.find()),
          );
        },
      ),
    );

    if (ticket != null) {
      controller.upsertTicket(ticket);
      _openChat(ticket);
    }
  }

  void _openChat(Map<String, dynamic> ticket) {
    Get.to(
      () => SupportTicketChatScreen(initialTicket: ticket),
      binding: BindingsBuilder(
        () {
          Get.put(
            SupportTicketChatController(Get.find()),
          );
        },
      ),
    );
  }

  static String _statusLabel(String status, Map<String, int> stats) {
    if (status.isEmpty) {
      return 'All statuses (${stats['total'] ?? 0})';
    }
    final label = status.replaceAll('_', ' ').split(' ').map((word) {
      if (word.isEmpty) {
        return word;
      }
      return '${word[0].toUpperCase()}${word.substring(1)}';
    }).join(' ');
    final count = switch (status) {
      'open' => stats['open'] ?? 0,
      'in_progress' => stats['in_progress'] ?? 0,
      'waiting_on_customer' => stats['waiting_on_customer'] ?? 0,
      'resolved' || 'closed' => stats['resolved'] ?? 0,
      _ => 0,
    };
    return '$label ($count)';
  }

  static String _statusHeading(String status) {
    return status
        .replaceAll('_', ' ')
        .split(' ')
        .where((word) => word.isNotEmpty)
        .map((word) => '${word[0].toUpperCase()}${word.substring(1)}')
        .join(' ');
  }
}
