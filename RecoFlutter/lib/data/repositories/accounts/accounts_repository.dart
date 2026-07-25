import 'dart:async';

import '../../../core/config/api_endpoints.dart';
import '../../models/masters/master_entities.dart';
import '../base/offline_first_repository.dart';

class AccountsRepository extends OfflineFirstRepository {
  AccountsRepository(
    super.apiClient,
    super.databaseService,
    super.networkMonitorService,
    super.syncService,
  );

  static const String _module = 'accounts';

  Future<List<Map<String, dynamic>>> getAccounts({
    Map<String, dynamic>? queryParameters,
  }) async {
    final localRecords = await getLocalModuleRecords(_module);
    final localPayloads = localRecords
        .map((record) => (record['payload'] as Map<String, dynamic>))
        .toList()
      ..sort(_sortAccounts);

    if (localPayloads.isNotEmpty) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(refreshAccounts(queryParameters: queryParameters));
      }
      return localPayloads;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshAccounts(queryParameters: queryParameters);
    }

    return localPayloads;
  }

  Future<List<Map<String, dynamic>>> refreshAccounts({
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.accounts,
      queryParameters: queryParameters,
    );

    final data = response.data?['data'];
    final records = _extractList(data)..sort(_sortAccounts);

    await mergeRemoteRecords(module: _module, records: records);
    return records;
  }

  Future<String> createAccountOffline(Map<String, dynamic> payload) {
    return queueCreate(
      module: _module,
      endpoint: ApiEndpoints.accounts,
      payload: payload,
    );
  }

  Future<String> updateAccountOffline({
    required String localId,
    required String accountId,
    required Map<String, dynamic> payload,
  }) {
    return queueUpdate(
      module: _module,
      endpoint: '${ApiEndpoints.accounts}/$accountId',
      payload: payload,
      localId: localId,
      serverId: accountId,
    );
  }

  Future<void> toggleAccountStatus({
    required AccountEntity entity,
    required bool isActive,
  }) async {
    final accountId = entity.id?.toString();
    if (accountId == null) {
      return;
    }
    final localId = entity.localId ?? 'remote-$_module-$accountId';
    final localPayload = <String, dynamic>{
      ...entity.toPayload(),
      'id': entity.id,
      'is_active': isActive,
    };

    await databaseService.saveLocalRecord(
      module: _module,
      payload: localPayload,
      syncAction: 'update',
      localId: localId,
      serverId: accountId,
    );

    await databaseService.queueMutation(
      module: _module,
      endpoint: '${ApiEndpoints.accounts}/$accountId/status',
      method: 'PATCH',
      payload: <String, dynamic>{'status': isActive},
      recordLocalId: localId,
    );

    if (await networkMonitorService.hasInternetNow()) {
      unawaited(syncService.syncPendingMutations(showSuccessMessage: false));
    }
  }

  Future<List<LookupOption>> getAccountsByType(String type) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.accountsByType,
      queryParameters: <String, dynamic>{'type': type},
    );

    final data = response.data?['data'];
    if (data is! Map<String, dynamic>) {
      return <LookupOption>[];
    }

    final accounts = data['accounts'];
    if (accounts is! List) {
      return <LookupOption>[];
    }

    return accounts
        .whereType<Map>()
        .map(
          (item) => LookupOption(
            id: int.tryParse(item['id'].toString()) ?? 0,
            label: (item['account_name'] ?? item['name'] ?? '').toString(),
            code: (item['account_code'] ?? item['code'] ?? '').toString(),
          ),
        )
        .toList();
  }

  Future<List<Map<String, dynamic>>> getCashBankAccounts({
    String? mode,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.accountCashBank,
      queryParameters: <String, dynamic>{
        if (mode != null && mode.isNotEmpty) 'mode': mode,
      },
    );
    return _extractList(response.data?['data']);
  }

  Future<List<Map<String, dynamic>>> getPaymentParticulars(String type) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.accountPaymentParticulars,
      queryParameters: <String, dynamic>{'type': type},
    );
    return _extractList(response.data?['data']);
  }

  Future<List<Map<String, dynamic>>> getAdjustmentParticulars() async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.accountAdjustmentParticulars,
    );
    return _extractList(response.data?['data']);
  }

  Future<void> deleteAccountOffline({
    required String localId,
    required String accountId,
    required Map<String, dynamic> payload,
  }) {
    return queueDelete(
      module: _module,
      endpoint: '${ApiEndpoints.accounts}/$accountId',
      payload: payload,
      localId: localId,
      serverId: accountId,
    );
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

  int _sortAccounts(Map<String, dynamic> a, Map<String, dynamic> b) {
    final codeA = (a['account_code'] ?? '').toString().toLowerCase();
    final codeB = (b['account_code'] ?? '').toString().toLowerCase();
    final codeCompare = codeA.compareTo(codeB);
    if (codeCompare != 0) {
      return codeCompare;
    }
    final nameA = (a['account_name'] ?? '').toString().toLowerCase();
    final nameB = (b['account_name'] ?? '').toString().toLowerCase();
    return nameA.compareTo(nameB);
  }
}
