import 'dart:io';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';

import '../../../core/utils/app_action_loader.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../core/utils/simple_table_pdf_builder.dart';
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
        _mergeWithFallback(
          _uniqueStrings(filters['actions'] as List? ?? const <dynamic>[]),
          _fallbackActionOptions,
        ),
      );
      moduleOptions.assignAll(
        _mergeWithFallback(
          _uniqueStrings(filters['modules'] as List? ?? const <dynamic>[]),
          _fallbackModuleOptions,
        ),
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

  static const _fallbackActionOptions = <String>[
    'create',
    'update',
    'delete',
    'login',
    'logout',
    'status_change',
  ];

  static const _fallbackModuleOptions = <String>[
    'accounts',
    'parties',
    'items',
    'item_categories',
    'tax_rates',
    'financial_years',
    'ledger',
    'sales_invoice',
    'purchase_invoice',
    'payment_voucher',
    'receipt_voucher',
  ];

  List<String> _mergeWithFallback(Iterable<String> serverValues, List<String> fallback) {
    final seen = <String>{};
    final merged = <String>[];
    for (final value in <String>[...serverValues, ...fallback]) {
      final normalized = value.trim();
      if (normalized.isEmpty) continue;
      if (seen.add(normalized)) {
        merged.add(normalized);
      }
    }
    return merged;
  }

  Future<void> clearFilters() async {
    selectedAction.value = '';
    selectedModule.value = '';
    selectedRecordId.value = '';
    selectedUserId.value = '';
    searchController.clear();
    await loadLogs();
  }

  Future<void> exportLogs(String format) async {
    if (logs.isEmpty) {
      AppSnackbar.error('No data to export.');
      return;
    }
    await AppActionLoader.run(
      () async {
        final rows = logs
            .map(
              (log) => <String, dynamic>{
                'date': (log['created_at'] ?? '').toString(),
                'user': log['user'] is Map
                    ? (log['user']['name'] ?? 'System').toString()
                    : 'System',
                'action': (log['action'] ?? '').toString(),
                'module': (log['module'] ?? '').toString(),
                'amount': _extractAmount(log),
                'description': (log['description'] ?? '').toString(),
                'ip': (log['ip_address'] ?? '').toString(),
              },
            )
            .toList();
        final directory = await getTemporaryDirectory();
        final timestamp = DateTime.now().millisecondsSinceEpoch;
        if (format == 'excel') {
          final fileName = 'audit_logs_$timestamp.csv';
          final file = File(p.join(directory.path, fileName));
          await file.writeAsString(_buildCsv(rows));
          await _shareOrOpen(file.path, 'Audit Logs');
        } else {
          final fileName = 'audit_logs_$timestamp.pdf';
          final file = File(p.join(directory.path, fileName));
          await file.writeAsBytes(
            SimpleTablePdfBuilder.build(
              title: 'Audit Logs',
              rows: rows,
            ),
          );
          await _shareOrOpen(file.path, 'Audit Logs');
        }
      },
      message: 'Preparing export...',
    );
  }

  String _buildCsv(List<Map<String, dynamic>> rows) {
    const headers = <String>[
      'date',
      'user',
      'action',
      'module',
      'amount',
      'description',
      'ip',
    ];
    final buffer = StringBuffer()..writeln(headers.join(','));
    for (final row in rows) {
      buffer.writeln(
        headers.map((h) => _escape(row[h]?.toString() ?? '')).join(','),
      );
    }
    return buffer.toString();
  }

  String _escape(String value) {
    final escaped = value.replaceAll('"', '""');
    return '"$escaped"';
  }

  Future<void> _shareOrOpen(String filePath, String reportName) async {
    final openResult = await OpenFilex.open(filePath);
    if (openResult.type == ResultType.done) {
      AppSnackbar.success('$reportName exported successfully.');
      return;
    }
    final shareResult = await SharePlus.instance.share(
      ShareParams(
        files: <XFile>[XFile(filePath)],
        subject: '$reportName Export',
        text: '$reportName exported successfully.',
      ),
    );
    if (shareResult.status == ShareResultStatus.success ||
        shareResult.status == ShareResultStatus.dismissed) {
      AppSnackbar.success('$reportName exported successfully.');
      return;
    }
    AppSnackbar.error('Unable to open the export.');
  }

  String? _extractAmount(Map<String, dynamic> log) {
    final newValues = log['new_values'];
    final oldValues = log['old_values'];
    final dynamic fromNew =
        newValues is Map ? newValues['opening_balance'] : null;
    final dynamic fromOld =
        oldValues is Map ? oldValues['opening_balance'] : null;
    final dynamic amountField = log['amount'];
    final resolved = amountField ?? fromNew ?? fromOld;
    if (resolved == null) return null;
    final text = resolved.toString();
    if (text.isEmpty || text == 'null' || text == '-') return null;
    return text;
  }
}
