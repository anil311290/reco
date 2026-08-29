class AppConfig {
  AppConfig._();

  static const String appName = 'Reco ERP';

  static const String baseUrl = 'https://betatesting.sahrudaya.online/api/v1';
  static const String origin = 'https://betatesting.sahrudaya.online';
  // static const String baseUrl = 'https://reco.sahrudaya.online/api/v1';
  // static const String origin = 'https://reco.sahrudaya.online';
  static const Duration connectTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(seconds: 30);
}
