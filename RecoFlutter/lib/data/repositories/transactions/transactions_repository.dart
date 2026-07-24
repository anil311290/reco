import 'dart:async';

import '../base/offline_first_repository.dart';

class TransactionsRepository extends OfflineFirstRepository {
  TransactionsRepository(
    super.apiClient,
    super.databaseService,
    super.networkMonitorService,
    super.syncService,
  );

  Future<List<Map<String, dynamic>>> getCollection({
    required String module,
    required String endpoint,
    Map<String, dynamic>? queryParameters,
  }) async {
    final localRecords = await getLocalModuleRecords(module);
    final localPayloads = localRecords
        .map((record) => Map<String, dynamic>.from(record))
        .toList();

    if (localPayloads.isNotEmpty) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(
          refreshCollection(
            module: module,
            endpoint: endpoint,
            queryParameters: queryParameters,
          ),
        );
      }
      return localPayloads;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshCollection(
        module: module,
        endpoint: endpoint,
        queryParameters: queryParameters,
      );
    }

    return localPayloads;
  }

  Future<String> createRecord({
    required String module,
    required String endpoint,
    required Map<String, dynamic> payload,
  }) {
    return queueCreate(
      module: module,
      endpoint: endpoint,
      payload: payload,
    );
  }

  Future<List<Map<String, dynamic>>> refreshCollection({
    required String module,
    required String endpoint,
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      endpoint,
      queryParameters: queryParameters,
    );
    final records = _extractList(response.data?['data']);
    await mergeRemoteRecords(module: module, records: records);
    return await getLocalModuleRecords(module);
  }

  Future<void> patchRecord({
    required String module,
    required String endpoint,
    required String localId,
    String? serverId,
    required Map<String, dynamic> payload,
  }) async {
    await databaseService.saveLocalRecord(
      module: module,
      payload: payload,
      syncAction: 'update',
      localId: localId,
      serverId: serverId,
    );
    await databaseService.queueMutation(
      module: module,
      endpoint: endpoint,
      method: 'PATCH',
      payload: payload,
      recordLocalId: localId,
    );
    if (await networkMonitorService.hasInternetNow()) {
      unawaited(syncService.syncPendingMutations(showSuccessMessage: false));
    }
  }

  Future<void> deleteRecord({
    required String module,
    required String endpoint,
    required String localId,
    String? serverId,
    required Map<String, dynamic> payload,
  }) {
    return queueDelete(
      module: module,
      endpoint: endpoint,
      payload: payload,
      localId: localId,
      serverId: serverId,
    );
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
}
