class LoginRequestModel {
  LoginRequestModel({
    required this.email,
    required this.password,
    this.deviceName = 'android',
    this.deviceType = 'android',
    this.deviceId = 'reco-flutter-device',
  });

  final String email;
  final String password;
  final String deviceName;
  final String deviceType;
  final String deviceId;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'email': email,
      'password': password,
      'device_name': deviceName,
      'device_type': deviceType,
      'device_id': deviceId,
    };
  }
}
