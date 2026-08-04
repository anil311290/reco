import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/services/network_monitor_service.dart';
import '../../../core/services/sync_service.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/models/masters/master_entities.dart';
import '../../../data/repositories/masters/masters_export_repository.dart';
import '../../../data/repositories/masters/parties_repository.dart';
import 'master_export_mixin.dart';

class PartiesController extends GetxController with MasterExportMixin {
  PartiesController(
    this._repository,
    this._syncService,
    this._networkMonitorService,
    this._exportRepository,
  );

  final PartiesRepository _repository;
  final SyncService _syncService;
  final NetworkMonitorService _networkMonitorService;
  final MastersExportRepository _exportRepository;

  final searchController = TextEditingController();
  final searchQuery = ''.obs;
  final isLoading = false.obs;
  final isLoadingMore = false.obs;
  final parties = <PartyEntity>[].obs;
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

  List<PartyEntity> get filteredItems => parties;

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

  Future<void> applyFilters({
    required String type,
    required String status,
  }) async {
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
          : await _repository.getPaginatedParties(
              queryParameters: _queryParameters,
              page: 1,
              perPage: pageSize,
            );
      if (localResult != null) {
        _applyPage(localResult, reset: true);
      }

      if (forceRemote || await _networkMonitorService.hasInternetNow()) {
        final remoteResult = await _repository.refreshPaginatedParties(
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
          ? _repository.refreshPaginatedParties(
              queryParameters: _queryParameters,
              page: nextPage,
              perPage: pageSize,
            )
          : _repository.getPaginatedParties(
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
      parties.assignAll(
        _mergeParties(
          List<PartyEntity>.from(result.items),
          base: const <PartyEntity>[],
        ),
      );
    } else {
      parties.assignAll(
        _mergeParties(
          List<PartyEntity>.from(result.items),
          base: parties,
        ),
      );
    }
  }

  List<PartyEntity> _mergeParties(
    List<PartyEntity> incoming, {
    required List<PartyEntity> base,
  }) {
    final merged = <String, PartyEntity>{};
    for (final item in base) {
      merged[_partyKey(item)] = item;
    }
    for (final item in incoming) {
      merged[_partyKey(item)] = item;
    }
    return merged.values.toList();
  }

  String _partyKey(PartyEntity item) {
    final id = item.id?.toString();
    if (id != null && id.isNotEmpty) {
      return 'id:$id';
    }
    final localId = item.localId?.trim();
    if (localId != null && localId.isNotEmpty) {
      return 'local:$localId';
    }
    return 'code:${item.partyCode}:${item.name}';
  }

  Future<void> save(PartyEntity entity) async {
    if (entity.id == null) {
      await _repository.create(entity);
      AppSnackbar.success('Party saved. Syncing to server...');
    } else {
      await _repository.update(entity);
      AppSnackbar.success('Party update queued. Syncing to server...');
    }
    // Force sync now so user sees result immediately
    if (_networkMonitorService.isOnline.value) {
      await _syncService.syncPendingMutations(showSuccessMessage: true);
    }
    await refreshData();
  }

  Future<void> deleteItem(PartyEntity entity) async {
    await _repository.delete(entity);
    await refreshData();
    AppSnackbar.success('Party delete queued.');
  }

  Future<void> toggleStatus(PartyEntity entity, bool value) async {
    await _repository.setStatus(entity, value);
    await refreshData();
  }

  Future<void> exportExcel() {
    return exportMasterExcel(
      repository: _exportRepository,
      type: 'parties',
      reportName: 'party_master',
      queryParameters: _exportQueryParameters,
      fallbackRows: filteredItems
          .map(
            (item) => <String, dynamic>{
              'Code': item.partyCode,
              'Name': item.name,
              'Type': item.type,
              'Mobile': item.mobile,
              'Email': item.email,
              'GSTIN': item.gstin,
              'Opening Balance': item.openingBalance.toStringAsFixed(2),
              'Balance Type': item.openingBalanceType,
              'Status': item.isActive ? 'Active' : 'Inactive',
            },
          )
          .toList(),
    );
  }

  Future<void> exportPdf() {
    return exportMasterPdf(
      repository: _exportRepository,
      type: 'parties',
      queryParameters: _exportQueryParameters,
      reportName: 'party_master',
      fallbackRows: filteredItems
          .map(
            (item) => <String, dynamic>{
              'Code': item.partyCode,
              'Name': item.name,
              'Type': item.type,
              'Mobile': item.mobile,
              'Email': item.email,
              'GSTIN': item.gstin,
              'Opening Balance': item.openingBalance.toStringAsFixed(2),
              'Balance Type': item.openingBalanceType,
              'Status': item.isActive ? 'Active' : 'Inactive',
            },
          )
          .toList(),
    );
  }

  Map<String, dynamic> get _exportQueryParameters => <String, dynamic>{
        if (searchController.text.trim().isNotEmpty)
          'search': searchController.text.trim(),
        if (selectedType.value != 'All') 'type': selectedType.value,
        if (selectedStatus.value != 'All')
          'is_active': selectedStatus.value == 'Active' ? 1 : 0,
      };

  Map<String, dynamic> get _queryParameters => _exportQueryParameters;
}
