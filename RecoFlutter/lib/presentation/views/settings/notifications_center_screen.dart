import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../controllers/settings/notifications_center_controller.dart';

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
                  : const Text('Mark all'),
            ),
          ),
        ],
      ),
      body: Obx(
        () => controller.isLoading.value
            ? const Center(child: CircularProgressIndicator())
            : ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: controller.notifications.length,
                itemBuilder: (context, index) {
                  final item = controller.notifications[index];
                  final isRead = item['is_read'] == true;
                  return Card(
                    child: ListTile(
                      tileColor: isRead
                          ? null
                          : Theme.of(context)
                              .colorScheme
                              .primary
                              .withValues(alpha: .05),
                      title: Text((item['title'] ?? 'Notification').toString()),
                      subtitle: Text((item['message'] ?? '').toString()),
                      trailing: isRead
                          ? null
                          : TextButton(
                              onPressed: () => controller.markAsRead(item),
                              child: const Text('Read'),
                            ),
                    ),
                  );
                },
              ),
      ),
    );
  }
}

