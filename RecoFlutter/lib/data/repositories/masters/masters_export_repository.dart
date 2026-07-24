import '../../../core/config/api_endpoints.dart';
import '../../../core/network/api_client.dart';
import 'package:dio/dio.dart';

class MastersExportRepository {
  MastersExportRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<Map<String, dynamic>> exportExcel({
    required String type,
    Map<String, dynamic>? queryParameters,
  }) async {
    return _requestWithFallback(
      endpoints: _excelEndpoints(type),
      queryParameters: queryParameters,
    );
  }

  Future<Map<String, dynamic>> exportPdf({
    required String type,
    Map<String, dynamic>? queryParameters,
  }) async {
    return _requestWithFallback(
      endpoints: _pdfEndpoints(type),
      queryParameters: queryParameters,
    );
  }

  Future<Map<String, dynamic>> _requestWithFallback({
    required List<String> endpoints,
    Map<String, dynamic>? queryParameters,
  }) async {
    DioException? lastError;

    for (final endpoint in endpoints) {
      try {
        final response = await _apiClient.get<Map<String, dynamic>>(
          endpoint,
          queryParameters: queryParameters,
          options: Options(extra: <String, dynamic>{'silentError': true}),
        );
        return response.data ?? <String, dynamic>{};
      } on DioException catch (error) {
        lastError = error;
        if (error.response?.statusCode != 404) {
          rethrow;
        }
      }
    }

    if (lastError != null) {
      throw lastError;
    }

    return <String, dynamic>{};
  }

  List<String> _excelEndpoints(String type) {
    final legacyType = _legacyType(type);
    return <String>[
      ApiEndpoints.exportMasterExcel(type),
      '/$legacyType/export/excel',
      '/export/$legacyType/excel',
    ];
  }

  List<String> _pdfEndpoints(String type) {
    final legacyType = _legacyType(type);
    return <String>[
      ApiEndpoints.exportMasterPdf(type),
      '/$legacyType/export/pdf',
      '/export/$legacyType/pdf',
    ];
  }

  String _legacyType(String type) {
    switch (type) {
      case 'item-categories':
        return 'item-categories';
      case 'tax-rates':
        return 'tax-rates';
      default:
        return type;
    }
  }
}
