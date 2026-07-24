import '../../../core/config/api_endpoints.dart';
import '../../../core/network/api_client.dart';

class SettingsRepository {
  SettingsRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<Map<String, dynamic>> fetchSettings() async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.settings,
    );
    return (response.data?['data'] ?? <String, dynamic>{})
        as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> fetchCompanySettings() async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.settingsCompany,
    );
    return (response.data?['data'] ?? <String, dynamic>{})
        as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> fetchThemeSettings() async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.settingsTheme,
    );
    return (response.data?['data'] ?? <String, dynamic>{})
        as Map<String, dynamic>;
  }

  Future<List<Map<String, dynamic>>> fetchFinancialYears() async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.settingsFinancialYears,
    );
    final data = response.data?['data'];
    if (data is List) {
      return data
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }
    return <Map<String, dynamic>>[];
  }

  Future<Map<String, dynamic>?> fetchCurrentFinancialYear() async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.settingsCurrentFinancialYear,
    );
    final data = response.data?['data'];
    if (data is Map<String, dynamic>) {
      return data;
    }
    return null;
  }

  Future<void> updateCompanySettings(Map<String, dynamic> payload) async {
    await _apiClient.put<void>(
      ApiEndpoints.settingsCompany,
      data: payload,
    );
  }

  Future<void> updateAccountingSettings(Map<String, dynamic> payload) async {
    await _apiClient.put<void>(
      ApiEndpoints.settingsAccounting,
      data: payload,
    );
  }

  Future<void> updateThemeSettings(Map<String, dynamic> payload) async {
    await _apiClient.put<void>(
      ApiEndpoints.themes,
      data: payload,
    );
  }
}

