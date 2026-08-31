import 'dart:async';

import '../../../core/config/api_endpoints.dart';
import '../../models/common/paginated_result.dart';
import '../../models/masters/master_entities.dart';
import '../base/offline_first_repository.dart';

class PartiesRepository extends OfflineFirstRepository {
  PartiesRepository(
    super.apiClient,
    super.databaseService,
    super.networkMonitorService,
    super.syncService,
  );

  static const String _module = 'parties';

  Future<List<PartyEntity>> getParties({
    Map<String, dynamic>? queryParameters,
  }) async {
    final local = await getLocalModuleRecords(_module);
    final entities = local.map(PartyEntity.fromRecord).toList()..sort(_sortParties);

    if (entities.isNotEmpty) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(refreshParties(queryParameters: queryParameters));
      }
      return entities;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshParties(queryParameters: queryParameters);
    }

    return entities;
  }

  Future<PaginatedResult<PartyEntity>> getPaginatedParties({
    Map<String, dynamic>? queryParameters,
    int page = 1,
    int perPage = 20,
  }) async {
    final local = await getLocalModuleRecords(_module);
    final entities = local.map(PartyEntity.fromRecord).toList()..sort(_sortParties);
    final filtered = _applyFilters(entities, queryParameters);
    return _slicePage(filtered, page: page, perPage: perPage);
  }

  Future<List<PartyEntity>> refreshParties({
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.parties,
      queryParameters: queryParameters,
    );
    final records = _extractList(response.data?['data']);
    await mergeRemoteRecords(module: _module, records: records);
    final local = await getLocalModuleRecords(_module);
    return local.map(PartyEntity.fromRecord).toList()..sort(_sortParties);
  }

  Future<PaginatedResult<PartyEntity>> refreshPaginatedParties({
    Map<String, dynamic>? queryParameters,
    int page = 1,
    int perPage = 20,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.parties,
      queryParameters: <String, dynamic>{
        ...?queryParameters,
        'page': page,
        'per_page': perPage,
      },
    );
    final data = response.data?['data'];
    final records = _extractList(data);
    await mergeRemoteRecords(module: _module, records: records);

    return getPaginatedParties(
      queryParameters: queryParameters,
      page: page,
      perPage: perPage,
    );
  }

  Future<Map<String, dynamic>> getPartyHistory(
    int partyId, {
    Map<String, dynamic>? queryParameters,
  }) async {
    final endpoint = ApiEndpoints.partyHistory(partyId);
    final cacheKey = buildCacheKey(endpoint, queryParameters);
    final cached = await getCachedObject(cacheKey) ?? <String, dynamic>{};

    if (cached.isNotEmpty) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(
          refreshPartyHistory(
            partyId,
            queryParameters: queryParameters,
          ),
        );
      }
      return cached;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshPartyHistory(
        partyId,
        queryParameters: queryParameters,
      );
    }

    return cached;
  }

  Future<Map<String, dynamic>> refreshPartyHistory(
    int partyId, {
    Map<String, dynamic>? queryParameters,
  }) async {
    final endpoint = ApiEndpoints.partyHistory(partyId);
    final response = await apiClient.get<Map<String, dynamic>>(
      endpoint,
      queryParameters: queryParameters,
    );
    final body = response.data ?? <String, dynamic>{};
    await saveCachedObject(
      cacheKey: buildCacheKey(endpoint, queryParameters),
      module: 'reports',
      endpoint: endpoint,
      response: body,
      queryParameters: queryParameters,
    );
    return body;
  }

  Future<String> create(PartyEntity entity) {
    return queueCreate(
      module: _module,
      endpoint: ApiEndpoints.parties,
      payload: entity.toPayload(),
    );
  }

  Future<String> update(PartyEntity entity) {
    final serverId = entity.id?.toString();
    return queueUpdate(
      module: _module,
      endpoint: '${ApiEndpoints.parties}/$serverId',
      payload: entity.toPayload(),
      localId: entity.localId ?? 'remote-$_module-$serverId',
      serverId: serverId,
    );
  }

  Future<void> delete(PartyEntity entity) {
    final serverId = entity.id?.toString();
    return queueDelete(
      module: _module,
      endpoint: '${ApiEndpoints.parties}/$serverId',
      payload: entity.toPayload(),
      localId: entity.localId ?? 'remote-$_module-$serverId',
      serverId: serverId,
    );
  }

  Future<void> setStatus(PartyEntity entity, bool isActive) {
    final serverId = entity.id?.toString();
    final localId = entity.localId ?? 'remote-$_module-$serverId';
    return _queueStatusUpdate(
      endpoint: '${ApiEndpoints.parties}/$serverId/status',
      localId: localId,
      serverId: serverId,
      localPayload: <String, dynamic>{
        ...entity.toPayload(),
        'id': entity.id,
        'is_active': isActive,
      },
      remotePayload: <String, dynamic>{'status': isActive},
    );
  }

  Future<void> _queueStatusUpdate({
    required String endpoint,
    required String localId,
    required String? serverId,
    required Map<String, dynamic> localPayload,
    required Map<String, dynamic> remotePayload,
  }) async {
    final resolvedLocalId = await databaseService.saveLocalRecord(
      module: _module,
      payload: localPayload,
      syncAction: 'update',
      localId: localId,
      serverId: serverId,
    );
    await databaseService.queueMutation(
      module: _module,
      endpoint: endpoint,
      method: 'PATCH',
      payload: remotePayload,
      recordLocalId: resolvedLocalId,
    );
    await invalidateRelatedCaches(module: _module);
    if (await networkMonitorService.hasInternetNow()) {
      unawaited(syncService.syncPendingMutations(showSuccessMessage: false));
    }
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

  List<PartyEntity> _applyFilters(
    List<PartyEntity> items,
    Map<String, dynamic>? queryParameters,
  ) {
    final query = (queryParameters?['search'] ?? '').toString().trim().toLowerCase();
    final type = (queryParameters?['type'] ?? '').toString().trim().toLowerCase();
    final isActive = queryParameters != null && queryParameters.containsKey('is_active')
        ? (queryParameters['is_active'].toString() == '1' ||
            queryParameters['is_active'].toString().toLowerCase() == 'true')
        : null;

    return items.where((item) {
      final matchesQuery = query.isEmpty ||
          item.name.toLowerCase().contains(query) ||
          item.partyCode.toLowerCase().contains(query) ||
          item.mobile.toLowerCase().contains(query);
      final matchesType = type.isEmpty || item.type.toLowerCase() == type;
      final matchesStatus = isActive == null || item.isActive == isActive;
      return matchesQuery && matchesType && matchesStatus;
    }).toList();
  }

  PaginatedResult<PartyEntity> _slicePage(
    List<PartyEntity> items, {
    required int page,
    required int perPage,
  }) {
    if (items.isEmpty) {
      return PaginatedResult<PartyEntity>(
        items: const <PartyEntity>[],
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
      return PaginatedResult<PartyEntity>(
        items: const <PartyEntity>[],
        currentPage: safePage,
        lastPage: lastPage,
        perPage: perPage,
        total: items.length,
      );
    }
    final end = (start + perPage) > items.length ? items.length : start + perPage;
    final lastPage = ((items.length + perPage - 1) ~/ perPage).clamp(1, 999999);
    return PaginatedResult<PartyEntity>(
      items: items.sublist(start, end),
      currentPage: safePage,
      lastPage: lastPage,
      perPage: perPage,
      total: items.length,
    );
  }

  int _sortParties(PartyEntity a, PartyEntity b) {
    final codeCompare = a.partyCode.toLowerCase().compareTo(b.partyCode.toLowerCase());
    if (codeCompare != 0) {
      return codeCompare;
    }
    return a.name.toLowerCase().compareTo(b.name.toLowerCase());
  }
}
