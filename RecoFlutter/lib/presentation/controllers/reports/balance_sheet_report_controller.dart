import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/utils/app_date_formatter.dart';
import 'base_report_controller.dart';
import 'report_lookup_controller.dart';

class BalanceSheetReportController extends BaseReportController {
  BalanceSheetReportController(super.repository, super.networkMonitorService);

  final asOfDateController = TextEditingController();
  final financialYearId = RxnInt();

  @override
  String get endpoint => ApiEndpoints.reportsBalanceSheet;

  @override
  Map<String, dynamic> get queryParameters => <String, dynamic>{
        if (financialYearId.value != null) 'financial_year_id': financialYearId.value,
        if (asOfDateController.text.isNotEmpty)
          'as_of_date': AppDateFormatter.toApiDate(asOfDateController.text),
      };

  @override
  void onInit() {
    super.onInit();
    _initializeDefaults();
  }

  Future<void> _initializeDefaults() async {
    final lookup = Get.find<ReportLookupController>();
    if (lookup.financialYears.isEmpty ||
        lookup.currentFinancialYearId.value == null) {
      await lookup.preload();
    }
    applyFinancialYear(lookup.currentFinancialYearId.value, lookup);
    await loadReport();
  }

  void applyFinancialYear(int? value, ReportLookupController lookup) {
    financialYearId.value = value;
    // Web balance-sheet as_of_date defaults to today.
    lookup.applyAsOfToday(asOfDateController);
  }

  @override
  void onClose() {
    asOfDateController.dispose();
    super.onClose();
  }
}
