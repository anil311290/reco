import 'dart:async';

import '../../models/common/paginated_result.dart';
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

  Future<PaginatedResult<Map<String, dynamic>>> getPaginatedCollection({
    required String module,
    required String endpoint,
    Map<String, dynamic>? queryParameters,
    int page = 1,
    int perPage = 20,
  }) async {
    final localRecords = await getLocalModuleRecords(module);
    final localPayloads = localRecords
        .map((record) => Map<String, dynamic>.from(record))
        .toList()
      ..sort(_sortTransactions);
    final filtered = _applyTransactionFilters(localPayloads, queryParameters);
    return _slicePage(filtered, page: page, perPage: perPage);
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
    try {
      await mergeRemoteRecords(module: module, records: records);
      final merged = await getLocalModuleRecords(module);
      merged.sort(_sortTransactions);
      return merged;
    } catch (_) {
      return records;
    }
  }

  Future<PaginatedResult<Map<String, dynamic>>> refreshPaginatedCollection({
    required String module,
    required String endpoint,
    Map<String, dynamic>? queryParameters,
    int page = 1,
    int perPage = 20,
  }) async {
    final params = <String, dynamic>{
      ...?queryParameters,
      'page': page,
      'per_page': perPage,
    };
    final response = await apiClient.get<Map<String, dynamic>>(
      endpoint,
      queryParameters: params,
    );
    final data = response.data?['data'];
    final records = _extractList(data)..sort(_sortTransactions);
    try {
      await mergeRemoteRecords(module: module, records: records);
    } catch (_) {
      // Keep the network payload renderable even if local merge/storage fails.
    }
    return _extractPaginatedResult(
      data,
      fallbackItems: records,
      page: page,
      perPage: perPage,
    );
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

  Future<void> updateRecord({
    required String module,
    required String endpoint,
    required String localId,
    String? serverId,
    required Map<String, dynamic> payload,
  }) async {
    await queueUpdate(
      module: module,
      endpoint: endpoint,
      payload: payload,
      localId: localId,
      serverId: serverId,
    );
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

  Future<Map<String, dynamic>> fetchRecordDetail({
    required String endpoint,
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      endpoint,
      queryParameters: queryParameters,
    );
    return response.data ?? <String, dynamic>{};
  }

  Future<Map<String, dynamic>> exportFile({
    required String endpoint,
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      endpoint,
      queryParameters: queryParameters,
    );
    return response.data ?? <String, dynamic>{};
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

  PaginatedResult<Map<String, dynamic>> _extractPaginatedResult(
    dynamic data, {
    required List<Map<String, dynamic>> fallbackItems,
    required int page,
    required int perPage,
  }) {
    if (data is Map<String, dynamic> && data['data'] is List) {
      final currentPage =
          int.tryParse(data['current_page']?.toString() ?? '$page') ?? page;
      final lastPage =
          int.tryParse(data['last_page']?.toString() ?? '$currentPage') ??
              currentPage;
      final resolvedPerPage =
          int.tryParse(data['per_page']?.toString() ?? '$perPage') ?? perPage;
      final total =
          int.tryParse(data['total']?.toString() ?? '${fallbackItems.length}') ??
              fallbackItems.length;
      return PaginatedResult<Map<String, dynamic>>(
        items: fallbackItems,
        currentPage: currentPage,
        lastPage: lastPage,
        perPage: resolvedPerPage,
        total: total,
      );
    }
    return PaginatedResult<Map<String, dynamic>>.singlePage(
      fallbackItems,
      currentPage: page,
      perPage: perPage,
      total: fallbackItems.length,
    );
  }

  PaginatedResult<Map<String, dynamic>> _slicePage(
    List<Map<String, dynamic>> items, {
    required int page,
    required int perPage,
  }) {
    if (items.isEmpty) {
      return PaginatedResult<Map<String, dynamic>>(
        items: const <Map<String, dynamic>>[],
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
      return PaginatedResult<Map<String, dynamic>>(
        items: const <Map<String, dynamic>>[],
        currentPage: safePage,
        lastPage: lastPage,
        perPage: perPage,
        total: items.length,
      );
    }
    final end = (start + perPage) > items.length ? items.length : start + perPage;
    final lastPage = ((items.length + perPage - 1) ~/ perPage).clamp(1, 999999);
    return PaginatedResult<Map<String, dynamic>>(
      items: items.sublist(start, end),
      currentPage: safePage,
      lastPage: lastPage,
      perPage: perPage,
      total: items.length,
    );
  }

  List<Map<String, dynamic>> _applyTransactionFilters(
    List<Map<String, dynamic>> items,
    Map<String, dynamic>? queryParameters,
  ) {
    final query = (queryParameters?['search'] ?? '').toString().trim().toLowerCase();
    final status = (queryParameters?['status'] ?? '').toString().trim().toLowerCase();
    final partyId = int.tryParse((queryParameters?['party_id'] ?? '0').toString()) ?? 0;
    final fromDate = (queryParameters?['date_from'] ?? '').toString().trim();
    final toDate = (queryParameters?['date_to'] ?? '').toString().trim();
    final type = (queryParameters?['type'] ?? '').toString().trim().toLowerCase();

    return items.where((record) {
      final payload = _payloadOf(record);
      final number = _numberOf(payload).toLowerCase();
      final partyName = (payload['party_name'] ?? payload['customer_name'] ?? payload['supplier_name'] ?? '')
          .toString()
          .toLowerCase();
      final nestedPartyName = ((payload['party'] is Map
                  ? (payload['party'] as Map)['name']
                  : null) ??
              '')
          .toString()
          .toLowerCase();
      final narration = (payload['narration'] ?? '').toString().toLowerCase();
      final supplierReference =
          (payload['supplier_invoice_number'] ?? payload['supplier_reference'] ?? '')
              .toString()
              .toLowerCase();
      final recordStatus = (payload['status'] ?? '').toString().toLowerCase();
      final recordPartyId =
          int.tryParse((payload['party_id'] ?? payload['customer_id'] ?? payload['supplier_id'] ?? '0').toString()) ??
              0;
      final recordType = (payload['voucher_type'] ?? payload['type'] ?? '')
          .toString()
          .toLowerCase();
      final recordDate = _dateOf(payload);

      final matchesQuery = query.isEmpty ||
          number.contains(query) ||
          partyName.contains(query) ||
          nestedPartyName.contains(query) ||
          narration.contains(query) ||
          supplierReference.contains(query);
      final matchesStatus = status.isEmpty || recordStatus == status;
      final matchesParty = partyId == 0 || recordPartyId == partyId;
      final matchesFrom = fromDate.isEmpty || recordDate.compareTo(fromDate) >= 0;
      final matchesTo = toDate.isEmpty || recordDate.compareTo(toDate) <= 0;
      final matchesType = type.isEmpty || recordType == type;

      return matchesQuery &&
          matchesStatus &&
          matchesParty &&
          matchesFrom &&
          matchesTo &&
          matchesType;
    }).toList();
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
