import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import 'base_report_controller.dart';

class LedgerReportController extends BaseReportController {
  LedgerReportController(super.repository, super.networkMonitorService);

  final fromDateController = TextEditingController();
  final toDateController = TextEditingController();
  final accountId = RxnInt();

  @override
  String get endpoint => ApiEndpoints.reportsLedger;

  @override
  Map<String, dynamic> get queryParameters => <String, dynamic>{
        if (accountId.value != null) 'account_id': accountId.value,
        if (fromDateController.text.isNotEmpty) 'date_from': fromDateController.text,
        if (toDateController.text.isNotEmpty) 'date_to': toDateController.text,
      };

  @override
  void onInit() {
    super.onInit();
    if (accountId.value != null) {
      loadReport();
    }
  }

  @override
  void onClose() {
    fromDateController.dispose();
    toDateController.dispose();
    super.onClose();
  }
}
