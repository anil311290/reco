import 'dart:async';

import '../../../core/config/api_endpoints.dart';
import '../base/offline_first_repository.dart';

class ReportsRepository extends OfflineFirstRepository {
  ReportsRepository(
    super.apiClient,
    super.databaseService,
    super.networkMonitorService,
    super.syncService,
  );

  static const String _module = 'reports';

  Future<Map<String, dynamic>> getReport(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
  }) async {
    final cacheKey = buildCacheKey(endpoint, queryParameters);
    final cached = await getCachedObject(cacheKey) ?? <String, dynamic>{};

    if (cached.isNotEmpty) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(refreshReport(endpoint, queryParameters: queryParameters));
      }
      return cached;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshReport(endpoint, queryParameters: queryParameters);
    }

    return cached;
  }

  Future<Map<String, dynamic>> refreshReport(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      endpoint,
      queryParameters: queryParameters,
    );
    final body = response.data ?? <String, dynamic>{};
    await saveCachedObject(
      cacheKey: buildCacheKey(endpoint, queryParameters),
      module: _module,
      endpoint: endpoint,
      response: body,
      queryParameters: queryParameters,
    );
    return body;
  }

  Future<List<Map<String, dynamic>>> getFinancialYears() async {
    final result = await getReport(ApiEndpoints.financialYears);
    final data = result['data'];
    if (data is List) {
      return data
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }
    return <Map<String, dynamic>>[];
  }

  Future<Map<String, dynamic>?> getCurrentFinancialYear() async {
    final result = await getReport(ApiEndpoints.currentFinancialYear);
    final data = result['data'];
    if (data is Map<String, dynamic>) {
      return data;
    }
    return null;
  }

  Future<Map<String, dynamic>> exportPdf(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      endpoint,
      queryParameters: queryParameters,
    );
    return response.data ?? <String, dynamic>{};
  }
}
