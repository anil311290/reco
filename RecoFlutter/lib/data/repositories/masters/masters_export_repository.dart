import '../../../core/config/api_endpoints.dart';
import '../../../core/network/api_client.dart';

class MastersExportRepository {
  MastersExportRepository(this._apiClient);

  final ApiClient _apiClient;

  /// Download Excel export from server. Returns raw API response JSON.
  Future<Map<String, dynamic>> exportExcel({
    required String type,
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.exportMasterExcel(type),
      queryParameters: queryParameters,
    );
    return response.data ?? <String, dynamic>{};
  }

  /// Download PDF export from server. Returns raw API response JSON.
  Future<Map<String, dynamic>> exportPdf({
    required String type,
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.exportMasterPdf(type),
      queryParameters: queryParameters,
    );
    return response.data ?? <String, dynamic>{};
  }
}
