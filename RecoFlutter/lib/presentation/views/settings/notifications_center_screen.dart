import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_date_formatter.dart';
import '../../controllers/settings/notifications_center_controller.dart';
import '../../controllers/settings/audit_log_detail_controller.dart';
import '../../controllers/settings/support_ticket_chat_controller.dart';
import '../../controllers/settings/subscription_controller.dart';
import '../../../data/repositories/settings/audit_logs_repository.dart';
import '../../../data/repositories/settings/subscriptions_repository.dart';
import 'audit_log_detail_screen.dart';
import 'subscription_screen.dart';
import 'support_ticket_chat_screen.dart';

class NotificationsCenterScreen
    extends GetView<NotificationsCenterController> {
  const NotificationsCenterScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Notifications',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: <Widget>[
          Obx(
            () => TextButton(
              onPressed: controller.unreadCount.value > 0
                  ? controller.markAllAsRead
                  : null,
              child: controller.isMarkingAll.value
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('Mark all as read'),
            ),
          ),
        ],
      ),
      body: Obx(
        () => controller.isLoading.value
            ? const Center(child: CircularProgressIndicator())
            : controller.notifications.isEmpty
                ? const _EmptyNotificationsState()
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: controller.notifications.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 10),
                    itemBuilder: (context, index) {
                      final item = controller.notifications[index];
                      final isRead = item['is_read'] == true;
                      return _NotificationCard(
                        item: item,
                        isRead: isRead,
                        onOpen: () => _openNotification(item),
                        onMarkRead: isRead
                            ? null
                            : () => controller.markAsRead(item),
                      );
                    },
                  ),
      ),
    );
  }

  void _openNotification(Map<String, dynamic> item) {
    final module = (item['link_module'] ?? '').toString();
    final linkId = item['link_id'];

    if (module == 'support-tickets' || module == 'platform-support-tickets') {
      final ticketId = linkId?.toString();
      if (ticketId != null && ticketId.isNotEmpty) {
        controller.markAsRead(item);
        Get.to(
          () => SupportTicketChatScreen(initialTicket: item),
          binding: BindingsBuilder(
            () {
              Get.put(
                SupportTicketChatController(Get.find()),
              );
            },
          ),
        );
        return;
      }
    }

    if (module == 'audit-logs') {
      final logId = int.tryParse(linkId?.toString() ?? '');
      if (logId != null) {
        controller.markAsRead(item);
        Get.to(
          () => AuditLogDetailScreen(
            logId: logId,
            initialLog: item,
          ),
          binding: BindingsBuilder(
            () {
              Get.put(
                AuditLogDetailController(
                  Get.find<AuditLogsRepository>(),
                ),
              );
            },
          ),
        );
        return;
      }
    }

    if (module == 'subscriptions' || module == 'subscription') {
      controller.markAsRead(item);
      Get.to(
        () => const SubscriptionScreen(),
        binding: BindingsBuilder(
          () {
            Get.put(
              SubscriptionController(
                Get.find<SubscriptionsRepository>(),
              ),
            );
          },
        ),
      );
      return;
    }

    if (item['is_read'] != true) {
      controller.markAsRead(item);
    }
  }
}

class _NotificationCard extends StatelessWidget {
  const _NotificationCard({
    required this.item,
    required this.isRead,
    required this.onOpen,
    required this.onMarkRead,
  });

