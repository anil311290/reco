import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/services/network_monitor_service.dart';
import '../../../core/services/sync_service.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/models/masters/master_entities.dart';
import '../../../data/repositories/accounts/accounts_repository.dart';
import '../../../data/repositories/masters/masters_export_repository.dart';
import 'master_export_mixin.dart';

class AccountsController extends GetxController with MasterExportMixin {
  AccountsController(
    this._repository,
    this._syncService,
    this._networkMonitorService,
    this._exportRepository,
  );

  final AccountsRepository _repository;
  final SyncService _syncService;
  final NetworkMonitorService _networkMonitorService;
  final MastersExportRepository _exportRepository;

  final searchController = TextEditingController();
  final searchQuery = ''.obs;
  final isLoading = false.obs;
  final accounts = <AccountEntity>[].obs;
  final selectedType = 'All'.obs;
  final selectedStatus = 'All'.obs;

  bool get isOnline => _networkMonitorService.isOnline.value;
  bool get isSyncing => _syncService.isSyncing.value;

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

  List<AccountEntity> get filteredItems {
    final query = searchQuery.value;
    return accounts.where((item) {
      final matchesQuery =
          query.isEmpty ||
          item.accountName.toLowerCase().contains(query) ||
          item.accountCode.toLowerCase().contains(query) ||
          item.accountType.toLowerCase().contains(query);
      final matchesType =
          selectedType.value == 'All' || item.accountType == selectedType.value;
      final matchesStatus =
          selectedStatus.value == 'All' ||
          (selectedStatus.value == 'Active' && item.isActive) ||
          (selectedStatus.value == 'Inactive' && !item.isActive);
      return matchesQuery && matchesType && matchesStatus;
    }).toList();
  }

  void applyFilters({required String type, required String status}) {
    selectedType.value = type;
    selectedStatus.value = status;
  }

  void clearFilters() {
    selectedType.value = 'All';
    selectedStatus.value = 'All';
  }

  Future<void> refreshData({bool forceRemote = false}) async {
    isLoading.value = true;
    try {
      accounts.assignAll(
        (forceRemote
                ? await _repository.refreshAccounts()
                : await _repository.getAccounts())
            .map(AccountEntity.fromRecord)
            .toList(),
      );
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> save(AccountEntity entity) async {
    if (entity.id == null) {
      await _repository.createAccountOffline(entity.toPayload());
      AppSnackbar.success('Account saved. Syncing to server...');
    } else {
      await _repository.updateAccountOffline(
        localId: entity.localId ?? 'remote-accounts-${entity.id}',
        accountId: entity.id.toString(),
        payload: entity.toPayload(),
      );
      AppSnackbar.success('Account update queued. Syncing to server...');
    }
    if (_networkMonitorService.isOnline.value) {
      await _syncService.syncPendingMutations(showSuccessMessage: true);
    }
    await refreshData();
  }

  Future<void> deleteItem(AccountEntity entity) async {
    if (entity.id == null) return;
    await _repository.deleteAccountOffline(
      localId: entity.localId ?? 'remote-accounts-${entity.id}',
      accountId: entity.id.toString(),
      payload: entity.toPayload(),
    );
    await refreshData();
    AppSnackbar.success('Account delete queued.');
  }

  Future<void> toggleStatus(AccountEntity entity, bool value) async {
    if (entity.id == null) return;
    await _repository.toggleAccountStatus(entity: entity, isActive: value);
    await refreshData();
  }

  Future<void> exportExcel() {
    return exportMasterExcel(
      repository: _exportRepository,
      type: 'accounts',
      reportName: 'accounts_master',
      queryParameters: _exportQueryParameters,
      fallbackRows: _exportRows,
    );
  }

  Future<void> exportPdf() {
    return exportMasterPdf(
      repository: _exportRepository,
      type: 'accounts',
      queryParameters: _exportQueryParameters,
      reportName: 'accounts_master',
      fallbackRows: _exportRows,
    );
  }

  List<Map<String, dynamic>> get _exportRows => filteredItems
      .map(
        (item) => <String, dynamic>{
          'Code': item.accountCode,
          'Name': item.accountName,
          'Type': item.accountType,
          'Mode': item.transactionMode.isEmpty ? '-' : item.transactionMode,
          'Opening Balance': item.openingBalance.toStringAsFixed(2),
          'Balance Type': item.balanceType,
          'Status': item.isActive ? 'Active' : 'Inactive',
          'Remarks': item.remarks,
        },
      )
      .toList();

  Map<String, dynamic> get _exportQueryParameters => <String, dynamic>{
        if (searchController.text.trim().isNotEmpty)
          'search': searchController.text.trim(),
        if (selectedType.value != 'All') 'account_type': selectedType.value,
        if (selectedStatus.value != 'All')
          'is_active': selectedStatus.value == 'Active' ? 1 : 0,
      };
}
