import 'dart:async';

import '../../../core/config/api_endpoints.dart';
import '../../models/common/paginated_result.dart';
import '../../models/masters/master_entities.dart';
import '../base/offline_first_repository.dart';

class TaxRatesRepository extends OfflineFirstRepository {
  TaxRatesRepository(
    super.apiClient,
    super.databaseService,
    super.networkMonitorService,
    super.syncService,
  );

  static const String _module = 'tax_rates';

  Future<List<TaxRateEntity>> getTaxRates() async {
    final local = await getLocalModuleRecords(_module);
    final entities = local.map(TaxRateEntity.fromRecord).toList()
      ..sort(_sortTaxRates);

    if (entities.isNotEmpty) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(refreshTaxRates());
      }
      return entities;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshTaxRates();
    }

    return entities;
  }

  Future<PaginatedResult<TaxRateEntity>> getPaginatedTaxRates({
    Map<String, dynamic>? queryParameters,
    int page = 1,
    int perPage = 20,
  }) async {
    final local = await getLocalModuleRecords(_module);
    final entities = local.map(TaxRateEntity.fromRecord).toList()
      ..sort(_sortTaxRates);
    final filtered = _applyFilters(entities, queryParameters);
    return _slicePage(filtered, page: page, perPage: perPage);
  }

  Future<List<TaxRateEntity>> refreshTaxRates() async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.taxRates,
    );
    final records = _extractList(response.data?['data']);
    await mergeRemoteRecords(module: _module, records: records);
    final local = await getLocalModuleRecords(_module);
    return local.map(TaxRateEntity.fromRecord).toList()..sort(_sortTaxRates);
  }

  Future<PaginatedResult<TaxRateEntity>> refreshPaginatedTaxRates({
    Map<String, dynamic>? queryParameters,
    int page = 1,
    int perPage = 20,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.taxRates,
      queryParameters: <String, dynamic>{
        ...?queryParameters,
        'page': page,
        'per_page': perPage,
      },
    );
    final data = response.data?['data'];
    final records = _extractList(data);
    await mergeRemoteRecords(module: _module, records: records);

    return getPaginatedTaxRates(
      queryParameters: queryParameters,
      page: page,
      perPage: perPage,
    );
  }

  Future<String> create(TaxRateEntity entity) {
    return queueCreate(
      module: _module,
      endpoint: ApiEndpoints.taxRates,
      payload: entity.toPayload(),
    );
  }

  Future<String> update(TaxRateEntity entity) {
    final serverId = entity.id?.toString();
    return queueUpdate(
      module: _module,
      endpoint: '${ApiEndpoints.taxRates}/$serverId',
      payload: entity.toPayload(),
      localId: entity.localId ?? 'remote-$_module-$serverId',
      serverId: serverId,
    );
  }

  Future<void> delete(TaxRateEntity entity) {
    final serverId = entity.id?.toString();
    return queueDelete(
      module: _module,
      endpoint: '${ApiEndpoints.taxRates}/$serverId',
      payload: entity.toPayload(),
      localId: entity.localId ?? 'remote-$_module-$serverId',
      serverId: serverId,
    );
  }

  Future<void> toggleStatus(TaxRateEntity entity, bool isActive) async {
    final serverId = entity.id?.toString();
    if (serverId == null) {
      return;
    }
    final localId = entity.localId ?? 'remote-$_module-$serverId';
    final resolvedLocalId = await databaseService.saveLocalRecord(
      module: _module,
      payload: <String, dynamic>{
        ...entity.toPayload(),
        'id': entity.id,
        'status': isActive ? 'active' : 'inactive',
      },
      syncAction: 'update',
      localId: localId,
      serverId: serverId,
    );
    await databaseService.queueMutation(
      module: _module,
      endpoint: '${ApiEndpoints.taxRates}/$serverId/status',
      method: 'PATCH',
      payload: const <String, dynamic>{},
      recordLocalId: resolvedLocalId,
    );
    if (await networkMonitorService.hasInternetNow()) {
      unawaited(syncService.syncPendingMutations(showSuccessMessage: false));
    }
  }

  Future<List<LookupOption>> getDropdownOptions() async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.taxRatesDropdown,
    );
    final records = _extractList(response.data?['data']);
    return records
        .map(
          (record) => LookupOption(
            id: int.tryParse(record['id'].toString()) ?? 0,
            label: (record['tax_name'] ?? record['name'] ?? '').toString(),
            code: (record['tax_code'] ?? record['code'] ?? '').toString(),
          ),
        )
        .toList();
  }

  List<Map<String, dynamic>> _extractList(dynamic data) {
    if (data is List) {
      return data
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }
    return <Map<String, dynamic>>[];
  }

  List<TaxRateEntity> _applyFilters(
    List<TaxRateEntity> items,
    Map<String, dynamic>? queryParameters,
  ) {
    final query = (queryParameters?['search'] ?? '').toString().trim().toLowerCase();
    final category = (queryParameters?['tax_category'] ?? '').toString().trim().toLowerCase();
    final type = (queryParameters?['tax_type'] ?? '').toString().trim().toLowerCase();
    final status = (queryParameters?['status'] ?? '').toString().trim().toLowerCase();

    return items.where((item) {
      final matchesQuery = query.isEmpty ||
          item.taxName.toLowerCase().contains(query) ||
          item.taxCode.toLowerCase().contains(query) ||
          item.taxCategory.toLowerCase().contains(query);
      final matchesCategory = category.isEmpty || item.taxCategory.toLowerCase() == category;
      final matchesType = type.isEmpty || item.taxType.toLowerCase() == type;
      final matchesStatus = status.isEmpty || item.status.toLowerCase() == status;
      return matchesQuery && matchesCategory && matchesType && matchesStatus;
    }).toList();
  }

  PaginatedResult<TaxRateEntity> _slicePage(
    List<TaxRateEntity> items, {
    required int page,
    required int perPage,
  }) {
    if (items.isEmpty) {
      return PaginatedResult<TaxRateEntity>(
        items: const <TaxRateEntity>[],
        currentPage: 1,
        lastPage: 1,
        perPage: perPage,
        total: 0,
      );
    }
    final safePage = page < 1 ? 1 : page;
    final start = (safePage - 1) * perPage;
    if (start >= items.length) {
      final lastPage = ((items.length + perPage - 1) ~/ perPage).clamp(1, 999999);
      return PaginatedResult<TaxRateEntity>(
        items: const <TaxRateEntity>[],
        currentPage: safePage,
        lastPage: lastPage,
        perPage: perPage,
        total: items.length,
      );
    }
    final end = (start + perPage) > items.length ? items.length : start + perPage;
    final lastPage = ((items.length + perPage - 1) ~/ perPage).clamp(1, 999999);
    return PaginatedResult<TaxRateEntity>(
      items: items.sublist(start, end),
      currentPage: safePage,
      lastPage: lastPage,
      perPage: perPage,
      total: items.length,
    );
  }

  int _sortTaxRates(TaxRateEntity a, TaxRateEntity b) {
    final codeCompare = a.taxCode.toLowerCase().compareTo(b.taxCode.toLowerCase());
    if (codeCompare != 0) {
      return codeCompare;
    }
    return a.taxName.toLowerCase().compareTo(b.taxName.toLowerCase());
  }
}