  final Map<String, dynamic> item;
  final bool isRead;
  final VoidCallback onOpen;
  final VoidCallback? onMarkRead;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final accent = isRead ? scheme.outline : scheme.primary;
    final timestamp = _formatDateTime(item['created_at'] ?? item['sent_at']);
    final hasDeepLink = _hasDeepLink(item);

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: hasDeepLink ? onOpen : null,
        borderRadius: BorderRadius.circular(20),
        child: Container(
          decoration: BoxDecoration(
            color: Theme.of(context).cardColor,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: accent.withValues(alpha: .16)),
            boxShadow: <BoxShadow>[
              BoxShadow(
                color: accent.withValues(alpha: .04),
                blurRadius: 16,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: accent.withValues(alpha: .10),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Icon(
                    isRead
                        ? FontAwesomeIcons.solidEnvelopeOpen.data
                        : FontAwesomeIcons.solidBell.data,
                    color: accent,
                    size: 16,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Row(
                        children: <Widget>[
                          Expanded(
                            child: Text(
                              (item['title'] ?? 'Notification').toString(),
                              style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                          if (!isRead)
                            Container(
                              width: 8,
                              height: 8,
                              decoration: BoxDecoration(
                                color: scheme.primary,
                                shape: BoxShape.circle,
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Text(
                        (item['message'] ?? '').toString(),
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: scheme.onSurfaceVariant,
                          height: 1.4,
                        ),
                      ),
                      if (timestamp.isNotEmpty) ...<Widget>[
                        const SizedBox(height: 8),
                        Text(
                          timestamp,
                          style: Theme.of(context).textTheme.labelMedium?.copyWith(
                            color: scheme.onSurfaceVariant,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                      if (hasDeepLink || (!isRead && onMarkRead != null)) ...<Widget>[
                        const SizedBox(height: 10),
                        Wrap(
                          spacing: 10,
                          runSpacing: 8,
                          children: <Widget>[
                            if (hasDeepLink)
                              InkWell(
                                borderRadius: BorderRadius.circular(999),
                                onTap: onOpen,
                                child: Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 12,
                                    vertical: 7,
                                  ),
                                  decoration: BoxDecoration(
                                    color: scheme.primary.withValues(alpha: .10),
                                    borderRadius: BorderRadius.circular(999),
                                  ),
                                  child: Text(
                                    'View details',
                                    style: Theme.of(context).textTheme.labelLarge?.copyWith(
                                      color: scheme.primary,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                              ),
                            if (!isRead && onMarkRead != null)
                              InkWell(
                                borderRadius: BorderRadius.circular(999),
                                onTap: onMarkRead,
                                child: Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 12,
                                    vertical: 7,
                                  ),
                                  decoration: BoxDecoration(
                                    color: scheme.primary.withValues(alpha: .10),
                                    borderRadius: BorderRadius.circular(999),
                                  ),
                                  child: Text(
                                    'Mark as read',
                                    style: Theme.of(context).textTheme.labelLarge?.copyWith(
                                      color: scheme.primary,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                              ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  static bool _hasDeepLink(Map<String, dynamic> item) {
    final module = (item['link_module'] ?? '').toString();
    final linkId = (item['link_id'] ?? '').toString();
    return linkId.isNotEmpty &&
        (module == 'support-tickets' ||
            module == 'platform-support-tickets' ||
            module == 'audit-logs' ||
            module == 'subscriptions' ||
            module == 'subscription');
  }

  static String _formatDateTime(dynamic value) {
    return AppDateFormatter.formatDateTime(value);
  }
}

class _EmptyNotificationsState extends StatelessWidget {
  const _EmptyNotificationsState();

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Container(
          width: double.infinity,
          constraints: const BoxConstraints(maxWidth: 420),
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: <Color>[
                scheme.primary.withValues(alpha: .08),
                const Color(0xFFF8FAFC),
              ],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(28),
            border: Border.all(
              color: scheme.primary.withValues(alpha: .14),
            ),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              Stack(
                alignment: Alignment.center,
                children: <Widget>[
                  Container(
                    width: 112,
                    height: 112,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: scheme.primary.withValues(alpha: .08),
                    ),
                  ),
                  Container(
                    width: 84,
                    height: 84,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: scheme.primary.withValues(alpha: .14),
                    ),
                  ),
                  Icon(
                    FontAwesomeIcons.bellSlash.data,
                    size: 30,
                    color: scheme.primary,
                  ),
                ],
              ),
              const SizedBox(height: 18),
              Text(
                'No notifications yet',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'In-app alerts for sync, support tickets, and account activity will appear here.',
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: scheme.onSurfaceVariant,
                  height: 1.45,
                ),
              ),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: .8),
                  borderRadius: BorderRadius.circular(999),
                  border: Border.all(
                    color: scheme.primary.withValues(alpha: .10),
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: <Widget>[
                    Icon(
                      Icons.notifications_active_outlined,
                      size: 16,
                      color: scheme.primary,
                    ),
                    const SizedBox(width: 8),
                    Text(
                      'You are all caught up',
                      style: Theme.of(context).textTheme.labelLarge?.copyWith(
                        color: scheme.primary,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
