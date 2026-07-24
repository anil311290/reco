import '../../../core/config/api_endpoints.dart';
import '../../../core/network/api_client.dart';

class SecurityRepository {
  SecurityRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<Map<String, dynamic>> fetchSettings() async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.securitySettings,
    );
    return (response.data?['data'] ?? <String, dynamic>{})
        as Map<String, dynamic>;
  }

  Future<void> updateSettings(Map<String, dynamic> payload) async {
    await _apiClient.put<void>(
      ApiEndpoints.securitySettings,
      data: payload,
    );
  }

  Future<void> toggleAppLock(bool enabled) async {
    await _apiClient.put<void>(
      ApiEndpoints.securityAppLock,
      data: <String, dynamic>{'enabled': enabled},
    );
  }

  Future<void> setPin({
    required String pin,
    required String confirmPin,
  }) async {
    await _apiClient.post<void>(
      ApiEndpoints.pinSet,
      data: <String, dynamic>{
        'pin': pin,
        'pin_confirmation': confirmPin,
      },
    );
  }
}

