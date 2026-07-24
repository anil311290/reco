import 'dart:async';
import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:get/get.dart' hide Response;

import '../database/app_database_service.dart';
import '../network/api_client.dart';
import '../utils/app_snackbar.dart';
import 'network_monitor_service.dart';

class SyncService extends GetxService {
  SyncService(
    this._apiClient,
    this._databaseService,
    this._networkMonitorService,
  );

  final ApiClient _apiClient;
  final AppDatabaseService _databaseService;
  final NetworkMonitorService _networkMonitorService;

  final RxBool isSyncing = false.obs;
  StreamSubscription<bool>? _networkSubscription;

  Future<SyncService> init() async {
    _networkSubscription = _networkMonitorService.statusStream.listen((online) {
      if (online) {
        unawaited(syncPendingMutations(showSuccessMessage: false));
      }
    });

    if (await _networkMonitorService.hasInternetNow()) {
      await syncPendingMutations(showSuccessMessage: false);
    }

    return this;
  }

  Future<void> syncPendingMutations({bool showSuccessMessage = true}) async {
    if (isSyncing.value) {
      return;
    }

    if (!await _networkMonitorService.hasInternetNow()) {
      return;
    }

    isSyncing.value = true;

    try {
      final items = await _databaseService.getPendingSyncQueue();
      if (items.isEmpty) {
        return;
      }

      for (final item in items) {
        await _syncQueueItem(item);
      }

      if (showSuccessMessage) {
        AppSnackbar.success('Pending local changes synced successfully.');
      }
    } finally {
      isSyncing.value = false;
    }
  }

  Future<void> _syncQueueItem(Map<String, Object?> item) async {
    final queueId = item['queue_id']?.toString();
    final endpoint = item['endpoint']?.toString();
    final method = item['method']?.toString().toUpperCase();
    final module = item['module']?.toString() ?? '';
    final localId = item['record_local_id']?.toString();

    if (queueId == null || endpoint == null || method == null) {
      return;
    }

    await _databaseService.markQueueSyncing(queueId);

    try {
      final payload =
          jsonDecode(item['payload_json'] as String) as Map<String, dynamic>;
      final queryParameters =
          jsonDecode(item['query_params_json'] as String)
              as Map<String, dynamic>;
      final options = Options(extra: <String, dynamic>{'silentError': true});

      Response<Map<String, dynamic>>? response;

      switch (method) {
        case 'POST':
          response = await _apiClient.post<Map<String, dynamic>>(
            endpoint,
            data: payload,
            queryParameters: queryParameters,
            options: options,
          );
        case 'PUT':
          response = await _apiClient.put<Map<String, dynamic>>(
            endpoint,
            data: payload,
            queryParameters: queryParameters,
            options: options,
          );
        case 'PATCH':
          response = await _apiClient.patch<Map<String, dynamic>>(
            endpoint,
            data: payload,
            queryParameters: queryParameters,
            options: options,
          );
        case 'DELETE':
          response = await _apiClient.delete<Map<String, dynamic>>(
            endpoint,
            data: payload,
            queryParameters: queryParameters,
            options: options,
          );
      }

      final body = response?.data ?? <String, dynamic>{};
      final responseData = _extractDataMap(body) ?? payload;

      if (localId != null && localId.isNotEmpty) {
        if (method == 'DELETE') {
          await _databaseService.removeRecordAfterSync(localId);
        } else {
          final serverId =
              responseData['id']?.toString() ??
              responseData['uuid']?.toString() ??
              payload['id']?.toString() ??
              payload['uuid']?.toString();

          await _databaseService.markRecordSynced(
            localId: localId,
            module: module,
            payload: responseData,
            serverId: serverId,
          );
        }
      }

      await _databaseService.markQueueSynced(queueId);
    } catch (error) {
      await _databaseService.markQueueFailed(queueId, error.toString());
    }
  }

  Map<String, dynamic>? _extractDataMap(Map<String, dynamic> body) {
    final dynamic data = body['data'];
    if (data is Map<String, dynamic>) {
      return data;
    }

    return null;
  }

  @override
  void onClose() {
    _networkSubscription?.cancel();
    super.onClose();
  }
}
