import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import 'base_report_controller.dart';
import 'report_lookup_controller.dart';

class BankBookReportController extends BaseReportController {
  BankBookReportController(super.repository, super.networkMonitorService);

  final fromDateController = TextEditingController();
  final toDateController = TextEditingController();
  final accountId = RxnInt();

  @override
  String get endpoint => ApiEndpoints.reportsBankBook;

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
    if (lookup.bankAccounts.isEmpty) {
      await lookup.loadCashBankAccounts();
    }
    accountId.value ??= _asInt(
      lookup.bankAccounts.isNotEmpty ? lookup.bankAccounts.first['id'] : null,
    );
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
