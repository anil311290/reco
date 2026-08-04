import 'dart:async';

import 'package:get/get.dart';

import '../../../core/utils/app_date_formatter.dart';
import '../../../data/repositories/accounts/accounts_repository.dart';
import '../../../data/repositories/reports/reports_repository.dart';

class ReportLookupController extends GetxController {
  ReportLookupController(
    this._reportsRepository,
    this._accountsRepository,
  );

  final ReportsRepository _reportsRepository;
  final AccountsRepository _accountsRepository;

  final financialYears = <Map<String, dynamic>>[].obs;
  final currentFinancialYearId = RxnInt();
  final currentFinancialYear = <String, dynamic>{}.obs;
  final ledgerAccounts = <Map<String, dynamic>>[].obs;

  @override
  void onInit() {
    super.onInit();
    unawaited(preload());
  }

  Future<void> preload() async {
    await Future.wait(<Future<void>>[
      loadFinancialYears(),
      loadLedgerAccounts(),
    ]);
  }

  Future<void> loadFinancialYears() async {
    financialYears.assignAll(await _reportsRepository.getFinancialYears());
    final current = await _reportsRepository.getCurrentFinancialYear();
    currentFinancialYear.assignAll(current ?? <String, dynamic>{});
    currentFinancialYearId.value = _asInt(current?['id']);
  }

  Future<void> loadLedgerAccounts() async {
    final records = await _accountsRepository.getAccounts();
    ledgerAccounts.assignAll(
      records
          .map((item) => item['payload'] is Map<String, dynamic>
              ? Map<String, dynamic>.from(item['payload'] as Map<String, dynamic>)
              : Map<String, dynamic>.from(item))
          .toList(),
    );
  }

  int? _asInt(dynamic value) {
    if (value is int) {
      return value;
    }
    return int.tryParse(value?.toString() ?? '');
  }

  Map<String, dynamic>? findFinancialYearById(int? id) {
    if (id == null) {
      return null;
    }
    for (final item in financialYears) {
      if (_asInt(item['id']) == id) {
        return item;
      }
    }
    return null;
  }

  String formatFinancialYearStart(int? id) {
    final item = findFinancialYearById(id) ??
        (currentFinancialYear.isEmpty ? null : currentFinancialYear);
    return AppDateFormatter.formatDisplay(item?['start_date']);
  }

  String formatFinancialYearEnd(int? id) {
    final item = findFinancialYearById(id) ??
        (currentFinancialYear.isEmpty ? null : currentFinancialYear);
    return AppDateFormatter.formatDisplay(item?['end_date']);
  }
}
