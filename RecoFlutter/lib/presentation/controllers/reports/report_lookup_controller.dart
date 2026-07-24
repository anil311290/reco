import 'dart:async';

import 'package:get/get.dart';

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
  final ledgerAccounts = <Map<String, dynamic>>[].obs;
  final cashAccounts = <Map<String, dynamic>>[].obs;
  final bankAccounts = <Map<String, dynamic>>[].obs;

  @override
  void onInit() {
    super.onInit();
    unawaited(preload());
  }

  Future<void> preload() async {
    await Future.wait(<Future<void>>[
      loadFinancialYears(),
      loadLedgerAccounts(),
      loadCashBankAccounts(),
    ]);
  }

  Future<void> loadFinancialYears() async {
    financialYears.assignAll(await _reportsRepository.getFinancialYears());
    final current = await _reportsRepository.getCurrentFinancialYear();
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

  Future<void> loadCashBankAccounts() async {
    cashAccounts.assignAll(await _accountsRepository.getCashBankAccounts(mode: 'cash'));
    bankAccounts.assignAll(await _accountsRepository.getCashBankAccounts(mode: 'bank'));
  }

  int? _asInt(dynamic value) {
    if (value is int) {
      return value;
    }
    return int.tryParse(value?.toString() ?? '');
  }
}
