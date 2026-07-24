class LoginResponseModel {
  LoginResponseModel({required this.token, required this.user});

  final String token;
  final Map<String, dynamic> user;

  factory LoginResponseModel.fromJson(Map<String, dynamic> json) {
    final data = (json['data'] ?? <String, dynamic>{}) as Map<String, dynamic>;
    return LoginResponseModel(
      token: data['token']?.toString() ?? '',
      user: (data['user'] ?? <String, dynamic>{}) as Map<String, dynamic>,
    );
  }
}
