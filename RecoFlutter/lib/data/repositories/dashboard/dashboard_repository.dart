import 'dart:async';

import '../../../core/config/api_endpoints.dart';
import '../base/offline_first_repository.dart';

class DashboardRepository extends OfflineFirstRepository {
  DashboardRepository(
    super.apiClient,
    super.databaseService,
    super.networkMonitorService,
    super.syncService,
  );

  static const String _module = 'dashboard';

  Future<Map<String, dynamic>> getCachedDashboard({
    Map<String, dynamic>? queryParameters,
  }) async {
    final cacheKey = buildCacheKey(ApiEndpoints.dashboard, queryParameters);
    return await getCachedObject(cacheKey) ?? <String, dynamic>{};
  }

  Future<Map<String, dynamic>> getDashboard({
    Map<String, dynamic>? queryParameters,
  }) async {
    final cached = await getCachedDashboard(queryParameters: queryParameters);
    if (cached.isNotEmpty) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(
          refreshDashboardIfConnected(queryParameters: queryParameters),
        );
      }
      return cached;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshDashboardIfConnected(queryParameters: queryParameters);
    }

    return cached;
  }

  Future<Map<String, dynamic>> refreshDashboardIfConnected({
    Map<String, dynamic>? queryParameters,
  }) async {
    if (!await networkMonitorService.hasInternetNow()) {
      return await getCachedDashboard(queryParameters: queryParameters);
    }

    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.dashboard,
      queryParameters: queryParameters,
    );

    final body = response.data ?? <String, dynamic>{};
    await saveCachedObject(
      cacheKey: buildCacheKey(ApiEndpoints.dashboard, queryParameters),
      module: _module,
      endpoint: ApiEndpoints.dashboard,
      response: body,
      queryParameters: queryParameters,
    );

    return body;
  }
}
