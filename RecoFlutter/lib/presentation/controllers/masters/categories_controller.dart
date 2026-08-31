import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/services/network_monitor_service.dart';
import '../../../core/services/sync_service.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/models/masters/master_entities.dart';
import '../../../data/repositories/masters/item_categories_repository.dart';
import '../../../data/repositories/masters/masters_export_repository.dart';
import 'master_export_mixin.dart';

class CategoriesController extends GetxController with MasterExportMixin {
  CategoriesController(
    this._repository,
    this._exportRepository,
    this._syncService,
    this._networkMonitorService,
  );

  final ItemCategoriesRepository _repository;
  final MastersExportRepository _exportRepository;
  final SyncService _syncService;
  final NetworkMonitorService _networkMonitorService;

  final searchController = TextEditingController();
  final searchQuery = ''.obs;
  final isLoading = false.obs;
  final isLoadingMore = false.obs;
  final items = <ItemCategoryEntity>[].obs;
  final selectedStatus = 'All'.obs;
  final currentPage = 1.obs;
  final lastPage = 1.obs;
  final total = 0.obs;
  final scrollController = ScrollController();

  static const int pageSize = 20;

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

  List<ItemCategoryEntity> get filteredItems => items;

  bool get hasMore => currentPage.value < lastPage.value;

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

  Future<void> applyFilters({required String status}) async {
    selectedStatus.value = status;
    await refreshData();
  }

  Future<void> clearFilters() async {
    selectedStatus.value = 'All';
    await refreshData();
  }

  Future<void> refreshData({bool forceRemote = false}) async {
    isLoading.value = true;
    currentPage.value = 1;
    try {
      final localResult = forceRemote
          ? null
          : await _repository.getPaginatedCategories(
              queryParameters: _queryParameters,
              page: 1,
              perPage: pageSize,
            );
      if (localResult != null) {
        _applyPage(localResult, reset: true);
      }

      if (forceRemote || await _networkMonitorService.hasInternetNow()) {
        final remoteResult = await _repository.refreshPaginatedCategories(
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
          ? _repository.refreshPaginatedCategories(
              queryParameters: _queryParameters,
              page: nextPage,
              perPage: pageSize,
            )
          : _repository.getPaginatedCategories(
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
      items.assignAll(
        _mergeCategories(
          List<ItemCategoryEntity>.from(result.items),
          base: const <ItemCategoryEntity>[],
        ),
      );
    } else {
      items.assignAll(
        _mergeCategories(
          List<ItemCategoryEntity>.from(result.items),
          base: items,
        ),
      );
    }
  }

  List<ItemCategoryEntity> _mergeCategories(
    List<ItemCategoryEntity> incoming, {
    required List<ItemCategoryEntity> base,
  }) {
    final merged = <String, ItemCategoryEntity>{};
    for (final item in base) {
      merged[_categoryKey(item)] = item;
    }
    for (final item in incoming) {
      merged[_categoryKey(item)] = item;
    }
    return merged.values.toList();
  }

  String _categoryKey(ItemCategoryEntity item) {
    final id = item.id?.toString();
    if (id != null && id.isNotEmpty) {
      return 'id:$id';
    }
    final localId = item.localId?.trim();
    if (localId != null && localId.isNotEmpty) {
      return 'local:$localId';
    }
    return 'name:${item.name}';
  }

  Future<void> save(ItemCategoryEntity entity) async {
    if (entity.id == null) {
      await _repository.create(entity);
      AppSnackbar.success('Category saved. Syncing to server...');
    } else {
      await _repository.update(entity);
      AppSnackbar.success('Category update queued. Syncing to server...');
    }
    if (_networkMonitorService.isOnline.value) {
      await _syncService.syncPendingMutations(showSuccessMessage: true);
    }
    await refreshData();
  }

  Future<void> deleteItem(ItemCategoryEntity entity) async {
    try {
      await _repository.delete(entity);
      if (_networkMonitorService.isOnline.value) {
        await _syncService.syncPendingMutations(showSuccessMessage: false);
      }
      await refreshData(forceRemote: true);
      AppSnackbar.success('Category deleted.');
    } catch (error) {
      AppSnackbar.error(error.toString());
    }
  }

  Future<void> toggleStatus(ItemCategoryEntity entity, bool value) async {
    await _repository.toggleStatus(entity, value);
    await refreshData();
  }

  Future<void> exportExcel() {
    return exportMasterExcel(
      repository: _exportRepository,
      type: 'item-categories',
      reportName: 'item_categories',
      queryParameters: _exportQueryParameters,
      fallbackRows: _exportRows,
    );
  }

  Future<void> exportPdf() {
    return exportMasterPdf(
      repository: _exportRepository,
      type: 'item-categories',
      queryParameters: _exportQueryParameters,
      reportName: 'item_categories',
      fallbackRows: _exportRows,
    );
  }

  List<Map<String, dynamic>> get _exportRows => filteredItems
      .map(
        (item) => <String, dynamic>{
          'Name': item.name,
          'Description': item.description,
          'Sort Order': item.sortOrder,
          'Status': item.isActive ? 'Active' : 'Inactive',
        },
      )
      .toList();

  Map<String, dynamic> get _exportQueryParameters => <String, dynamic>{
        if (searchController.text.trim().isNotEmpty)
          'search': searchController.text.trim(),
        if (selectedStatus.value != 'All')
          'is_active': selectedStatus.value == 'Active' ? 1 : 0,
      };

  Map<String, dynamic> get _queryParameters => _exportQueryParameters;
}
