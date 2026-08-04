import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../data/repositories/settings/audit_logs_repository.dart';

class AuditLogsController extends GetxController {
  AuditLogsController(
    this._repository, {
    this.initialModule,
    this.initialRecordId,
  });

  final AuditLogsRepository _repository;
  final String? initialModule;
  final String? initialRecordId;

  final isLoading = false.obs;
  final isRefreshing = false.obs;
  final refreshTurns = 0.0.obs;
  final isLoadingMore = false.obs;
  final logs = <Map<String, dynamic>>[].obs;
  final statistics = <String, dynamic>{}.obs;
  final actionOptions = <String>[].obs;
  final moduleOptions = <String>[].obs;
  final userOptions = <Map<String, dynamic>>[].obs;

  final selectedAction = ''.obs;
  final selectedModule = ''.obs;
  final selectedRecordId = ''.obs;
  final selectedUserId = ''.obs;
  final currentPage = 1.obs;
  final lastPage = 1.obs;
  final total = 0.obs;

  final searchController = TextEditingController();

  bool get hasMore => currentPage.value < lastPage.value;

  List<String> _uniqueStrings(Iterable<dynamic> values) {
    final seen = <String>{};
    final result = <String>[];
    for (final value in values) {
      final normalized = value.toString().trim();
      if (normalized.isEmpty || !seen.add(normalized)) {
        continue;
      }
      result.add(normalized);
    }
    return result;
  }

  List<Map<String, dynamic>> _uniqueUsersById(Iterable<dynamic> values) {
    final seen = <String>{};
    final result = <Map<String, dynamic>>[];
    for (final value in values.whereType<Map>()) {
      final item = Map<String, dynamic>.from(value);
      final id = item['id']?.toString().trim() ?? '';
      if (id.isEmpty || !seen.add(id)) {
        continue;
      }
      result.add(item);
    }
    return result;
  }

  @override
  void onInit() {
    super.onInit();
    selectedModule.value = initialModule ?? '';
    selectedRecordId.value = initialRecordId ?? '';
    loadLogs();
  }

  @override
  void onClose() {
    searchController.dispose();
    super.onClose();
  }

  Future<void> loadLogs({bool reset = true}) async {
    if (reset && !isLoading.value) {
      isRefreshing.value = true;
      refreshTurns.value += 1;
    }
    if (reset) {
      isLoading.value = true;
      currentPage.value = 1;
    } else {
      isLoadingMore.value = true;
    }

    try {
      final result = await _repository.getAuditLogs(
        search: searchController.text.trim().isEmpty
            ? null
            : searchController.text.trim(),
        action: selectedAction.value.isEmpty ? null : selectedAction.value,
        module: selectedModule.value.isEmpty ? null : selectedModule.value,
        recordId: selectedRecordId.value.isEmpty ? null : selectedRecordId.value,
        userId: selectedUserId.value.isEmpty ? null : selectedUserId.value,
        page: currentPage.value,
      );

      final records =
          (result['logs'] as List?)
              ?.whereType<Map>()
              .map((item) => Map<String, dynamic>.from(item))
              .toList() ??
          <Map<String, dynamic>>[];

      if (reset) {
        logs.assignAll(records);
      } else {
        logs.addAll(records);
      }

      statistics.assignAll(
        (result['statistics'] is Map<String, dynamic>)
            ? result['statistics'] as Map<String, dynamic>
            : <String, dynamic>{},
      );

      final filters = (result['filters'] is Map<String, dynamic>)
          ? result['filters'] as Map<String, dynamic>
          : <String, dynamic>{};
      actionOptions.assignAll(
        _uniqueStrings(filters['actions'] as List? ?? const <dynamic>[]),
      );
      moduleOptions.assignAll(
        _uniqueStrings(filters['modules'] as List? ?? const <dynamic>[]),
      );
      userOptions.assignAll(
        _uniqueUsersById(filters['users'] as List? ?? const <dynamic>[]),
      );

      if (selectedAction.value.isNotEmpty &&
          !actionOptions.contains(selectedAction.value)) {
        selectedAction.value = '';
      }
      if (selectedModule.value.isNotEmpty &&
          !moduleOptions.contains(selectedModule.value)) {
        selectedModule.value = '';
      }
      if (selectedUserId.value.isNotEmpty &&
          !userOptions.any(
            (item) => item['id']?.toString() == selectedUserId.value,
          )) {
        selectedUserId.value = '';
      }

      final pagination = (result['pagination'] is Map<String, dynamic>)
          ? result['pagination'] as Map<String, dynamic>
          : <String, dynamic>{};
      currentPage.value = int.tryParse(
            pagination['current_page']?.toString() ?? '1',
          ) ??
          1;
      lastPage.value = int.tryParse(
            pagination['last_page']?.toString() ?? '1',
          ) ??
          1;
      total.value =
          int.tryParse(pagination['total']?.toString() ?? '${logs.length}') ??
              logs.length;
    } finally {
      isLoading.value = false;
      isLoadingMore.value = false;
      if (reset) {
        isRefreshing.value = false;
      }
    }
  }

  Future<void> loadMore() async {
    if (!hasMore || isLoadingMore.value) return;
    currentPage.value++;
    await loadLogs(reset: false);
  }

  Future<void> applyFilters() async {
    await loadLogs();
  }

  void clearFilters() {
    selectedAction.value = '';
    selectedModule.value = '';
    selectedRecordId.value = '';
    selectedUserId.value = '';
    searchController.clear();
  }
}
