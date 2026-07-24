import '../../../core/config/api_endpoints.dart';
import '../../../core/network/api_client.dart';
import '../../models/auth/login_request_model.dart';
import '../../models/auth/login_response_model.dart';
import '../../models/auth/register_request_model.dart';

class AuthRepository {
  AuthRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<LoginResponseModel> login(LoginRequestModel request) async {
    final response = await _apiClient.post<Map<String, dynamic>>(
      ApiEndpoints.login,
      data: request.toJson(),
    );

    return LoginResponseModel.fromJson(response.data ?? <String, dynamic>{});
  }

  Future<void> logout() async {
    await _apiClient.post<void>(ApiEndpoints.logout);
  }

  Future<Map<String, dynamic>> fetchProfile() async {
    final response = await _apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.me,
    );
    return (response.data?['data'] ?? <String, dynamic>{})
        as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> register(RegisterRequestModel request) async {
    final response = await _apiClient.post<Map<String, dynamic>>(
      ApiEndpoints.register,
      data: request.toJson(),
    );

    return (response.data?['data'] ?? <String, dynamic>{})
        as Map<String, dynamic>;
  }
}
