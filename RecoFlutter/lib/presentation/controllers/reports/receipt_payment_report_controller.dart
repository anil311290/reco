import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import 'base_report_controller.dart';

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
        if (fromDateController.text.isNotEmpty) 'date_from': fromDateController.text,
        if (toDateController.text.isNotEmpty) 'date_to': toDateController.text,
      };

  @override
  void onInit() {
    super.onInit();
    loadReport();
  }

  @override
  void onClose() {
    fromDateController.dispose();
    toDateController.dispose();
    super.onClose();
  }
}
