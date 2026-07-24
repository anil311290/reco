import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import 'base_report_controller.dart';

class TrialBalanceReportController extends BaseReportController {
  TrialBalanceReportController(super.repository, super.networkMonitorService);

  final financialYearId = RxnInt();

  @override
  String get endpoint => ApiEndpoints.reportsTrialBalance;

  @override
  Map<String, dynamic> get queryParameters => <String, dynamic>{
        if (financialYearId.value != null) 'financial_year_id': financialYearId.value,
      };

  @override
  void onInit() {
    super.onInit();
    loadReport();
  }
}
