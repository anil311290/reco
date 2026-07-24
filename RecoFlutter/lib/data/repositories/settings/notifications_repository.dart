import '../../../core/config/api_endpoints.dart';
import '../../../core/network/api_client.dart';

class NotificationsRepository {
  NotificationsRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<Map<String, dynamic>> fetchNotifications({int limit = 50}) async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.notifications,
      queryParameters: <String, dynamic>{'limit': limit},
    );
    return (response.data?['data'] ?? <String, dynamic>{})
        as Map<String, dynamic>;
  }

  Future<int> markAsRead(int id) async {
    final response = await _apiClient.patch<Map<String, dynamic>>(
      '${ApiEndpoints.notifications}/$id/read',
    );
    final data = response.data?['data'];
    if (data is Map<String, dynamic>) {
      return int.tryParse(data['unread_count'].toString()) ?? 0;
    }
    return 0;
  }

  Future<int> markAllAsRead() async {
    final response = await _apiClient.post<Map<String, dynamic>>(
      ApiEndpoints.notificationsReadAll,
    );
    final data = response.data?['data'];
    if (data is Map<String, dynamic>) {
      return int.tryParse(data['unread_count'].toString()) ?? 0;
    }
    return 0;
  }
}

