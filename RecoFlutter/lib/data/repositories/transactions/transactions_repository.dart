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
        .toList()
      ..sort(_sortTransactions);

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
  }) async {
    final localId = await queueCreate(
      module: module,
      endpoint: endpoint,
      payload: payload,
    );
    await invalidateRelatedCaches(module: module);
    return localId;
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
    final records = _extractList(response.data?['data'])..sort(_sortTransactions);
    await mergeRemoteRecords(module: module, records: records);
    final merged = await getLocalModuleRecords(module);
    merged.sort(_sortTransactions);
    return merged;
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
    await invalidateRelatedCaches(module: module);
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
  }) async {
    await queueDelete(
      module: module,
      endpoint: endpoint,
      payload: payload,
      localId: localId,
      serverId: serverId,
    );
    await invalidateRelatedCaches(module: module);
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

  int _sortTransactions(Map<String, dynamic> a, Map<String, dynamic> b) {
    final payloadA = _payloadOf(a);
    final payloadB = _payloadOf(b);

    final dateA = _dateOf(payloadA);
    final dateB = _dateOf(payloadB);
    final dateCompare = dateB.compareTo(dateA);
    if (dateCompare != 0) {
      return dateCompare;
    }

    final numberA = _numberOf(payloadA).toLowerCase();
    final numberB = _numberOf(payloadB).toLowerCase();
    return numberB.compareTo(numberA);
  }

  Map<String, dynamic> _payloadOf(Map<String, dynamic> record) {
    final payload = record['payload'];
    if (payload is Map<String, dynamic>) {
      return payload;
    }
    return record;
  }

  String _dateOf(Map<String, dynamic> payload) {
    return (payload['voucher_date'] ??
            payload['invoice_date'] ??
            payload['date'] ??
            '')
        .toString();
  }

  String _numberOf(Map<String, dynamic> payload) {
    return (payload['voucher_number'] ??
            payload['invoice_number'] ??
            payload['number'] ??
            '')
        .toString();
  }
}
