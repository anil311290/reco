import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/services/network_monitor_service.dart';
import '../../../core/services/sync_service.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/models/masters/master_entities.dart';
import '../../../data/repositories/accounts/accounts_repository.dart';
import '../../../data/repositories/masters/item_categories_repository.dart';
import '../../../data/repositories/masters/items_repository.dart';
import '../../../data/repositories/masters/masters_export_repository.dart';
import '../../../data/repositories/masters/tax_rates_repository.dart';
import 'masters_lookup_controller.dart';
import 'master_export_mixin.dart';

class ItemsController extends GetxController with MasterExportMixin {
  ItemsController(
    this._repository,
    this._itemCategoriesRepository,
    this._taxRatesRepository,
    this._accountsRepository,
    this._lookupController,
    this._exportRepository,
    this._syncService,
    this._networkMonitorService,
  );

  final ItemsRepository _repository;
  final ItemCategoriesRepository _itemCategoriesRepository;
  final TaxRatesRepository _taxRatesRepository;
  final AccountsRepository _accountsRepository;
  final MastersLookupController _lookupController;
  final MastersExportRepository _exportRepository;
  final SyncService _syncService;
  final NetworkMonitorService _networkMonitorService;

  final searchController = TextEditingController();
  final searchQuery = ''.obs;
  final isLoading = false.obs;
  final isLoadingMore = false.obs;
  final items = <ItemEntity>[].obs;
  final selectedType = 'All'.obs;
  final selectedCategory = 'All'.obs;
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

  List<ItemEntity> get filteredItems => items;

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

  bool get hasMore => currentPage.value < lastPage.value;

  Future<void> applyFilters({
    required String type,
    required String category,
    required String status,
  }) async {
    selectedType.value = type;
    selectedCategory.value = category;
    selectedStatus.value = status;
    await refreshData();
  }

  Future<void> clearFilters() async {
    selectedType.value = 'All';
    selectedCategory.value = 'All';
    selectedStatus.value = 'All';
    await refreshData();
  }

  Future<void> refreshData({bool forceRemote = false}) async {
    isLoading.value = true;
    try {
      currentPage.value = 1;
      final results = await Future.wait<dynamic>(<Future<dynamic>>[
        forceRemote
            ? _repository.refreshPaginatedItems(
                queryParameters: _queryParameters,
                page: 1,
                perPage: pageSize,
              )
            : _repository.getPaginatedItems(
                queryParameters: _queryParameters,
                page: 1,
                perPage: pageSize,
              ),
        _itemCategoriesRepository.getDropdownOptions(),
        _taxRatesRepository.getDropdownOptions(),
        _accountsRepository.getAccountsByType('income'),
        _accountsRepository.getAccountsByType('expense'),
      ]);
      _applyPage(results[0], reset: true);
      _lookupController.categories.assignAll(results[1] as List<LookupOption>);
      _lookupController.taxes.assignAll(results[2] as List<LookupOption>);
      _lookupController.incomeAccounts.assignAll(results[3] as List<LookupOption>);
      _lookupController.expenseAccounts.assignAll(results[4] as List<LookupOption>);
      if (!forceRemote && await _networkMonitorService.hasInternetNow()) {
        final remoteResult = await _repository.refreshPaginatedItems(
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
          ? _repository.refreshPaginatedItems(
              queryParameters: _queryParameters,
              page: nextPage,
              perPage: pageSize,
            )
          : _repository.getPaginatedItems(
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
        _mergeItems(
          List<ItemEntity>.from(result.items),
          base: const <ItemEntity>[],
        ),
      );
    } else {
      items.assignAll(
        _mergeItems(
          List<ItemEntity>.from(result.items),
          base: items,
        ),
      );
    }
  }

  List<ItemEntity> _mergeItems(
    List<ItemEntity> incoming, {
    required List<ItemEntity> base,
  }) {
    final merged = <String, ItemEntity>{};
    for (final item in base) {
      merged[_itemKey(item)] = item;
    }
    for (final item in incoming) {
      merged[_itemKey(item)] = item;
    }
    return merged.values.toList();
  }

  String _itemKey(ItemEntity item) {
    final id = item.id?.toString();
    if (id != null && id.isNotEmpty) {
      return 'id:$id';
    }
    final localId = item.localId?.trim();
    if (localId != null && localId.isNotEmpty) {
      return 'local:$localId';
    }
    return 'code:${item.itemCode}:${item.name}:${item.type}';
  }

  Future<void> save(ItemEntity entity) async {
    try {
      if (entity.id == null) {
        await _repository.create(entity);
      } else {
        await _repository.update(entity);
      }
      if (_networkMonitorService.isOnline.value) {
        await _syncService.syncPendingMutations(
          showSuccessMessage: false,
          propagateErrors: true,
        );
      }
      await refreshData();
      AppSnackbar.success(
        entity.id == null ? 'Item saved successfully.' : 'Item updated successfully.',
      );
    } catch (error) {
      AppSnackbar.errorDialog(extractApiErrorMessage(error));
    }
  }

  Future<void> deleteItem(ItemEntity entity) async {
    try {
      await _repository.delete(entity);
      if (_networkMonitorService.isOnline.value) {
        await _syncService.syncPendingMutations(
          showSuccessMessage: false,
          propagateErrors: true,
        );
      }
      await refreshData(forceRemote: true);
      AppSnackbar.success('Item deleted.');
    } catch (error) {
      AppSnackbar.errorDialog(extractApiErrorMessage(error));
    }
  }

  Future<void> toggleStatus(ItemEntity entity, bool value) async {
    await _repository.toggleStatus(entity, value);
    await refreshData();
  }

  Future<void> exportExcel() {
    return exportMasterExcel(
      repository: _exportRepository,
      type: 'items',
      reportName: 'items_master',
      queryParameters: _exportQueryParameters,
      fallbackRows: _exportRows,
    );
  }

  Future<void> exportPdf() {
    return exportMasterPdf(
      repository: _exportRepository,
      type: 'items',
      queryParameters: _exportQueryParameters,
      reportName: 'items_master',
      fallbackRows: _exportRows,
    );
  }

  List<Map<String, dynamic>> get _exportRows => filteredItems
      .map(
        (item) => <String, dynamic>{
          'Code': item.itemCode,
          'Name': item.name,
          'Category': item.categoryName,
          'Type': item.type,
          'HSN/SAC': item.hsnSacCode,
          'Selling Price': item.sellingPrice.toStringAsFixed(2),
          'Stock': item.currentStock.toStringAsFixed(2),
          'Status': item.isActive ? 'Active' : 'Inactive',
        },
      )
      .toList();

  Map<String, dynamic> get _exportQueryParameters => <String, dynamic>{
        if (searchController.text.trim().isNotEmpty)
          'search': searchController.text.trim(),
        if (selectedType.value != 'All') 'type': selectedType.value,
        if (selectedCategory.value != 'All')
          'category_id': _selectedCategoryId,
        if (selectedStatus.value != 'All')
          'is_active': selectedStatus.value == 'Active' ? 1 : 0,
      };

  int? get _selectedCategoryId {
    if (selectedCategory.value == 'All') {
      return null;
    }
    final match = _lookupController.categories.firstWhereOrNull(
      (item) => item.label == selectedCategory.value,
    );
    return match?.id;
  }

  Map<String, dynamic> get _queryParameters => _exportQueryParameters;
}
