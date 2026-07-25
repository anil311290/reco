import 'package:get/get.dart';

import '../../../core/services/network_monitor_service.dart';
import '../../../core/services/sync_service.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/models/masters/master_entities.dart';
import '../../../data/repositories/masters/financial_years_repository.dart';

class FinancialYearsController extends GetxController {
  FinancialYearsController(
    this._repository,
    this._syncService,
    this._networkMonitorService,
  );

  final FinancialYearsRepository _repository;
  final SyncService _syncService;
  final NetworkMonitorService _networkMonitorService;

  final isLoading = false.obs;
  final isProcessing = false.obs;
  final financialYears = <FinancialYearEntity>[].obs;
  final currentFinancialYear = Rxn<FinancialYearEntity>();

  bool get isOnline => _networkMonitorService.isOnline.value;
  bool get isSyncing => _syncService.isSyncing.value;

  @override
  void onInit() {
    super.onInit();
    refreshData();
  }

  Future<void> refreshData() async {
    isLoading.value = true;
    try {
      final years = await _repository.getFinancialYears();
      financialYears.assignAll(years);

      final current = await _repository.getCurrentFinancialYear();
      currentFinancialYear.value = current;
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> createFinancialYear(FinancialYearEntity entity) async {
    isProcessing.value = true;
    try {
      await _repository.create(entity);
      AppSnackbar.success('Financial year created successfully.');
      await refreshData();
    } catch (e) {
      AppSnackbar.error('Failed to create financial year: $e');
    } finally {
      isProcessing.value = false;
    }
  }

  Future<void> updateFinancialYear(FinancialYearEntity entity) async {
    isProcessing.value = true;
    try {
      await _repository.update(entity);
      AppSnackbar.success('Financial year updated successfully.');
      await refreshData();
    } catch (e) {
      AppSnackbar.error('Failed to update financial year: $e');
    } finally {
      isProcessing.value = false;
    }
  }

  Future<void> deleteFinancialYear(FinancialYearEntity entity) async {
    if (entity.isCurrent) {
      AppSnackbar.error('Cannot delete the current financial year.');
      return;
    }
    if (entity.isClosed) {
      AppSnackbar.error('Cannot delete a closed financial year.');
      return;
    }

    isProcessing.value = true;
    try {
      await _repository.delete(entity);
      AppSnackbar.success('Financial year deleted successfully.');
      await refreshData();
    } catch (e) {
      AppSnackbar.error('Failed to delete financial year: $e');
    } finally {
      isProcessing.value = false;
    }
  }

  Future<void> setAsCurrent(FinancialYearEntity entity) async {
    if (entity.isCurrent) {
      AppSnackbar.error('This is already the current financial year.');
      return;
    }
    if (entity.isClosed) {
      AppSnackbar.error('Cannot set a closed financial year as current.');
      return;
    }

    isProcessing.value = true;
    try {
      await _repository.setAsCurrent(entity);
      AppSnackbar.success('Financial year set as current successfully.');
      await refreshData();
    } catch (e) {
      AppSnackbar.error('Failed to set as current: $e');
    } finally {
      isProcessing.value = false;
    }
  }

  Future<void> closeFinancialYear(FinancialYearEntity entity) async {
    if (entity.isClosed) {
      AppSnackbar.error('Financial year is already closed.');
      return;
    }
    if (entity.isCurrent) {
      AppSnackbar.error(
        'Cannot close the current financial year. '
        'Set another year as current first.',
      );
      return;
    }

    isProcessing.value = true;
    try {
      await _repository.closeYear(entity);
      AppSnackbar.success('Financial year closed successfully.');
      await refreshData();
    } catch (e) {
      AppSnackbar.error('Failed to close financial year: $e');
    } finally {
      isProcessing.value = false;
    }
  }
}
