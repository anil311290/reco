import '../../../core/config/api_endpoints.dart';
import '../../../core/network/api_client.dart';

class SubscriptionsRepository {
  SubscriptionsRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<Map<String, dynamic>?> fetchCurrent() async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.currentSubscription,
    );
    final data = response.data?['data'];
    if (data is Map<String, dynamic>) {
      return data;
    }
    return null;
  }

  Future<List<Map<String, dynamic>>> fetchPlans() async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.subscriptionPlans,
    );
    final data = response.data?['data'];
    if (data is List) {
      return data
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }
    return <Map<String, dynamic>>[];
  }

  Future<List<Map<String, dynamic>>> fetchInvoices({int perPage = 10}) async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.subscriptionInvoices,
      queryParameters: <String, dynamic>{'per_page': perPage},
    );
    final data = response.data?['data'];
    if (data is List) {
      return data
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }
    if (data is Map<String, dynamic> && data['data'] is List) {
      return (data['data'] as List)
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }
    return <Map<String, dynamic>>[];
  }

  Future<List<Map<String, dynamic>>> fetchPayments({int perPage = 10}) async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.subscriptionPayments,
      queryParameters: <String, dynamic>{'per_page': perPage},
    );
    final data = response.data?['data'];
    if (data is List) {
      return data
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }
    if (data is Map<String, dynamic> && data['data'] is List) {
      return (data['data'] as List)
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }
    return <Map<String, dynamic>>[];
  }

  Future<void> cancelCurrent() async {
    await _apiClient.post<void>(ApiEndpoints.subscriptionCancel);
  }
}

