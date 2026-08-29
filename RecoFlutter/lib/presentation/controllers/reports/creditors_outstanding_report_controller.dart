import '../../../core/config/api_endpoints.dart';
import 'base_report_controller.dart';
import 'outstanding_report_filters.dart';

class CreditorsOutstandingReportController extends BaseReportController
    with OutstandingReportFiltersMixin {
  CreditorsOutstandingReportController(
    super.repository,
    super.networkMonitorService,
  );

  @override
  String get endpoint => ApiEndpoints.reportsCreditorsOutstanding;

  @override
  Map<String, dynamic> get queryParameters => outstandingQueryParameters;

  @override
  void onInit() {
    super.onInit();
    _initializeDefaults();
  }

  Future<void> _initializeDefaults() async {
    await initializeOutstandingDefaults();
    await loadReport();
  }

  @override
  void onClose() {
    disposeOutstandingFilters();
    super.onClose();
  }
}
