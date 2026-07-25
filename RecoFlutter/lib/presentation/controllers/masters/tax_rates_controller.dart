import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/services/network_monitor_service.dart';
import '../../../core/services/sync_service.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/models/masters/master_entities.dart';
import '../../../data/repositories/masters/masters_export_repository.dart';
import '../../../data/repositories/masters/tax_rates_repository.dart';
import 'master_export_mixin.dart';

class TaxRatesController extends GetxController with MasterExportMixin {
  TaxRatesController(
    this._repository,
    this._exportRepository,
    this._syncService,
    this._networkMonitorService,
  );

  final TaxRatesRepository _repository;
  final MastersExportRepository _exportRepository;
  final SyncService _syncService;
  final NetworkMonitorService _networkMonitorService;

  final searchController = TextEditingController();
  final searchQuery = ''.obs;
  final isLoading = false.obs;
  final items = <TaxRateEntity>[].obs;
  final selectedCategory = 'All'.obs;
  final selectedType = 'All'.obs;
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

  List<TaxRateEntity> get filteredItems {
    final query = searchQuery.value;
    return items.where((item) {
      final matchesQuery =
          query.isEmpty ||
          item.taxName.toLowerCase().contains(query) ||
          item.taxCode.toLowerCase().contains(query) ||
          item.taxCategory.toLowerCase().contains(query);
      final matchesCategory =
          selectedCategory.value == 'All' ||
          item.taxCategory == selectedCategory.value;
      final matchesType =
          selectedType.value == 'All' || item.taxType == selectedType.value;
      final itemStatus = item.isActive ? 'Active' : 'Inactive';
      final matchesStatus =
          selectedStatus.value == 'All' || itemStatus == selectedStatus.value;
      return matchesQuery && matchesCategory && matchesType && matchesStatus;
    }).toList();
  }

  void applyFilters({
    required String category,
    required String type,
    required String status,
  }) {
    selectedCategory.value = category;
    selectedType.value = type;
    selectedStatus.value = status;
  }

  void clearFilters() {
    selectedCategory.value = 'All';
    selectedType.value = 'All';
    selectedStatus.value = 'All';
  }

  Future<void> refreshData() async {
    isLoading.value = true;
    try {
      items.assignAll(await _repository.getTaxRates());
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> save(TaxRateEntity entity) async {
    if (entity.id == null) {
      await _repository.create(entity);
      AppSnackbar.success('Tax rate saved. Syncing to server...');
    } else {
      await _repository.update(entity);
      AppSnackbar.success('Tax rate update queued. Syncing to server...');
    }
    if (_networkMonitorService.isOnline.value) {
      await _syncService.syncPendingMutations(showSuccessMessage: true);
    }
    await refreshData();
  }

  Future<void> deleteItem(TaxRateEntity entity) async {
    await _repository.delete(entity);
    await refreshData();
    AppSnackbar.success('Tax rate delete queued.');
  }

  Future<void> toggleStatus(TaxRateEntity entity, bool value) async {
    await _repository.toggleStatus(entity, value);
    await refreshData();
  }

  Future<void> exportExcel() {
    return exportMasterExcel(
      repository: _exportRepository,
      type: 'tax-rates',
      reportName: 'tax_rates',
      queryParameters: _exportQueryParameters,
      fallbackRows: filteredItems
          .map(
            (item) => <String, dynamic>{
              'Tax Code': item.taxCode,
              'Tax Name': item.taxName,
              'Category': item.taxCategory,
              'Type': item.taxType,
              'Rate': item.taxRate.toStringAsFixed(2),
              'Status': item.status,
              'Notes': item.notes,
            },
          )
          .toList(),
    );
  }

  Future<void> exportPdf() {
    return exportMasterPdf(
      repository: _exportRepository,
      type: 'tax-rates',
      queryParameters: _exportQueryParameters,
    );
  }

  Map<String, dynamic> get _exportQueryParameters => <String, dynamic>{
        if (searchController.text.trim().isNotEmpty)
          'search': searchController.text.trim(),
        if (selectedCategory.value != 'All')
          'tax_category': selectedCategory.value,
        if (selectedType.value != 'All') 'tax_type': selectedType.value,
        if (selectedStatus.value != 'All')
          'status': selectedStatus.value.toLowerCase(),
      };
}
