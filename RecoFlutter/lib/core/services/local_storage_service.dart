import 'package:get_storage/get_storage.dart';

import '../config/storage_keys.dart';

class LocalStorageService {
  final GetStorage _box = GetStorage();

  String? get token => _box.read<String>(StorageKeys.token);
  Map<String, dynamic>? get user =>
      _box.read<Map<String, dynamic>>(StorageKeys.user);
  bool get hasActiveSession =>
      (token?.isNotEmpty ?? false) && (user?.isNotEmpty ?? false);
  String get savedThemeMode =>
      _box.read<String>(StorageKeys.themeMode) ?? 'light';

  Future<void> saveToken(String token) => _box.write(StorageKeys.token, token);

  Future<void> saveUser(Map<String, dynamic> user) =>
      _box.write(StorageKeys.user, user);

  Future<void> saveThemeMode(String mode) =>
      _box.write(StorageKeys.themeMode, mode);

  Future<void> clearSession() async {
    await _box.remove(StorageKeys.token);
    await _box.remove(StorageKeys.user);
  }
}
