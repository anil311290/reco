import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/services/network_monitor_service.dart';
import '../../../core/services/sync_service.dart';
import '../../../core/utils/app_snackbar.dart';
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
  final records = <TransactionRecord>[].obs;
  final selectedStatus = 'All'.obs;
  final selectedPartyId = 0.obs;
  final selectedFromDate = ''.obs;
  final selectedToDate = ''.obs;

  String get module;
  String get endpoint;
  TransactionRecordKind get kind;
  String get searchHint;
  bool get supportsPartyFilter;
  bool get supportsWorkflowActions;
  bool get supportsDeleteAction => true;
  List<String> get statusOptions;
  Map<String, dynamic> get extraQueryParameters => const <String, dynamic>{};

  bool get isOnline => networkMonitorService.isOnline.value;
  bool get isSyncing => syncService.isSyncing.value;

  @override
  void onInit() {
    super.onInit();
    searchController.addListener(() {
      searchQuery.value = searchController.text.trim().toLowerCase();
    });
    refreshData();
  }

  @override
  void onClose() {
    searchController.dispose();
    super.onClose();
  }

  List<TransactionRecord> get filteredItems {
    final query = searchQuery.value;
    return records.where((item) {
      final matchesQuery =
          query.isEmpty ||
          item.number.toLowerCase().contains(query) ||
          item.partyName.toLowerCase().contains(query) ||
          item.narration.toLowerCase().contains(query) ||
          item.supplierReference.toLowerCase().contains(query);
      final matchesStatus =
          selectedStatus.value == 'All' ||
          item.status.toLowerCase() == selectedStatus.value.toLowerCase();
      final matchesParty =
          !supportsPartyFilter ||
          selectedPartyId.value == 0 ||
          item.partyId == selectedPartyId.value;
      final matchesFrom =
          selectedFromDate.value.isEmpty ||
          item.date.compareTo(selectedFromDate.value) >= 0;
      final matchesTo =
          selectedToDate.value.isEmpty ||
          item.date.compareTo(selectedToDate.value) <= 0;
      return matchesQuery &&
          matchesStatus &&
          matchesParty &&
          matchesFrom &&
          matchesTo;
    }).toList();
  }

  Future<void> refreshData({bool forceRemote = false}) async {
    isLoading.value = true;
    try {
      if (forceRemote) {
        final remoteItems = await repository.refreshCollection(
          module: module,
          endpoint: endpoint,
          queryParameters: queryParameters,
        );
        records.assignAll(remoteItems.map(mapRecord).toList());
        return;
      }

      final localItems = await repository.getCollection(
        module: module,
        endpoint: endpoint,
        queryParameters: queryParameters,
      );
      records.assignAll(localItems.map(mapRecord).toList());

      if (await networkMonitorService.hasInternetNow()) {
        final remoteItems = await repository.refreshCollection(
          module: module,
          endpoint: endpoint,
          queryParameters: queryParameters,
        );
        records.assignAll(remoteItems.map(mapRecord).toList());
      }
    } finally {
      isLoading.value = false;
    }
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
      'per_page': 50,
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
      params['date_from'] = selectedFromDate.value;
    }
    if (selectedToDate.value.isNotEmpty) {
      params['date_to'] = selectedToDate.value;
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
