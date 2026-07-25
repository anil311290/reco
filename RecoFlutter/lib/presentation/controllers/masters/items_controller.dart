import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';

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
  final items = <ItemEntity>[].obs;
  final selectedType = 'All'.obs;
  final selectedCategory = 'All'.obs;
  final selectedStatus = 'All'.obs;

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

  List<ItemEntity> get filteredItems {
    final query = searchQuery.value;
    return items.where((item) {
      final matchesQuery =
          query.isEmpty ||
          item.name.toLowerCase().contains(query) ||
          item.itemCode.toLowerCase().contains(query) ||
          item.categoryName.toLowerCase().contains(query);
      final matchesType =
          selectedType.value == 'All' || item.type == selectedType.value;
      final matchesCategory =
          selectedCategory.value == 'All' ||
          item.categoryName == selectedCategory.value;
      final matchesStatus =
          selectedStatus.value == 'All' ||
          (selectedStatus.value == 'Active' && item.isActive) ||
          (selectedStatus.value == 'Inactive' && !item.isActive);
      return matchesQuery &&
          matchesType &&
          matchesCategory &&
          matchesStatus;
    }).toList();
  }

  void applyFilters({
    required String type,
    required String category,
    required String status,
  }) {
    selectedType.value = type;
    selectedCategory.value = category;
    selectedStatus.value = status;
  }

  void clearFilters() {
    selectedType.value = 'All';
    selectedCategory.value = 'All';
    selectedStatus.value = 'All';
  }

  Future<void> refreshData() async {
    isLoading.value = true;
    try {
      final results = await Future.wait<dynamic>(<Future<dynamic>>[
        _repository.getItems(),
        _itemCategoriesRepository.getDropdownOptions(),
        _taxRatesRepository.getDropdownOptions(),
        _accountsRepository.getAccountsByType('income'),
        _accountsRepository.getAccountsByType('expense'),
      ]);
      items.assignAll(results[0] as List<ItemEntity>);
      _lookupController.categories.assignAll(results[1] as List<LookupOption>);
      _lookupController.taxes.assignAll(results[2] as List<LookupOption>);
      _lookupController.incomeAccounts.assignAll(results[3] as List<LookupOption>);
      _lookupController.expenseAccounts.assignAll(results[4] as List<LookupOption>);
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> save(ItemEntity entity) async {
    if (entity.id == null) {
      await _repository.create(entity);
      AppSnackbar.success('Item saved. Syncing to server...');
    } else {
      await _repository.update(entity);
      AppSnackbar.success('Item update queued. Syncing to server...');
    }
    if (_networkMonitorService.isOnline.value) {
      await _syncService.syncPendingMutations(showSuccessMessage: true);
    }
    await refreshData();
  }

  Future<void> deleteItem(ItemEntity entity) async {
    await _repository.delete(entity);
    await refreshData();
    AppSnackbar.success('Item delete queued.');
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
      fallbackRows: filteredItems
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
          .toList(),
    );
  }

  Future<void> exportPdf() {
    return exportMasterPdf(
      repository: _exportRepository,
      type: 'items',
      queryParameters: _exportQueryParameters,
    );
  }

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
}
