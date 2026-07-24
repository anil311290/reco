import '../../../core/config/api_endpoints.dart';
import '../base/offline_first_repository.dart';

class AuditLogsRepository extends OfflineFirstRepository {
  AuditLogsRepository(
    super.apiClient,
    super.databaseService,
    super.networkMonitorService,
    super.syncService,
  );

  static const String _module = 'audit_logs';

  Future<Map<String, dynamic>> getAuditLogs({
    String? search,
    String? action,
    String? module,
    String? userId,
    int page = 1,
    int perPage = 25,
  }) async {
    final queryParameters = <String, dynamic>{
      if (search != null && search.isNotEmpty) 'search': search,
      if (action != null && action.isNotEmpty) 'action': action,
      if (module != null && module.isNotEmpty) 'module': module,
      if (userId != null && userId.isNotEmpty) 'user_id': userId,
      'page': page,
      'per_page': perPage,
    };
    final cacheKey = buildCacheKey(ApiEndpoints.auditLogs, queryParameters);
    final cached = await getCachedObject(cacheKey);

    if (cached != null) {
      if (await networkMonitorService.hasInternetNow()) {
        Future<void>.microtask(
          () => refreshAuditLogs(
            search: search,
            action: action,
            module: module,
            userId: userId,
            page: page,
            perPage: perPage,
          ),
        );
      }
      return cached;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshAuditLogs(
        search: search,
        action: action,
        module: module,
        userId: userId,
        page: page,
        perPage: perPage,
      );
    }

    return <String, dynamic>{
      'logs': <Map<String, dynamic>>[],
      'pagination': <String, dynamic>{
        'current_page': 1,
        'last_page': 1,
        'per_page': perPage,
        'total': 0,
        'has_more': false,
      },
      'statistics': <String, dynamic>{},
      'filters': <String, dynamic>{},
    };
  }

  Future<Map<String, dynamic>> refreshAuditLogs({
    String? search,
    String? action,
    String? module,
    String? userId,
    int page = 1,
    int perPage = 25,
  }) async {
    final queryParameters = <String, dynamic>{
      if (search != null && search.isNotEmpty) 'search': search,
      if (action != null && action.isNotEmpty) 'action': action,
      if (module != null && module.isNotEmpty) 'module': module,
      if (userId != null && userId.isNotEmpty) 'user_id': userId,
      'page': page,
      'per_page': perPage,
    };

    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.auditLogs,
      queryParameters: queryParameters,
    );

    final data = _extractMap(response.data?['data']);
    await saveCachedObject(
      cacheKey: buildCacheKey(ApiEndpoints.auditLogs, queryParameters),
      module: _module,
      endpoint: ApiEndpoints.auditLogs,
      response: data,
      queryParameters: queryParameters,
    );
    return data;
  }

  Future<Map<String, dynamic>?> getAuditLogDetail(int id) async {
    final cacheKey = buildCacheKey(ApiEndpoints.auditLogDetail(id));
    final cached = await getCachedObject(cacheKey);

    if (cached != null) {
      if (await networkMonitorService.hasInternetNow()) {
        Future<void>.microtask(() => refreshAuditLogDetail(id));
      }
      return cached;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshAuditLogDetail(id);
    }

    return cached;
  }

  Future<Map<String, dynamic>?> refreshAuditLogDetail(int id) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.auditLogDetail(id),
    );
    final data = _extractMap(response.data?['data']);
    await saveCachedObject(
      cacheKey: buildCacheKey(ApiEndpoints.auditLogDetail(id)),
      module: _module,
      endpoint: ApiEndpoints.auditLogDetail(id),
      response: data,
    );
    return data;
  }

  Map<String, dynamic> _extractMap(dynamic data) {
    if (data is Map<String, dynamic>) {
      return Map<String, dynamic>.from(data);
    }
    if (data is Map) {
      return Map<String, dynamic>.from(data);
    }
    return <String, dynamic>{};
  }
}
