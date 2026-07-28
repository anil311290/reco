import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import 'base_report_controller.dart';
import 'report_lookup_controller.dart';

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
    _initialize();
  }

  Future<void> _initialize() async {
    final lookup = Get.find<ReportLookupController>();
    if (lookup.ledgerAccounts.isEmpty) {
      await lookup.loadLedgerAccounts();
    }
    if (accountId.value == null && lookup.ledgerAccounts.isNotEmpty) {
      accountId.value = _asInt(lookup.ledgerAccounts.first['id']);
    }
    if (accountId.value != null) {
      await loadReport();
    }
  }

  Future<void> openLinkedLedger(int accountIdValue) async {
    final lookup = Get.find<ReportLookupController>();
    if (lookup.ledgerAccounts.isEmpty) {
      await lookup.loadLedgerAccounts();
    }
    accountId.value = accountIdValue;
    fromDateController.clear();
    toDateController.clear();
    reportData.clear();
    hasLoadedOnce.value = false;
    await loadReport();
  }

  @override
  void onClose() {
    fromDateController.dispose();
    toDateController.dispose();
    super.onClose();
  }

  int? _asInt(dynamic value) => int.tryParse(value?.toString() ?? '');
}
