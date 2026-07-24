import 'package:get/get.dart';

import '../../../core/services/local_storage_service.dart';
import '../../../core/services/network_monitor_service.dart';
import '../../../data/repositories/auth/auth_repository.dart';
import '../../bindings/login_binding.dart';
import '../../bindings/main_binding.dart';
import '../../views/auth/login_screen.dart';
import '../../views/main/main_screen.dart';

class SplashController extends GetxController {
  SplashController(
    this._localStorageService,
    this._authRepository,
    this._networkMonitorService,
  );

  final LocalStorageService _localStorageService;
  final AuthRepository _authRepository;
  final NetworkMonitorService _networkMonitorService;
  bool _didNavigate = false;

  @override
  void onReady() {
    super.onReady();
    Future<void>.delayed(const Duration(milliseconds: 900), _bootstrapSession);
  }

  Future<void> _bootstrapSession() async {
    if (_didNavigate) {
      return;
    }

    final token = _localStorageService.token;
    final user = _localStorageService.user;

    if (token == null || token.isEmpty) {
      _openLogin();
      return;
    }

    final hasInternet = await _networkMonitorService.hasInternetNow().timeout(
      const Duration(seconds: 4),
      onTimeout: () => false,
    );

    if (!hasInternet) {
      if (user != null && user.isNotEmpty) {
        _openMain();
      } else {
        await _localStorageService.clearSession();
        _openLogin();
      }
      return;
    }

    try {
      final profile = await _authRepository
          .fetchProfile()
          .timeout(const Duration(seconds: 8));
      if (profile.isNotEmpty) {
        await _localStorageService.saveUser(profile);
      }
      _openMain();
      return;
    } catch (_) {
      await _localStorageService.clearSession();
    }

    _openLogin();
  }

  void _openLogin() {
    if (_didNavigate) {
      return;
    }
    _didNavigate = true;
    Get.offAll(() => const LoginScreen(), binding: LoginBinding());
  }

  void _openMain() {
    if (_didNavigate) {
      return;
    }
    _didNavigate = true;
    Get.offAll(() => const MainScreen(), binding: MainBinding());
  }
}
