import 'dart:async';

import 'package:dio/dio.dart';

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

  static Map<String, dynamic> _emptyResult({int perPage = 25}) {
    return <String, dynamic>{
      'logs': <Map<String, dynamic>>[],
      'pagination': <String, dynamic>{
        'current_page': 1,
        'last_page': 1,
        'per_page': perPage,
        'total': 0,
        'has_more': false,
      },
      'statistics': <String, dynamic>{
        'total_logs': 0,
        'today_logs': 0,
        'month_logs': 0,
        'by_action': <String, dynamic>{},
        'by_module': <String, dynamic>{},
      },
      'filters': <String, dynamic>{
        'actions': <String>[],
        'modules': <String>[],
        'users': <Map<String, dynamic>>[],
      },
    };
  }

  Future<Map<String, dynamic>> getAuditLogs({
    String? search,
    String? action,
    String? module,
    String? userId,
    int page = 1,
    int perPage = 25,
  }) async {
    final queryParameters = _buildQueryParams(
      search: search,
      action: action,
      module: module,
      userId: userId,
      page: page,
      perPage: perPage,
    );
    final cacheKey = buildCacheKey(ApiEndpoints.auditLogs, queryParameters);
    final cached = await getCachedObject(cacheKey);

    if (cached != null) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(
          refreshAuditLogs(
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

    return _emptyResult(perPage: perPage);
  }

  Future<Map<String, dynamic>> refreshAuditLogs({
    String? search,
    String? action,
    String? module,
    String? userId,
    int page = 1,
    int perPage = 25,
  }) async {
    final queryParameters = _buildQueryParams(
      search: search,
      action: action,
      module: module,
      userId: userId,
      page: page,
      perPage: perPage,
    );

    try {
      final response = await apiClient.get<Map<String, dynamic>>(
        ApiEndpoints.auditLogs,
        queryParameters: queryParameters,
        options: Options(extra: <String, dynamic>{'silentError': true}),
      );

      final data = _extractMap(response.data?['data']);
      await saveCachedObject(
        cacheKey: buildCacheKey(ApiEndpoints.auditLogs, queryParameters),
        module: _module,
        endpoint: ApiEndpoints.auditLogs,
        response: data,
      );
      return data;
    } catch (_) {
      return _emptyResult(perPage: perPage);
    }
  }

  Future<Map<String, dynamic>?> getAuditLogDetail(int id) async {
    final cacheKey = buildCacheKey(ApiEndpoints.auditLogDetail(id));
    final cached = await getCachedObject(cacheKey);

    if (cached != null) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(refreshAuditLogDetail(id));
      }
      return cached;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshAuditLogDetail(id);
    }

    return cached;
  }

  Future<Map<String, dynamic>?> refreshAuditLogDetail(int id) async {
    try {
      final response = await apiClient.get<Map<String, dynamic>>(
        ApiEndpoints.auditLogDetail(id),
        options: Options(extra: <String, dynamic>{'silentError': true}),
      );
      final data = _extractMap(response.data?['data']);
      await saveCachedObject(
        cacheKey: buildCacheKey(ApiEndpoints.auditLogDetail(id)),
        module: _module,
        endpoint: ApiEndpoints.auditLogDetail(id),
        response: data,
      );
      return data;
    } catch (_) {
      return null;
    }
  }

  Map<String, dynamic> _buildQueryParams({
    String? search,
    String? action,
    String? module,
    String? userId,
    int page = 1,
    int perPage = 25,
  }) {
    return <String, dynamic>{
      if (search != null && search.isNotEmpty) 'search': search,
      if (action != null && action.isNotEmpty) 'action': action,
      if (module != null && module.isNotEmpty) 'module': module,
      if (userId != null && userId.isNotEmpty) 'user_id': userId,
      'page': page,
      'per_page': perPage,
    };
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
