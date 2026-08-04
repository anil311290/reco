import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/utils/app_date_formatter.dart';
import 'base_report_controller.dart';
import 'report_lookup_controller.dart';

class LedgerReportController extends BaseReportController {
  LedgerReportController(super.repository, super.networkMonitorService);

  final fromDateController = TextEditingController();
  final toDateController = TextEditingController();
  final financialYearId = RxnInt();
  final accountId = RxnInt();
  final entries = <Map<String, dynamic>>[].obs;
  final isLoadingEntries = false.obs;
  final isLoadingMoreEntries = false.obs;
  final entriesCurrentPage = 1.obs;
  final entriesLastPage = 1.obs;
  final entriesTotal = 0.obs;

  static const int entriesPageSize = 20;

  bool get hasMoreEntries => entriesCurrentPage.value < entriesLastPage.value;

  @override
  String get endpoint => ApiEndpoints.reportsLedger;

  @override
  Map<String, dynamic> get queryParameters => <String, dynamic>{
        if (accountId.value != null) 'account_id': accountId.value,
        if (financialYearId.value != null) 'financial_year_id': financialYearId.value,
        if (fromDateController.text.isNotEmpty)
          'date_from': AppDateFormatter.toApiDate(fromDateController.text),
        if (toDateController.text.isNotEmpty)
          'date_to': AppDateFormatter.toApiDate(toDateController.text),
      };

  @override
  void onInit() {
    super.onInit();
    _initialize();
  }

  Future<void> _initialize() async {
    final lookup = Get.find<ReportLookupController>();
    if (lookup.financialYears.isEmpty || lookup.currentFinancialYearId.value == null) {
      await lookup.preload();
    }
    applyFinancialYear(lookup.currentFinancialYearId.value, lookup);
    if (lookup.ledgerAccounts.isEmpty) {
      await lookup.loadLedgerAccounts();
    }
    if (accountId.value != null) {
      await loadReport();
      return;
    }
    hasLoadedOnce.value = true;
    isLoading.value = false;
  }

  void applyFinancialYear(int? value, ReportLookupController lookup) {
    financialYearId.value = value;
    fromDateController.text = lookup.formatFinancialYearStart(value);
    toDateController.text = lookup.formatFinancialYearEnd(value);
  }

  @override
  Future<void> loadReport() async {
    await super.loadReport();
    if (_applyEntriesFromReportData()) {
      isLoadingEntries.value = false;
      isLoadingMoreEntries.value = false;
      return;
    }
    await loadEntries(reset: true);
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

  Future<void> loadEntries({required bool reset}) async {
    final selectedAccountId = accountId.value;
    if (selectedAccountId == null) {
      entries.clear();
      entriesCurrentPage.value = 1;
      entriesLastPage.value = 1;
      entriesTotal.value = 0;
      return;
    }

    final targetPage = reset ? 1 : (entriesCurrentPage.value + 1);
    if (reset) {
      isLoadingEntries.value = true;
      entriesCurrentPage.value = 1;
    } else {
      if (isLoadingMoreEntries.value || !hasMoreEntries) {
        return;
      }
      isLoadingMoreEntries.value = true;
    }

    try {
      final endpoint = ApiEndpoints.ledgerEntries(selectedAccountId);
      final result = reset
          ? await repository.getPaginatedList(
              endpoint,
              queryParameters: _entryQueryParameters,
              page: targetPage,
              perPage: entriesPageSize,
            )
          : await (await networkMonitorService.hasInternetNow()
              ? repository.refreshPaginatedList(
                  endpoint,
                  queryParameters: _entryQueryParameters,
                  page: targetPage,
                  perPage: entriesPageSize,
                )
              : repository.getPaginatedList(
                  endpoint,
                  queryParameters: _entryQueryParameters,
                  page: targetPage,
                  perPage: entriesPageSize,
                ));

      _applyEntryPage(result, reset: reset);

      if (reset && await networkMonitorService.hasInternetNow()) {
        final fresh = await repository.refreshPaginatedList(
          endpoint,
          queryParameters: _entryQueryParameters,
          page: 1,
          perPage: entriesPageSize,
        );
        _applyEntryPage(fresh, reset: true);
      }
    } finally {
      isLoadingEntries.value = false;
      isLoadingMoreEntries.value = false;
    }
  }

  bool _applyEntriesFromReportData() {
    final data = reportData['data'];
    if (data is! Map<String, dynamic>) {
      return false;
    }
    final rawEntries = data['entries'];
    if (rawEntries is! List) {
      return false;
    }

    final mapped = rawEntries
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList(growable: false);

    entries.assignAll(
      _mergeEntries(
        mapped,
        base: const <Map<String, dynamic>>[],
      ),
    );
    entriesCurrentPage.value = 1;
    entriesLastPage.value = 1;
    entriesTotal.value = mapped.length;
    return true;
  }

  void _applyEntryPage(dynamic result, {required bool reset}) {
    entriesCurrentPage.value = result.currentPage;
    entriesLastPage.value = result.lastPage;
    entriesTotal.value = result.total;
    final mapped = List<Map<String, dynamic>>.from(
      (result.items as List)
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item)),
    );
    if (reset) {
      entries.assignAll(
        _mergeEntries(
          mapped,
          base: const <Map<String, dynamic>>[],
        ),
      );
    } else {
      entries.assignAll(
        _mergeEntries(
          mapped,
          base: entries.toList(growable: false),
        ),
      );
    }
  }

  List<Map<String, dynamic>> _mergeEntries(
    List<Map<String, dynamic>> incoming, {
    required List<Map<String, dynamic>> base,
  }) {
    final merged = <String, Map<String, dynamic>>{};
    for (final item in base) {
      merged[_entryKey(item)] = item;
    }
    for (final item in incoming) {
      merged[_entryKey(item)] = item;
    }
    return merged.values.toList();
  }

  String _entryKey(Map<String, dynamic> item) {
    final id = item['id']?.toString();
    if (id != null && id.isNotEmpty) {
      return 'id:$id';
    }
    final voucherId = item['voucher_id']?.toString() ?? '';
    final date = item['transaction_date']?.toString() ?? '';
    final balance = item['running_balance']?.toString() ?? '';
    return 'entry:$voucherId:$date:$balance';
  }

  Map<String, dynamic> get _entryQueryParameters => <String, dynamic>{
        if (fromDateController.text.isNotEmpty)
          'date_from': AppDateFormatter.toApiDate(fromDateController.text),
        if (toDateController.text.isNotEmpty)
          'date_to': AppDateFormatter.toApiDate(toDateController.text),
      };

  @override
  void onClose() {
    fromDateController.dispose();
    toDateController.dispose();
    super.onClose();
  }
}
