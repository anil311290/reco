import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/services/local_storage_service.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/models/auth/login_request_model.dart';
import '../../../data/repositories/auth/auth_repository.dart';
import '../../bindings/main_binding.dart';
import '../../bindings/register_binding.dart';
import '../../views/auth/forgot_password_screen.dart';
import '../../views/auth/register_screen.dart';
import '../../views/main/main_screen.dart';
import 'forgot_password_controller.dart';

class LoginController extends GetxController {
  LoginController(this._authRepository);

  final AuthRepository _authRepository;
  final formKey = GlobalKey<FormState>();
  final emailController = TextEditingController();
  final passwordController = TextEditingController();
  final isLoading = false.obs;
  final isPasswordHidden = true.obs;

  @override
  void onClose() {
    emailController.dispose();
    passwordController.dispose();
    super.onClose();
  }

  void togglePasswordVisibility() {
    isPasswordHidden.value = !isPasswordHidden.value;
  }

  void openRegister() {
    Get.to(() => const RegisterScreen(), binding: RegisterBinding());
  }

  void openForgotPassword() {
    Get.to(
      () => const ForgotPasswordScreen(),
      binding: BindingsBuilder(() {
        Get.put(ForgotPasswordController(Get.find<AuthRepository>()));
      }),
    );
  }

  Future<void> login() async {
    if (!(formKey.currentState?.validate() ?? false)) {
      return;
    }

    isLoading.value = true;
    try {
      final response = await _authRepository.login(
        LoginRequestModel(
          email: emailController.text.trim(),
          password: passwordController.text,
        ),
      );

      await Get.find<LocalStorageService>().saveToken(response.token);
      final profile = await _authRepository.fetchProfile();
      await Get.find<LocalStorageService>().saveUser(
        profile.isNotEmpty ? profile : response.user,
      );

      AppSnackbar.success('Login successful');
      Get.offAll(() => const MainScreen(), binding: MainBinding());
    } finally {
      isLoading.value = false;
    }
  }
}
