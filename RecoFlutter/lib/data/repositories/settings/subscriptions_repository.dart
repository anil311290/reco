import '../../../core/config/api_endpoints.dart';
import '../../../core/network/api_client.dart';
import '../../models/common/paginated_result.dart';

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

  Future<PaginatedResult<Map<String, dynamic>>> fetchInvoicesPage({
    int page = 1,
    int perPage = 10,
  }) async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.subscriptionInvoices,
      queryParameters: <String, dynamic>{'page': page, 'per_page': perPage},
    );
    return _parsePaginatedResponse(response.data, page: page, perPage: perPage);
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

  Future<PaginatedResult<Map<String, dynamic>>> fetchPaymentsPage({
    int page = 1,
    int perPage = 10,
  }) async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.subscriptionPayments,
      queryParameters: <String, dynamic>{'page': page, 'per_page': perPage},
    );
    return _parsePaginatedResponse(response.data, page: page, perPage: perPage);
  }

  Future<void> cancelCurrent() async {
    await _apiClient.post<void>(ApiEndpoints.subscriptionCancel);
  }

  PaginatedResult<Map<String, dynamic>> _parsePaginatedResponse(
    Map<String, dynamic>? body, {
    required int page,
    required int perPage,
  }) {
    final data = body?['data'];
    if (data is List) {
      final items = _mapList(data);
      return PaginatedResult<Map<String, dynamic>>.singlePage(
        items,
        currentPage: page,
        perPage: perPage,
        total: items.length,
      );
    }
    if (data is Map<String, dynamic> && data['data'] is List) {
      final items = _mapList(data['data'] as List);
      return PaginatedResult<Map<String, dynamic>>(
        items: items,
        currentPage:
            int.tryParse(data['current_page']?.toString() ?? '$page') ?? page,
        lastPage:
            int.tryParse(data['last_page']?.toString() ?? '$page') ?? page,
        perPage:
            int.tryParse(data['per_page']?.toString() ?? '$perPage') ?? perPage,
        total:
            int.tryParse(data['total']?.toString() ?? '${items.length}') ??
            items.length,
      );
    }
    return PaginatedResult<Map<String, dynamic>>(
      items: const <Map<String, dynamic>>[],
      currentPage: page,
      lastPage: page,
      perPage: perPage,
      total: 0,
    );
  }

  List<Map<String, dynamic>> _mapList(List<dynamic> data) {
    return data
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }
}
