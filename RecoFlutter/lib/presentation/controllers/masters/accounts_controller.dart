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
  final isLoadingMore = false.obs;
  final accounts = <AccountEntity>[].obs;
  final selectedType = 'All'.obs;
  final selectedStatus = 'All'.obs;
  final currentPage = 1.obs;
  final lastPage = 1.obs;
  final total = 0.obs;
  final scrollController = ScrollController();

  static const int pageSize = 20;

  bool get isOnline => _networkMonitorService.isOnline.value;
  bool get isSyncing => _syncService.isSyncing.value;
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

  List<AccountEntity> get filteredItems => accounts;

  void _handleScroll() {
    if (!scrollController.hasClients || isLoadingMore.value || !hasMore) {
      return;
    }
    if (scrollController.position.pixels >=
        scrollController.position.maxScrollExtent - 160) {
      loadMore();
    }
  }

  Future<void> onSearchChanged() => refreshData();

  Future<void> applyFilters({required String type, required String status}) async {
    selectedType.value = type;
    selectedStatus.value = status;
    await refreshData();
  }

  Future<void> clearFilters() async {
    selectedType.value = 'All';
    selectedStatus.value = 'All';
    await refreshData();
  }

  Future<void> refreshData({bool forceRemote = false}) async {
    isLoading.value = true;
    currentPage.value = 1;
    try {
      final localResult = forceRemote
          ? null
          : await _repository.getPaginatedAccounts(
              queryParameters: _queryParameters,
              page: 1,
              perPage: pageSize,
            );
      if (localResult != null) {
        _applyPage(localResult, reset: true);
      }

      if (forceRemote || await _networkMonitorService.hasInternetNow()) {
        final remoteResult = await _repository.refreshPaginatedAccounts(
          queryParameters: _queryParameters,
          page: 1,
          perPage: pageSize,
        );
        _applyPage(remoteResult, reset: true);
      }
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> loadMore() async {
    if (isLoading.value || isLoadingMore.value || !hasMore) return;
    isLoadingMore.value = true;
    final nextPage = currentPage.value + 1;
    try {
      final result = await (await _networkMonitorService.hasInternetNow()
          ? _repository.refreshPaginatedAccounts(
              queryParameters: _queryParameters,
              page: nextPage,
              perPage: pageSize,
            )
          : _repository.getPaginatedAccounts(
              queryParameters: _queryParameters,
              page: nextPage,
              perPage: pageSize,
            ));
      _applyPage(result, reset: false);
    } finally {
      isLoadingMore.value = false;
    }
  }

  void _applyPage(dynamic result, {required bool reset}) {
    currentPage.value = result.currentPage;
    lastPage.value = result.lastPage;
    total.value = result.total;
    if (reset) {
      accounts.assignAll(
        _mergeAccounts(
          List<AccountEntity>.from(result.items),
          base: const <AccountEntity>[],
        ),
      );
    } else {
      accounts.assignAll(
        _mergeAccounts(
          List<AccountEntity>.from(result.items),
          base: accounts,
        ),
      );
    }
  }

  List<AccountEntity> _mergeAccounts(
    List<AccountEntity> incoming, {
    required List<AccountEntity> base,
  }) {
    final merged = <String, AccountEntity>{};
    for (final item in base) {
      merged[_accountKey(item)] = item;
    }
    for (final item in incoming) {
      merged[_accountKey(item)] = item;
    }
    return merged.values.toList();
  }

  String _accountKey(AccountEntity item) {
    final id = item.id?.toString();
    if (id != null && id.isNotEmpty) {
      return 'id:$id';
    }
    final localId = item.localId?.trim();
    if (localId != null && localId.isNotEmpty) {
      return 'local:$localId';
    }
    return 'code:${item.accountCode}:${item.accountName}';
  }

  Future<void> save(AccountEntity entity) async {
    final payload = <String, dynamic>{
      ...entity.toPayload(),
      'entry_source': entity.entrySource.isEmpty ? 'manual' : entity.entrySource,
    };
    if (entity.id == null) {
      await _repository.createAccountOffline(payload);
      AppSnackbar.success('Account saved. Syncing to server...');
    } else {
      await _repository.updateAccountOffline(
        localId: entity.localId ?? 'remote-accounts-${entity.id}',
        accountId: entity.id.toString(),
        payload: payload,
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
    try {
      await _repository.deleteAccountOffline(
        localId: entity.localId ?? 'remote-accounts-${entity.id}',
        accountId: entity.id.toString(),
        payload: entity.toPayload(),
      );
      if (_networkMonitorService.isOnline.value) {
        await _syncService.syncPendingMutations(showSuccessMessage: false);
      }
      await refreshData(forceRemote: true);
      AppSnackbar.success('Account deleted.');
    } catch (error) {
      AppSnackbar.error(error.toString());
    }
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
          'Is Cash/Bank/OD': item.accountType == 'asset'
              ? (item.isCashBankOd ? 'Yes' : 'No')
              : '-',
          'Opening Balance': item.openingBalance.toStringAsFixed(2),
          'Balance Type': item.balanceType,
          'Status': item.isActive ? 'Active' : 'Inactive',
          'Notes': item.remarks,
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

  Map<String, dynamic> get _queryParameters => _exportQueryParameters;
}
