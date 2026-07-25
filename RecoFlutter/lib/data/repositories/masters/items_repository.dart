import 'dart:async';

import '../../../core/config/api_endpoints.dart';
import '../../models/masters/master_entities.dart';
import '../base/offline_first_repository.dart';

class ItemsRepository extends OfflineFirstRepository {
  ItemsRepository(
    super.apiClient,
    super.databaseService,
    super.networkMonitorService,
    super.syncService,
  );

  static const String _module = 'items';

  Future<List<ItemEntity>> getItems({
    Map<String, dynamic>? queryParameters,
  }) async {
    final local = await getLocalModuleRecords(_module);
    final entities = local.map(ItemEntity.fromRecord).toList()..sort(_sortItems);

    if (entities.isNotEmpty) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(refreshItems(queryParameters: queryParameters));
      }
      return entities;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshItems(queryParameters: queryParameters);
    }

    return entities;
  }

  Future<List<ItemEntity>> refreshItems({
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.items,
      queryParameters: queryParameters,
    );
    final records = _extractList(response.data?['data']);
    await mergeRemoteRecords(module: _module, records: records);
    return records.map(ItemEntity.fromRecord).toList()..sort(_sortItems);
  }

  Future<Map<String, dynamic>> getItemHistory(
    int itemId, {
    Map<String, dynamic>? queryParameters,
  }) async {
    final endpoint = ApiEndpoints.itemHistory(itemId);
    final cacheKey = buildCacheKey(endpoint, queryParameters);
    final cached = await getCachedObject(cacheKey) ?? <String, dynamic>{};

    if (cached.isNotEmpty) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(
          refreshItemHistory(
            itemId,
            queryParameters: queryParameters,
          ),
        );
      }
      return cached;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshItemHistory(
        itemId,
        queryParameters: queryParameters,
      );
    }

    return cached;
  }

  Future<Map<String, dynamic>> refreshItemHistory(
    int itemId, {
    Map<String, dynamic>? queryParameters,
  }) async {
    final endpoint = ApiEndpoints.itemHistory(itemId);
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

  Future<List<ItemEntity>> getDropdownItems() {
    return getItems();
  }

  Future<String> create(ItemEntity entity) {
    return queueCreate(
      module: _module,
      endpoint: ApiEndpoints.items,
      payload: entity.toPayload(),
    );
  }

  Future<String> update(ItemEntity entity) {
    final serverId = entity.id?.toString();
    return queueUpdate(
      module: _module,
      endpoint: '${ApiEndpoints.items}/$serverId',
      payload: entity.toPayload(),
      localId: entity.localId ?? 'remote-$_module-$serverId',
      serverId: serverId,
    );
  }

  Future<void> delete(ItemEntity entity) {
    final serverId = entity.id?.toString();
    return queueDelete(
      module: _module,
      endpoint: '${ApiEndpoints.items}/$serverId',
      payload: entity.toPayload(),
      localId: entity.localId ?? 'remote-$_module-$serverId',
      serverId: serverId,
    );
  }

  Future<void> toggleStatus(ItemEntity entity, bool isActive) async {
    final serverId = entity.id?.toString();
    if (serverId == null) {
      return;
    }
    final localId = entity.localId ?? 'remote-$_module-$serverId';
    await databaseService.saveLocalRecord(
      module: _module,
      payload: <String, dynamic>{
        ...entity.toPayload(),
        'id': entity.id,
        'is_active': isActive,
      },
      syncAction: 'update',
      localId: localId,
      serverId: serverId,
    );
    await databaseService.queueMutation(
      module: _module,
      endpoint: '${ApiEndpoints.items}/$serverId/status',
      method: 'PATCH',
      payload: const <String, dynamic>{},
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

  int _sortItems(ItemEntity a, ItemEntity b) {
    final codeCompare = a.itemCode.toLowerCase().compareTo(b.itemCode.toLowerCase());
    if (codeCompare != 0) {
      return codeCompare;
    }
    return a.name.toLowerCase().compareTo(b.name.toLowerCase());
  }
}
