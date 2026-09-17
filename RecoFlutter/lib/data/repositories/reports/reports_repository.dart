import 'dart:async';

import '../../../core/config/api_endpoints.dart';
import '../../models/common/paginated_result.dart';
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

  Future<PaginatedResult<Map<String, dynamic>>> getPaginatedList(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
    int page = 1,
    int perPage = 20,
  }) async {
    final params = <String, dynamic>{
      ...?queryParameters,
      'page': page,
      'per_page': perPage,
    };
    final cacheKey = buildCacheKey(endpoint, params);
    final cached = await getCachedObject(cacheKey);
    final cachedResult = _parsePaginatedResponse(
      cached,
      fallbackPage: page,
      fallbackPerPage: perPage,
    );

    if (cachedResult != null) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(
          refreshPaginatedList(
            endpoint,
            queryParameters: queryParameters,
            page: page,
            perPage: perPage,
          ),
        );
      }
      return cachedResult;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshPaginatedList(
        endpoint,
        queryParameters: queryParameters,
        page: page,
        perPage: perPage,
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

  Future<PaginatedResult<Map<String, dynamic>>> refreshPaginatedList(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
    int page = 1,
    int perPage = 20,
  }) async {
    final params = <String, dynamic>{
      ...?queryParameters,
      'page': page,
      'per_page': perPage,
    };
    final response = await apiClient.get<Map<String, dynamic>>(
      endpoint,
      queryParameters: params,
    );
    final body = response.data ?? <String, dynamic>{};
    await saveCachedObject(
      cacheKey: buildCacheKey(endpoint, params),
      module: _module,
      endpoint: endpoint,
      response: body,
      queryParameters: params,
    );
    return _parsePaginatedResponse(
          body,
          fallbackPage: page,
          fallbackPerPage: perPage,
        ) ??
        PaginatedResult<Map<String, dynamic>>(
          items: const <Map<String, dynamic>>[],
          currentPage: page,
          lastPage: page,
          perPage: perPage,
          total: 0,
        );
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

  Future<Map<String, dynamic>> exportFile(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      endpoint,
      queryParameters: queryParameters,
    );
    return response.data ?? <String, dynamic>{};
  }

  PaginatedResult<Map<String, dynamic>>? _parsePaginatedResponse(
    Map<String, dynamic>? body, {
    required int fallbackPage,
    required int fallbackPerPage,
  }) {
    if (body == null || body.isEmpty) {
      return null;
    }
    final data = body['data'];
    if (data is! Map<String, dynamic> || data['data'] is! List) {
      return null;
    }
    final items = (data['data'] as List)
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    return PaginatedResult<Map<String, dynamic>>(
      items: items,
      currentPage:
          int.tryParse(data['current_page']?.toString() ?? '$fallbackPage') ??
              fallbackPage,
      lastPage:
          int.tryParse(data['last_page']?.toString() ?? '$fallbackPage') ??
              fallbackPage,
      perPage:
          int.tryParse(data['per_page']?.toString() ?? '$fallbackPerPage') ??
              fallbackPerPage,
      total: int.tryParse(data['total']?.toString() ?? '${items.length}') ??
          items.length,
    );
  }

  Future<List<Map<String, dynamic>>> getStockValueEntries({
    required int financialYearId,
    String? fromDate,
    String? toDate,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.reportsStockValueEntries,
      queryParameters: <String, dynamic>{
        'financial_year_id': financialYearId,
        if (fromDate != null && fromDate.isNotEmpty) 'from_date': fromDate,
        if (toDate != null && toDate.isNotEmpty) 'to_date': toDate,
      },
    );
    final data = response.data?['data'];
    if (data is! List) {
      return <Map<String, dynamic>>[];
    }
    return data
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  Future<Map<String, dynamic>> saveStockValueEntry({
    required int financialYearId,
    required String valuationDate,
    required double stockValue,
    String? remarks,
  }) async {
    final response = await apiClient.post<Map<String, dynamic>>(
      ApiEndpoints.reportsStockValueEntries,
      data: <String, dynamic>{
        'financial_year_id': financialYearId,
        'valuation_date': valuationDate,
        'stock_value': stockValue,
        if (remarks != null && remarks.trim().isNotEmpty) 'remarks': remarks.trim(),
      },
    );
    await databaseService.clearCachedResponses(module: _module);
    return Map<String, dynamic>.from(response.data ?? <String, dynamic>{});
  }

  Future<Map<String, dynamic>> updateStockValueEntry({
    required int entryId,
    required int financialYearId,
    required String valuationDate,
    required double stockValue,
    String? remarks,
  }) async {
    final response = await apiClient.put<Map<String, dynamic>>(
      ApiEndpoints.stockValueEntryDetail(entryId),
      data: <String, dynamic>{
        'financial_year_id': financialYearId,
        'valuation_date': valuationDate,
        'stock_value': stockValue,
        if (remarks != null && remarks.trim().isNotEmpty) 'remarks': remarks.trim(),
      },
    );
    await databaseService.clearCachedResponses(module: _module);
    return Map<String, dynamic>.from(response.data ?? <String, dynamic>{});
  }
}
