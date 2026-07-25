import 'dart:async';

import '../../../core/database/app_database_service.dart';
import '../../../core/network/api_client.dart';
import '../../../core/services/network_monitor_service.dart';
import '../../../core/services/sync_service.dart';

abstract class OfflineFirstRepository {
  OfflineFirstRepository(
    this.apiClient,
    this.databaseService,
    this.networkMonitorService,
    this.syncService,
  );

  final ApiClient apiClient;
  final AppDatabaseService databaseService;
  final NetworkMonitorService networkMonitorService;
  final SyncService syncService;

  String buildCacheKey(
    String endpoint, [
    Map<String, dynamic>? queryParameters,
  ]) {
    final buffer = StringBuffer(endpoint);
    if (queryParameters != null && queryParameters.isNotEmpty) {
      final sortedKeys = queryParameters.keys.toList()..sort();
      for (final key in sortedKeys) {
        buffer
          ..write('|')
          ..write(key)
          ..write('=')
          ..write(queryParameters[key]);
      }
    }
    return buffer.toString();
  }

  Future<Map<String, dynamic>?> getCachedObject(String cacheKey) {
    return databaseService.getCachedResponse(cacheKey);
  }

  Future<void> saveCachedObject({
    required String cacheKey,
    required String module,
    required String endpoint,
    required Map<String, dynamic> response,
    Map<String, dynamic>? queryParameters,
  }) {
    return databaseService.saveCachedResponse(
      cacheKey: cacheKey,
      module: module,
      endpoint: endpoint,
      response: response,
      queryParameters: queryParameters,
    );
  }

  Future<List<Map<String, dynamic>>> getLocalModuleRecords(String module) {
    return databaseService.getModuleRecords(module);
  }

  Future<void> invalidateRelatedCaches({required String module}) async {
    const masterModules = <String>{
      'accounts',
      'parties',
      'items',
      'item_categories',
      'tax_rates',
      'financial_years',
      'payment_vouchers',
      'receipt_vouchers',
      'adjustment_vouchers',
      'sales_invoices',
      'purchase_invoices',
      'vouchers',
    };

    if (!masterModules.contains(module)) {
      return;
    }

    await databaseService.clearCachedResponses(module: 'reports');
    await databaseService.clearCachedResponses(module: 'dashboard');
  }

  Future<void> mergeRemoteRecords({
    required String module,
    required List<Map<String, dynamic>> records,
    String serverIdKey = 'id',
  }) {
    return databaseService.mergeRemoteRecords(
      module: module,
      records: records,
      serverIdKey: serverIdKey,
    );
  }

  Future<String> queueCreate({
    required String module,
    required String endpoint,
    required Map<String, dynamic> payload,
  }) async {
    final localId = await databaseService.saveLocalRecord(
      module: module,
      payload: payload,
      syncAction: 'create',
    );

    await databaseService.queueMutation(
      module: module,
      endpoint: endpoint,
      method: 'POST',
      payload: payload,
      recordLocalId: localId,
    );
    await invalidateRelatedCaches(module: module);

    if (await networkMonitorService.hasInternetNow()) {
      unawaited(syncService.syncPendingMutations(showSuccessMessage: false));
    }

    return localId;
  }

  Future<String> queueUpdate({
    required String module,
    required String endpoint,
    required Map<String, dynamic> payload,
    required String localId,
    String? serverId,
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
      method: 'PUT',
      payload: payload,
      recordLocalId: localId,
    );
    await invalidateRelatedCaches(module: module);

    if (await networkMonitorService.hasInternetNow()) {
      unawaited(syncService.syncPendingMutations(showSuccessMessage: false));
    }

    return localId;
  }

  Future<void> queueDelete({
    required String module,
    required String endpoint,
    required Map<String, dynamic> payload,
    required String localId,
    String? serverId,
  }) async {
    await databaseService.saveLocalRecord(
      module: module,
      payload: payload,
      syncAction: 'delete',
      localId: localId,
      serverId: serverId,
      markDeleted: true,
    );

    await databaseService.queueMutation(
      module: module,
      endpoint: endpoint,
      method: 'DELETE',
      payload: payload,
      recordLocalId: localId,
    );
    await invalidateRelatedCaches(module: module);

    if (await networkMonitorService.hasInternetNow()) {
      unawaited(syncService.syncPendingMutations(showSuccessMessage: false));
    }
  }
}
