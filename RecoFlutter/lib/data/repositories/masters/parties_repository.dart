import 'dart:async';

import '../../../core/config/api_endpoints.dart';
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

  Future<List<PartyEntity>> refreshParties({
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.parties,
      queryParameters: queryParameters,
    );
    final records = _extractList(response.data?['data']);
    await mergeRemoteRecords(module: _module, records: records);
    return records.map(PartyEntity.fromRecord).toList()..sort(_sortParties);
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
    await databaseService.saveLocalRecord(
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
      recordLocalId: localId,
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

  int _sortParties(PartyEntity a, PartyEntity b) {
    final codeCompare = a.partyCode.toLowerCase().compareTo(b.partyCode.toLowerCase());
    if (codeCompare != 0) {
      return codeCompare;
    }
    return a.name.toLowerCase().compareTo(b.name.toLowerCase());
  }
}
