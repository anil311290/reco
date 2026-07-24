import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import 'base_report_controller.dart';

class DayBookReportController extends BaseReportController {
  DayBookReportController(super.repository, super.networkMonitorService);

  final dateController = TextEditingController(
    text: DateTime.now().toIso8601String().substring(0, 10),
  );
  final financialYearId = RxnInt();

  @override
  String get endpoint => ApiEndpoints.reportsDayBook;

  @override
  Map<String, dynamic> get queryParameters => <String, dynamic>{
        'date': dateController.text,
        if (financialYearId.value != null) 'financial_year_id': financialYearId.value,
      };

  @override
  void onInit() {
    super.onInit();
    loadReport();
  }

  @override
  void onClose() {
    dateController.dispose();
    super.onClose();
  }
}
