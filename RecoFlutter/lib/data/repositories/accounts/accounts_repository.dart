import 'dart:async';

import '../../../core/config/api_endpoints.dart';
import '../../models/common/paginated_result.dart';
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

  Future<PaginatedResult<AccountEntity>> getPaginatedAccounts({
    Map<String, dynamic>? queryParameters,
    int page = 1,
    int perPage = 20,
  }) async {
    final localRecords = await getLocalModuleRecords(_module);
    final localPayloads = localRecords
        .map((record) => Map<String, dynamic>.from(record['payload'] as Map))
        .toList()
      ..sort(_sortAccounts);
    final filtered = _applyFilters(localPayloads, queryParameters);
    return _slicePage(
      filtered.map(AccountEntity.fromRecord).toList(),
      page: page,
      perPage: perPage,
    );
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

  Future<PaginatedResult<AccountEntity>> refreshPaginatedAccounts({
    Map<String, dynamic>? queryParameters,
    int page = 1,
    int perPage = 20,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.accounts,
      queryParameters: <String, dynamic>{
        ...?queryParameters,
        'page': page,
        'per_page': perPage,
      },
    );

    final data = response.data?['data'];
    final records = _extractList(data)..sort(_sortAccounts);
    await mergeRemoteRecords(module: _module, records: records);

    if (data is Map<String, dynamic> && data['data'] is List) {
      return PaginatedResult<AccountEntity>(
        items: records.map(AccountEntity.fromRecord).toList(),
        currentPage: int.tryParse(data['current_page']?.toString() ?? '$page') ?? page,
        lastPage: int.tryParse(data['last_page']?.toString() ?? '$page') ?? page,
        perPage: int.tryParse(data['per_page']?.toString() ?? '$perPage') ?? perPage,
        total: int.tryParse(data['total']?.toString() ?? '${records.length}') ?? records.length,
      );
    }

    final localResult = await getPaginatedAccounts(
      queryParameters: queryParameters,
      page: page,
      perPage: perPage,
    );
    return localResult;
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
        .map((item) {
          final code = (item['account_code'] ?? item['code'] ?? '').toString();
          final name = (item['account_name'] ?? item['name'] ?? '').toString();
          final label = name.trim().isNotEmpty
              ? name.trim()
              : (code.trim().isNotEmpty ? code.trim() : 'Account');
          return LookupOption(
            id: int.tryParse(item['id'].toString()) ?? 0,
            label: label,
            code: code,
          );
        })
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

  List<Map<String, dynamic>> _applyFilters(
    List<Map<String, dynamic>> items,
    Map<String, dynamic>? queryParameters,
  ) {
    final query = (queryParameters?['search'] ?? '').toString().trim().toLowerCase();
    final type = (queryParameters?['account_type'] ?? '').toString().trim().toLowerCase();
    final isActive = queryParameters != null && queryParameters.containsKey('is_active')
        ? (queryParameters['is_active'].toString() == '1' ||
            queryParameters['is_active'].toString().toLowerCase() == 'true')
        : null;

    return items.where((item) {
      final code = (item['account_code'] ?? '').toString().toLowerCase();
      final name = (item['account_name'] ?? '').toString().toLowerCase();
      final accountType = (item['account_type'] ?? '').toString().toLowerCase();
      final active = item['is_active'] == true || item['is_active'].toString() == '1';

      final matchesQuery =
          query.isEmpty || code.contains(query) || name.contains(query) || accountType.contains(query);
      final matchesType = type.isEmpty || accountType == type;
      final matchesStatus = isActive == null || active == isActive;
      return matchesQuery && matchesType && matchesStatus;
    }).toList();
  }

  PaginatedResult<AccountEntity> _slicePage(
    List<AccountEntity> items, {
    required int page,
    required int perPage,
  }) {
    if (items.isEmpty) {
      return PaginatedResult<AccountEntity>(
        items: const <AccountEntity>[],
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
      return PaginatedResult<AccountEntity>(
        items: const <AccountEntity>[],
        currentPage: safePage,
        lastPage: lastPage,
        perPage: perPage,
        total: items.length,
      );
    }
    final end = (start + perPage) > items.length ? items.length : start + perPage;
    final lastPage = ((items.length + perPage - 1) ~/ perPage).clamp(1, 999999);
    return PaginatedResult<AccountEntity>(
      items: items.sublist(start, end),
      currentPage: safePage,
      lastPage: lastPage,
      perPage: perPage,
      total: items.length,
    );
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
