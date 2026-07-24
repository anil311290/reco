import '../../../core/config/api_endpoints.dart';
import 'base_report_controller.dart';

class CreditorsOutstandingReportController extends BaseReportController {
  CreditorsOutstandingReportController(super.repository, super.networkMonitorService);

  @override
  String get endpoint => ApiEndpoints.reportsCreditorsOutstanding;

  @override
  Map<String, dynamic> get queryParameters => const <String, dynamic>{};

  @override
  void onInit() {
    super.onInit();
    loadReport();
  }
}
