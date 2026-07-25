import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_snackbar.dart';
import '../../../data/repositories/auth/auth_repository.dart';

class ForgotPasswordController extends GetxController {
  ForgotPasswordController(this._authRepository);

  final AuthRepository _authRepository;

  final formKey = GlobalKey<FormState>();
  final emailController = TextEditingController();
  final tokenController = TextEditingController();
  final passwordController = TextEditingController();
  final confirmPasswordController = TextEditingController();

  final isLoading = false.obs;
  final isPasswordHidden = true.obs;
  final isConfirmHidden = true.obs;
  final emailSent = false.obs;
  final resetDone = false.obs;

  @override
  void onClose() {
    emailController.dispose();
    tokenController.dispose();
    passwordController.dispose();
    confirmPasswordController.dispose();
    super.onClose();
  }

  void togglePasswordVisibility() {
    isPasswordHidden.value = !isPasswordHidden.value;
  }

  void toggleConfirmVisibility() {
    isConfirmHidden.value = !isConfirmHidden.value;
  }

  Future<void> sendResetLink() async {
    if (!(formKey.currentState?.validate() ?? false)) return;

    isLoading.value = true;
    try {
      final message = await _authRepository.forgotPassword(
        emailController.text.trim(),
      );
      emailSent.value = true;
      AppSnackbar.success(message);
    } catch (e) {
      AppSnackbar.error('Failed to send reset link. Please try again.');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> resetPassword() async {
    if (!(formKey.currentState?.validate() ?? false)) return;

    isLoading.value = true;
    try {
      final message = await _authRepository.resetPassword(
        email: emailController.text.trim(),
        token: tokenController.text.trim(),
        password: passwordController.text,
        passwordConfirmation: confirmPasswordController.text,
      );
      resetDone.value = true;
      AppSnackbar.success(message);
    } catch (e) {
      AppSnackbar.error('Failed to reset password. Invalid or expired token.');
    } finally {
      isLoading.value = false;
    }
  }

  void goBackToLogin() {
    Get.back();
  }
}
