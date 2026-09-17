import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/services/network_monitor_service.dart';
import '../../../core/services/sync_service.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../core/utils/app_date_formatter.dart';
import '../../../data/models/transactions/transaction_entities.dart';
import '../../../data/repositories/transactions/transactions_repository.dart';

abstract class BaseTransactionsTabController extends GetxController {
  BaseTransactionsTabController(
    this.repository,
    this.syncService,
    this.networkMonitorService,
  );

  final TransactionsRepository repository;
  final SyncService syncService;
  final NetworkMonitorService networkMonitorService;

  final searchController = TextEditingController();
  final searchQuery = ''.obs;
  final isLoading = false.obs;
  final isLoadingMore = false.obs;
  final records = <TransactionRecord>[].obs;
  final selectedStatus = 'All'.obs;
  final selectedPartyId = 0.obs;
  final selectedFromDate = ''.obs;
  final selectedToDate = ''.obs;
  final currentPage = 1.obs;
  final lastPage = 1.obs;
  final total = 0.obs;
  final scrollController = ScrollController();

  static const int pageSize = 20;

  String get module;
  String get endpoint;
  TransactionRecordKind get kind;
  String get searchHint;
  bool get supportsPartyFilter;
  bool get supportsWorkflowActions;
  bool get supportsDeleteAction => true;
  bool get supportsDateFilter => true;
  List<String> get statusOptions;
  Map<String, dynamic> get extraQueryParameters => const <String, dynamic>{};

  bool get isOnline => networkMonitorService.isOnline.value;
  bool get isSyncing => syncService.isSyncing.value;
  bool get hasMore => currentPage.value < lastPage.value;

  @override
  void onInit() {
    super.onInit();
    searchController.addListener(() {
      searchQuery.value = searchController.text.trim().toLowerCase();
    });
    scrollController.addListener(_handleScroll);
    refreshData();
  }

  @override
  void onClose() {
    scrollController.removeListener(_handleScroll);
    scrollController.dispose();
    searchController.dispose();
    super.onClose();
  }

  List<TransactionRecord> get filteredItems => records;

  void _handleScroll() {
    if (!scrollController.hasClients || isLoadingMore.value || !hasMore) {
      return;
    }
    final position = scrollController.position;
    if (position.pixels >= position.maxScrollExtent - 160) {
      loadMore();
    }
  }

  Future<void> onSearchChanged() async {
    await refreshData();
  }

