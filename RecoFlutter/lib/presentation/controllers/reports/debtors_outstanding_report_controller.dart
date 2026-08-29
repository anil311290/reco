import '../../../core/config/api_endpoints.dart';
import 'base_report_controller.dart';
import 'outstanding_report_filters.dart';

class DebtorsOutstandingReportController extends BaseReportController
    with OutstandingReportFiltersMixin {
  DebtorsOutstandingReportController(
    super.repository,
    super.networkMonitorService,
  );

  @override
  String get endpoint => ApiEndpoints.reportsDebtorsOutstanding;

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
