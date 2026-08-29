import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/utils/app_date_formatter.dart';
import 'base_report_controller.dart';
import 'report_lookup_controller.dart';

class UnappliedReceiptsReportController extends BaseReportController {
  UnappliedReceiptsReportController(super.repository, super.networkMonitorService);

  final fromDateController = TextEditingController();
  final toDateController = TextEditingController();

  @override
  String get endpoint => ApiEndpoints.reportsUnappliedReceipts;

  @override
  Map<String, dynamic> get queryParameters => <String, dynamic>{
        if (fromDateController.text.isNotEmpty)
          'from_date': AppDateFormatter.toApiDate(fromDateController.text),
        if (toDateController.text.isNotEmpty)
          'to_date': AppDateFormatter.toApiDate(toDateController.text),
      };

  @override
  void onInit() {
    super.onInit();
    final now = DateTime.now();
    fromDateController.text =
        AppDateFormatter.formatDisplay(DateTime(now.year, now.month, 1));
    toDateController.text = AppDateFormatter.formatDisplay(now);
    loadReport();
  }

  List<Map<String, dynamic>> listFor(String key) {
    final data = reportData['data'];
    if (data is! Map || data[key] is! List) return <Map<String, dynamic>>[];
    return (data[key] as List)
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  @override
  void onClose() {
    fromDateController.dispose();
    toDateController.dispose();
    super.onClose();
  }
}

class StockRegisterReportController extends BaseReportController {
  StockRegisterReportController(super.repository, super.networkMonitorService);

  final fromDateController = TextEditingController();
  final toDateController = TextEditingController();
  final itemId = RxnInt();
  final itemOptions = <Map<String, dynamic>>[].obs;

  @override
  String get endpoint => ApiEndpoints.reportsStockRegister;

  @override
  Map<String, dynamic> get queryParameters => <String, dynamic>{
        if (fromDateController.text.isNotEmpty)
          'from_date': AppDateFormatter.toApiDate(fromDateController.text),
        if (toDateController.text.isNotEmpty)
          'to_date': AppDateFormatter.toApiDate(toDateController.text),
        if (itemId.value != null) 'item_id': itemId.value,
      };

  @override
  void onInit() {
    super.onInit();
    final now = DateTime.now();
    fromDateController.text =
        AppDateFormatter.formatDisplay(DateTime(now.year, now.month, 1));
    toDateController.text = AppDateFormatter.formatDisplay(now);
    _loadItems();
    loadReport();
  }

  Future<void> _loadItems() async {
    try {
      final response = await repository.apiClient.get<Map<String, dynamic>>(
        ApiEndpoints.itemsDropdown,
      );
      final data = response.data?['data'];
      final list = <Map<String, dynamic>>[];
      if (data is List) {
        for (final item in data.whereType<Map>()) {
          list.add(Map<String, dynamic>.from(item));
        }
      } else if (data is Map && data['items'] is List) {
        for (final item in (data['items'] as List).whereType<Map>()) {
          list.add(Map<String, dynamic>.from(item));
        }
      }
      itemOptions.assignAll(list);
    } catch (_) {}
  }

  List<Map<String, dynamic>> get rows {
    final data = reportData['data'];
    if (data is! Map || data['rows'] is! List) return <Map<String, dynamic>>[];
    return (data['rows'] as List)
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  @override
  void onClose() {
    fromDateController.dispose();
    toDateController.dispose();
    super.onClose();
  }
}

class SettlementAuditReportController extends BaseReportController {
  SettlementAuditReportController(super.repository, super.networkMonitorService);

  final fromDateController = TextEditingController();
  final toDateController = TextEditingController();
  final statusFilter = 'all'.obs;
  final typeFilter = 'all'.obs;

  static const statusOptions = <MapEntry<String, String>>[
    MapEntry('all', 'All Status'),
    MapEntry('pending', 'Pending'),
    MapEntry('partial', 'Partial'),
    MapEntry('full', 'Full'),
    MapEntry('reversed', 'Reversed'),
  ];

  static const typeOptions = <MapEntry<String, String>>[
    MapEntry('all', 'All Types'),
    MapEntry('sales', 'Sales'),
    MapEntry('purchase', 'Purchase'),
  ];

  @override
  String get endpoint => ApiEndpoints.reportsSettlementAudit;

  @override
  Map<String, dynamic> get queryParameters {
    final params = <String, dynamic>{
      if (fromDateController.text.isNotEmpty)
        'date_from': AppDateFormatter.toApiDate(fromDateController.text),
      if (toDateController.text.isNotEmpty)
        'date_to': AppDateFormatter.toApiDate(toDateController.text),
    };
    if (statusFilter.value != 'all') {
      params['filters[status]'] = statusFilter.value;
    }
    if (typeFilter.value != 'all') {
      params['filters[type]'] = typeFilter.value;
    }
    return params;
  }

  @override
  void onInit() {
    super.onInit();
    final lookup = Get.find<ReportLookupController>();
    if (lookup.financialYears.isEmpty) {
      lookup.preload().then((_) {
        fromDateController.text =
            lookup.formatFinancialYearStart(lookup.currentFinancialYearId.value);
        toDateController.text =
            lookup.formatFinancialYearEnd(lookup.currentFinancialYearId.value);
        loadReport();
      });
    } else {
      fromDateController.text =
          lookup.formatFinancialYearStart(lookup.currentFinancialYearId.value);
      toDateController.text =
          lookup.formatFinancialYearEnd(lookup.currentFinancialYearId.value);
      loadReport();
    }
  }

  List<Map<String, dynamic>> get mappings {
    final data = reportData['data'];
    if (data is! Map || data['mappings'] is! List) {
      return <Map<String, dynamic>>[];
    }
    return (data['mappings'] as List)
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  Map<String, dynamic> get summary {
    final data = reportData['data'];
    if (data is Map && data['summary'] is Map) {
      return Map<String, dynamic>.from(data['summary'] as Map);
    }
    return <String, dynamic>{};
  }

  @override
  void onClose() {
    fromDateController.dispose();
    toDateController.dispose();
    super.onClose();
  }
}
