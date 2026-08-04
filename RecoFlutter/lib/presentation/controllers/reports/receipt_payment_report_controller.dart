import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/utils/app_date_formatter.dart';
import 'base_report_controller.dart';
import 'report_lookup_controller.dart';

class ReceiptPaymentReportController extends BaseReportController {
  ReceiptPaymentReportController(super.repository, super.networkMonitorService);

  final fromDateController = TextEditingController();
  final toDateController = TextEditingController();
  final financialYearId = RxnInt();

  @override
  String get endpoint => ApiEndpoints.reportsReceiptPayment;

  @override
  Map<String, dynamic> get queryParameters => <String, dynamic>{
        if (financialYearId.value != null) 'financial_year_id': financialYearId.value,
        if (fromDateController.text.isNotEmpty)
          'date_from': AppDateFormatter.toApiDate(fromDateController.text),
        if (toDateController.text.isNotEmpty)
          'date_to': AppDateFormatter.toApiDate(toDateController.text),
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
    applyFinancialYear(lookup.currentFinancialYearId.value, lookup);
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
