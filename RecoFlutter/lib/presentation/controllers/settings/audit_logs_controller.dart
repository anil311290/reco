import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../data/repositories/settings/audit_logs_repository.dart';

class AuditLogsController extends GetxController {
  AuditLogsController(this._repository);

  final AuditLogsRepository _repository;

  final isLoading = false.obs;
  final isLoadingMore = false.obs;
  final logs = <Map<String, dynamic>>[].obs;
  final statistics = <String, dynamic>{}.obs;
  final actionOptions = <String>[].obs;
  final moduleOptions = <String>[].obs;
  final userOptions = <Map<String, dynamic>>[].obs;

  final selectedAction = ''.obs;
  final selectedModule = ''.obs;
  final selectedUserId = ''.obs;
  final currentPage = 1.obs;
  final lastPage = 1.obs;
  final total = 0.obs;

  final searchController = TextEditingController();

  @override
  void onInit() {
    super.onInit();
    loadLogs();
  }

  @override
  void onClose() {
    searchController.dispose();
    super.onClose();
  }

  Future<void> loadLogs({bool reset = true}) async {
    if (reset) {
      isLoading.value = true;
      currentPage.value = 1;
    } else {
      isLoadingMore.value = true;
    }

    try {
      final result = await _repository.getAuditLogs(
        search: searchController.text.trim(),
        action: selectedAction.value,
        module: selectedModule.value,
        userId: selectedUserId.value,
        page: currentPage.value,
      );

      final records = _extractList(result['logs']);
      if (reset) {
        logs.assignAll(records);
      } else {
        logs.addAll(records);
      }

      statistics.assignAll(_extractMap(result['statistics']));

      final filters = _extractMap(result['filters']);
      actionOptions.assignAll(
        (filters['actions'] as List? ?? const <dynamic>[])
            .map((item) => item.toString())
            .where((item) => item.isNotEmpty)
            .toList(),
      );
      moduleOptions.assignAll(
        (filters['modules'] as List? ?? const <dynamic>[])
            .map((item) => item.toString())
            .where((item) => item.isNotEmpty)
            .toList(),
      );
      userOptions.assignAll(
        _extractList(filters['users']),
      );

      final pagination = _extractMap(result['pagination']);
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
    }
  }

  Future<void> loadMore() async {
    if (isLoadingMore.value || currentPage.value >= lastPage.value) {
      return;
    }
    currentPage.value += 1;
    await loadLogs(reset: false);
  }

  Future<void> applyFilters() async {
    await loadLogs();
  }

  void clearFilters() {
    searchController.clear();
    selectedAction.value = '';
    selectedModule.value = '';
    selectedUserId.value = '';
  }

  bool get hasMore => currentPage.value < lastPage.value;

  List<Map<String, dynamic>> _extractList(dynamic data) {
    if (data is List) {
      return data
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }
    return <Map<String, dynamic>>[];
  }

  Map<String, dynamic> _extractMap(dynamic data) {
    if (data is Map<String, dynamic>) {
      return Map<String, dynamic>.from(data);
    }
    if (data is Map) {
      return Map<String, dynamic>.from(data);
    }
    return <String, dynamic>{};
  }
}
