import 'dart:async';

import '../../../core/config/api_endpoints.dart';
import '../../models/masters/master_entities.dart';
import '../base/offline_first_repository.dart';

class ItemCategoriesRepository extends OfflineFirstRepository {
  ItemCategoriesRepository(
    super.apiClient,
    super.databaseService,
    super.networkMonitorService,
    super.syncService,
  );

  static const String _module = 'item_categories';

  Future<List<ItemCategoryEntity>> getCategories({
    Map<String, dynamic>? queryParameters,
  }) async {
    final local = await getLocalModuleRecords(_module);
    final entities = local.map(ItemCategoryEntity.fromRecord).toList()
      ..sort(_sortCategories);

    if (entities.isNotEmpty) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(refreshCategories(queryParameters: queryParameters));
      }
      return entities;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshCategories(queryParameters: queryParameters);
    }

    return entities;
  }

  Future<List<ItemCategoryEntity>> refreshCategories({
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.itemCategories,
      queryParameters: queryParameters,
    );
    final records = _extractList(response.data?['data']);
    await mergeRemoteRecords(module: _module, records: records);
    return records.map(ItemCategoryEntity.fromRecord).toList()
      ..sort(_sortCategories);
  }

  Future<String> create(ItemCategoryEntity entity) {
    return queueCreate(
      module: _module,
      endpoint: ApiEndpoints.itemCategories,
      payload: entity.toPayload(),
    );
  }

  Future<String> update(ItemCategoryEntity entity) {
    final serverId = entity.id?.toString();
    return queueUpdate(
      module: _module,
      endpoint: '${ApiEndpoints.itemCategories}/$serverId',
      payload: entity.toPayload(),
      localId: entity.localId ?? 'remote-$_module-$serverId',
      serverId: serverId,
    );
  }

  Future<void> delete(ItemCategoryEntity entity) {
    final serverId = entity.id?.toString();
    return queueDelete(
      module: _module,
      endpoint: '${ApiEndpoints.itemCategories}/$serverId',
      payload: entity.toPayload(),
      localId: entity.localId ?? 'remote-$_module-$serverId',
      serverId: serverId,
    );
  }

  Future<void> toggleStatus(ItemCategoryEntity entity, bool isActive) async {
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
      endpoint: '${ApiEndpoints.itemCategories}/$serverId/status',
      method: 'PATCH',
      payload: const <String, dynamic>{},
      recordLocalId: localId,
    );
    if (await networkMonitorService.hasInternetNow()) {
      unawaited(syncService.syncPendingMutations(showSuccessMessage: false));
    }
  }

  Future<List<LookupOption>> getDropdownOptions() async {
    final local = await getLocalModuleRecords(_module);
    final localOptions = local
        .map(ItemCategoryEntity.fromRecord)
        .where((item) => item.id != null && item.isActive)
        .map(
          (item) => LookupOption(
            id: item.id!,
            label: item.name,
            rawId: item.id!.toString(),
          ),
        )
        .toList()
      ..sort((a, b) => a.label.toLowerCase().compareTo(b.label.toLowerCase()));

    final hasInternet = await networkMonitorService.hasInternetNow();

    if (hasInternet) {
      try {
        return await _refreshDropdownOptions();
      } catch (_) {
        if (localOptions.isNotEmpty) {
          return localOptions;
        }
        rethrow;
      }
    }

    if (localOptions.isNotEmpty) {
      return localOptions;
    }

    return <LookupOption>[];
  }

  Future<List<LookupOption>> _refreshDropdownOptions() async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.itemCategoriesDropdown,
    );
    final records = _extractList(response.data?['data']);
    return records
        .map(
          (record) => LookupOption.fromJson(Map<String, dynamic>.from(record)),
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
    if (data is Map<String, dynamic> && data['data'] is List) {
      return (data['data'] as List)
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }
    return <Map<String, dynamic>>[];
  }

  int _sortCategories(ItemCategoryEntity a, ItemCategoryEntity b) {
    return a.name.toLowerCase().compareTo(b.name.toLowerCase());
  }
}
