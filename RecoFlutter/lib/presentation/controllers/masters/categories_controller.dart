import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_snackbar.dart';
import '../../../data/models/masters/master_entities.dart';
import '../../../data/repositories/masters/item_categories_repository.dart';
import '../../../data/repositories/masters/masters_export_repository.dart';
import 'master_export_mixin.dart';

class CategoriesController extends GetxController with MasterExportMixin {
  CategoriesController(this._repository, this._exportRepository);

  final ItemCategoriesRepository _repository;
  final MastersExportRepository _exportRepository;

  final searchController = TextEditingController();
  final searchQuery = ''.obs;
  final isLoading = false.obs;
  final items = <ItemCategoryEntity>[].obs;
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

  List<ItemCategoryEntity> get filteredItems {
    final query = searchQuery.value;
    return items.where((item) {
      final matchesQuery =
          query.isEmpty ||
          item.name.toLowerCase().contains(query) ||
          item.description.toLowerCase().contains(query);
      final matchesStatus =
          selectedStatus.value == 'All' ||
          (selectedStatus.value == 'Active' && item.isActive) ||
          (selectedStatus.value == 'Inactive' && !item.isActive);
      return matchesQuery && matchesStatus;
    }).toList();
  }

  void applyFilters({required String status}) {
    selectedStatus.value = status;
  }

  void clearFilters() {
    selectedStatus.value = 'All';
  }

  Future<void> refreshData() async {
    isLoading.value = true;
    try {
      items.assignAll(await _repository.getCategories());
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> save(ItemCategoryEntity entity) async {
    if (entity.id == null) {
      await _repository.create(entity);
      AppSnackbar.success('Category saved locally. Sync queue updated.');
    } else {
      await _repository.update(entity);
      AppSnackbar.success('Category update queued successfully.');
    }
    await refreshData();
  }

  Future<void> deleteItem(ItemCategoryEntity entity) async {
    await _repository.delete(entity);
    await refreshData();
    AppSnackbar.success('Category delete queued.');
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
      fallbackRows: filteredItems
          .map(
            (item) => <String, dynamic>{
              'Name': item.name,
              'Description': item.description,
              'Sort Order': item.sortOrder,
              'Status': item.isActive ? 'Active' : 'Inactive',
            },
          )
          .toList(),
    );
  }

  Future<void> exportPdf() {
    return exportMasterPdf(
      repository: _exportRepository,
      type: 'item-categories',
      queryParameters: _exportQueryParameters,
    );
  }

  Map<String, dynamic> get _exportQueryParameters => <String, dynamic>{
        if (searchController.text.trim().isNotEmpty)
          'search': searchController.text.trim(),
        if (selectedStatus.value != 'All')
          'is_active': selectedStatus.value == 'Active' ? 1 : 0,
      };
}
