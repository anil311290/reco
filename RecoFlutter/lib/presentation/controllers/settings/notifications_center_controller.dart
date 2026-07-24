import 'package:get/get.dart';

import '../../../data/repositories/settings/notifications_repository.dart';

class NotificationsCenterController extends GetxController {
  NotificationsCenterController(this._repository);

  final NotificationsRepository _repository;

  final isLoading = false.obs;
  final isMarkingAll = false.obs;
  final unreadCount = 0.obs;
  final notifications = <Map<String, dynamic>>[].obs;

  @override
  void onInit() {
    super.onInit();
    loadNotifications();
  }

  Future<void> loadNotifications() async {
    isLoading.value = true;
    try {
      final result = await _repository.fetchNotifications();
      unreadCount.value = int.tryParse(
            result['unread_count']?.toString() ?? '0',
          ) ??
          0;
      final records = result['notifications'];
      if (records is List) {
        notifications.assignAll(
          records
              .whereType<Map>()
              .map((item) => Map<String, dynamic>.from(item))
              .toList(),
        );
      } else {
        notifications.clear();
      }
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> markAsRead(Map<String, dynamic> item) async {
    final id = int.tryParse(item['id']?.toString() ?? '');
    if (id == null) {
      return;
    }
    unreadCount.value = await _repository.markAsRead(id);
    item['is_read'] = true;
    notifications.refresh();
  }

  Future<void> markAllAsRead() async {
    isMarkingAll.value = true;
    try {
      unreadCount.value = await _repository.markAllAsRead();
      for (final item in notifications) {
        item['is_read'] = true;
      }
      notifications.refresh();
    } finally {
      isMarkingAll.value = false;
    }
  }
}