  Future<void> refreshData({bool forceRemote = false}) async {
    isLoading.value = true;
    currentPage.value = 1;
    try {
      if (forceRemote) {
        final remoteItems = await repository.refreshPaginatedCollection(
          module: module,
          endpoint: endpoint,
          queryParameters: queryParameters,
          page: 1,
          perPage: pageSize,
        );
        _applyPage(remoteItems, reset: true);
        return;
      }

      final localItems = await repository.getPaginatedCollection(
        module: module,
        endpoint: endpoint,
        queryParameters: queryParameters,
        page: 1,
        perPage: pageSize,
      );
      _applyPage(localItems, reset: true);

      if (await networkMonitorService.hasInternetNow()) {
        final remoteItems = await repository.refreshPaginatedCollection(
          module: module,
          endpoint: endpoint,
          queryParameters: queryParameters,
          page: 1,
          perPage: pageSize,
        );
        _applyPage(remoteItems, reset: true);
      }
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> loadMore() async {
    if (isLoading.value || isLoadingMore.value || !hasMore) {
      return;
    }
    isLoadingMore.value = true;
    final nextPage = currentPage.value + 1;
    try {
      final pageResult = await (await networkMonitorService.hasInternetNow()
          ? repository.refreshPaginatedCollection(
              module: module,
              endpoint: endpoint,
              queryParameters: queryParameters,
              page: nextPage,
              perPage: pageSize,
            )
          : repository.getPaginatedCollection(
              module: module,
              endpoint: endpoint,
              queryParameters: queryParameters,
              page: nextPage,
              perPage: pageSize,
            ));
      _applyPage(pageResult, reset: false);
    } finally {
      isLoadingMore.value = false;
    }
  }

  void _applyPage(
    dynamic pageResult, {
    required bool reset,
  }) {
    final mapped = <TransactionRecord>[];
    for (final item in pageResult.items) {
      try {
        mapped.add(mapRecord(Map<String, dynamic>.from(item as Map)));
      } catch (_) {
        // Skip malformed records so one bad payload does not blank the whole tab.
      }
    }
    currentPage.value = pageResult.currentPage;
    lastPage.value = pageResult.lastPage;
    total.value = pageResult.total;
    if (reset) {
      records.assignAll(_mergeRecords(mapped, base: const <TransactionRecord>[]));
    } else {
      records.assignAll(_mergeRecords(mapped, base: records));
    }
  }

  List<TransactionRecord> _mergeRecords(
    List<TransactionRecord> incoming, {
    required List<TransactionRecord> base,
  }) {
    final merged = <String, TransactionRecord>{};
    for (final item in base) {
      merged[_recordKey(item)] = item;
    }
    for (final item in incoming) {
      merged[_recordKey(item)] = item;
    }
    return merged.values.toList();
  }

  String _recordKey(TransactionRecord item) {
    final id = item.id?.toString();
    if (id != null && id.isNotEmpty) {
      return 'id:$id';
    }
    final localId = item.localId?.trim();
    if (localId != null && localId.isNotEmpty) {
      return 'local:$localId';
    }
    return '${item.kind.name}:${item.number}:${item.date}:${item.amount}:${item.status}';
  }

  TransactionRecord mapRecord(Map<String, dynamic> record) {
    switch (kind) {
      case TransactionRecordKind.voucher:
        return TransactionRecord.fromVoucher(record);
      case TransactionRecordKind.salesInvoice:
        return TransactionRecord.fromSalesInvoice(record);
      case TransactionRecordKind.purchaseInvoice:
        return TransactionRecord.fromPurchaseInvoice(record);
    }
  }

  Map<String, dynamic> get queryParameters {
    final params = <String, dynamic>{
      ...extraQueryParameters,
    };
    if (searchQuery.value.isNotEmpty) {
      params['search'] = searchQuery.value;
    }
    if (selectedStatus.value != 'All') {
      params['status'] = selectedStatus.value.toLowerCase();
    }
    if (supportsPartyFilter && selectedPartyId.value > 0) {
      params['party_id'] = selectedPartyId.value;
    }
    if (selectedFromDate.value.isNotEmpty) {
      params['date_from'] = AppDateFormatter.toApiDate(selectedFromDate.value);
    }
    if (selectedToDate.value.isNotEmpty) {
      params['date_to'] = AppDateFormatter.toApiDate(selectedToDate.value);
    }
    return params;
  }

  Future<void> applyFilters({
    required String status,
    required int partyId,
    required String fromDate,
    required String toDate,
  }) async {
    selectedStatus.value = status;
    selectedPartyId.value = partyId;
    selectedFromDate.value = fromDate;
    selectedToDate.value = toDate;
    await refreshData();
  }

  Future<void> clearFilters() async {
    selectedStatus.value = 'All';
    selectedPartyId.value = 0;
    selectedFromDate.value = '';
    selectedToDate.value = '';
    await refreshData();
  }

  Future<void> postRecord(TransactionRecord record) async {
    if (!supportsWorkflowActions || record.id == null) {
      return;
    }
    final localId = record.localId ?? 'remote-$module-${record.id}';
    await repository.patchRecord(
      module: module,
      endpoint: '/vouchers/${record.id}/post',
      localId: localId,
      serverId: record.id.toString(),
      payload: record.payloadWithStatus('posted'),
    );
    AppSnackbar.success('Post action queued successfully.');
    await refreshData();
  }

  Future<void> cancelRecord(TransactionRecord record) async {
    if (!supportsWorkflowActions || record.id == null) {
      return;
    }
    final localId = record.localId ?? 'remote-$module-${record.id}';
    await repository.patchRecord(
      module: module,
      endpoint: '/vouchers/${record.id}/cancel',
      localId: localId,
      serverId: record.id.toString(),
      payload: record.payloadWithStatus('cancelled'),
    );
    AppSnackbar.success('Cancel action queued successfully.');
    await refreshData();
  }

  Future<void> deleteRecord(TransactionRecord record) async {
    if (!supportsDeleteAction || record.id == null) {
      return;
    }
    final localId = record.localId ?? 'remote-$module-${record.id}';
    await repository.deleteRecord(
      module: module,
      endpoint: '$endpoint/${record.id}',
      localId: localId,
      serverId: record.id.toString(),
      payload: record.rawPayload,
    );
    AppSnackbar.success('Delete action queued successfully.');
    await refreshData();
  }
}
