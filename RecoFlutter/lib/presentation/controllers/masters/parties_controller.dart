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
  final parties = <PartyEntity>[].obs;
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

  List<PartyEntity> get filteredItems {
    final query = searchQuery.value;
    return parties.where((item) {
      final matchesQuery =
          query.isEmpty ||
          item.name.toLowerCase().contains(query) ||
          item.partyCode.toLowerCase().contains(query) ||
          item.mobile.toLowerCase().contains(query);
      final matchesType =
          selectedType.value == 'All' || item.type == selectedType.value;
      final matchesStatus =
          selectedStatus.value == 'All' ||
          (selectedStatus.value == 'Active' && item.isActive) ||
          (selectedStatus.value == 'Inactive' && !item.isActive);
      return matchesQuery && matchesType && matchesStatus;
    }).toList();
  }

  void applyFilters({
    required String type,
    required String status,
  }) {
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
      parties.assignAll(
        forceRemote
            ? await _repository.refreshParties()
            : await _repository.getParties(),
      );
    } finally {
      isLoading.value = false;
    }
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
}
