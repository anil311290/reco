import '../../../core/config/api_endpoints.dart';
import 'base_report_controller.dart';
import 'outstanding_report_filters.dart';

class AgingSummaryReportController extends BaseReportController
    with OutstandingReportFiltersMixin {
  AgingSummaryReportController(
    super.repository,
    super.networkMonitorService,
  );

  @override
  String get endpoint => ApiEndpoints.reportsAgingSummary;

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

  Map<String, dynamic> get summary {
    final data = reportData['data'];
    if (data is Map && data['summary'] is Map) {
      return Map<String, dynamic>.from(data['summary'] as Map);
    }
    return <String, dynamic>{};
  }

  List<Map<String, dynamic>> get rows {
    final data = reportData['data'];
    if (data is! Map || data['rows'] is! List) {
      return <Map<String, dynamic>>[];
    }
    return (data['rows'] as List)
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  @override
  void onClose() {
    disposeOutstandingFilters();
    super.onClose();
  }
}
