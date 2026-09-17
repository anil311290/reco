import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_date_formatter.dart';
import 'report_lookup_controller.dart';

/// Shared aging / outstanding query filters used by debtors, creditors, and aging summary.
mixin OutstandingReportFiltersMixin on GetxController {
  final asOfDateController = TextEditingController();
  final ageMinController = TextEditingController();
  final ageMaxController = TextEditingController();
  final financialYearId = RxnInt();
  final overdueStatus = 'all'.obs;
  final ageBucket = 'all'.obs;
  final basis = 'due'.obs;

  static const overdueStatusOptions = <MapEntry<String, String>>[
    MapEntry('all', 'All'),
    MapEntry('due', 'Due'),
    MapEntry('not_due', 'Not Due'),
  ];

  static const ageBucketOptions = <MapEntry<String, String>>[
    MapEntry('all', 'All Buckets'),
    MapEntry('current', 'Current'),
    MapEntry('1_30', '1-30 Days'),
    MapEntry('31_60', '31-60 Days'),
    MapEntry('61_90', '61-90 Days'),
    MapEntry('91_plus', '91+ Days'),
    MapEntry('custom', 'Custom Range'),
  ];

  static const basisOptions = <MapEntry<String, String>>[
    MapEntry('due', 'Due Days'),
    MapEntry('billed', 'Billed Days'),
  ];

  Map<String, dynamic> get outstandingQueryParameters => <String, dynamic>{
        if (financialYearId.value != null)
          'financial_year_id': financialYearId.value,
        if (asOfDateController.text.isNotEmpty)
          'as_of_date': AppDateFormatter.toApiDate(asOfDateController.text),
        if (overdueStatus.value.isNotEmpty && overdueStatus.value != 'all')
          'overdue_status': overdueStatus.value,
        if (ageBucket.value.isNotEmpty && ageBucket.value != 'all')
          'age_bucket': ageBucket.value,
        if (basis.value.isNotEmpty) 'basis': basis.value,
        if (ageBucket.value == 'custom' && ageMinController.text.isNotEmpty)
          'age_min': int.tryParse(ageMinController.text.trim()),
        if (ageBucket.value == 'custom' && ageMaxController.text.isNotEmpty)
          'age_max': int.tryParse(ageMaxController.text.trim()),
      };

  Future<void> initializeOutstandingDefaults() async {
    final lookup = Get.find<ReportLookupController>();
    if (lookup.financialYears.isEmpty ||
        lookup.currentFinancialYearId.value == null) {
      await lookup.preload();
    }
    financialYearId.value = lookup.currentFinancialYearId.value;
    lookup.applyAsOfToday(asOfDateController);
    overdueStatus.value = 'all';
    ageBucket.value = 'all';
    basis.value = 'due';
  }

  void applyFinancialYear(int? value, ReportLookupController lookup) {
    financialYearId.value = value;
  }

  void disposeOutstandingFilters() {
    asOfDateController.dispose();
    ageMinController.dispose();
    ageMaxController.dispose();
  }

  String ageBucketLabel(String? bucket) {
    switch (bucket) {
      case 'current':
        return 'Current';
      case '1_30':
        return '1-30';
      case '31_60':
        return '31-60';
      case '61_90':
        return '61-90';
      case '91_plus':
        return '91+';
      default:
        return bucket?.isNotEmpty == true ? bucket! : '-';
    }
  }
}
