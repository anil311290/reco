import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/utils/app_date_formatter.dart';
import 'base_report_controller.dart';
import 'report_lookup_controller.dart';

class DayBookReportController extends BaseReportController {
  DayBookReportController(super.repository, super.networkMonitorService);

  final fromDateController = TextEditingController();
  final toDateController = TextEditingController();
  final financialYearId = RxnInt();

  @override
  String get endpoint => ApiEndpoints.reportsDayBook;

  @override
  Map<String, dynamic> get queryParameters => <String, dynamic>{
        'date': AppDateFormatter.toApiDate(fromDateController.text),
        'date_from': AppDateFormatter.toApiDate(fromDateController.text),
        'date_to': AppDateFormatter.toApiDate(toDateController.text),
        if (financialYearId.value != null) 'financial_year_id': financialYearId.value,
      };

  @override
  void onInit() {
    super.onInit();
    _initializeDefaults();
  }

  Future<void> _initializeDefaults() async {
    final lookup = Get.find<ReportLookupController>();
    if (lookup.financialYears.isEmpty || lookup.currentFinancialYearId.value == null) {
      await lookup.preload();
    }
    financialYearId.value = lookup.currentFinancialYearId.value;
    final today = DateTime.now();
    final todayDisplay = AppDateFormatter.formatDisplay(today);
    fromDateController.text = todayDisplay;
    toDateController.text = todayDisplay;
    await loadReport();
  }

  void applyFinancialYear(int? value, ReportLookupController lookup) {
    financialYearId.value = value;
    fromDateController.text = lookup.formatFinancialYearStart(value);
    toDateController.text = lookup.formatFinancialYearEnd(value);
  }

  @override
  void onClose() {
    fromDateController.dispose();
    toDateController.dispose();
    super.onClose();
  }
}
