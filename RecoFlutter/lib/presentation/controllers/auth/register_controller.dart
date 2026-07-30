import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_snackbar.dart';
import '../../../data/models/auth/register_request_model.dart';
import '../../../data/repositories/auth/auth_repository.dart';
import '../../bindings/login_binding.dart';
import '../../views/auth/login_screen.dart';

class RegisterController extends GetxController {
  RegisterController(this._authRepository);

  final AuthRepository _authRepository;

  final formKey = GlobalKey<FormState>();
  final nameController = TextEditingController();
  final emailController = TextEditingController();
  final phoneController = TextEditingController();
  final companyNameController = TextEditingController();
  final passwordController = TextEditingController();
  final confirmPasswordController = TextEditingController();

  final isLoading = false.obs;
  final obscurePassword = true.obs;
  final obscureConfirmPassword = true.obs;

  @override
  void onClose() {
    nameController.dispose();
    emailController.dispose();
    phoneController.dispose();
    companyNameController.dispose();
    passwordController.dispose();
    confirmPasswordController.dispose();
    super.onClose();
  }

  void togglePasswordVisibility() {
    obscurePassword.value = !obscurePassword.value;
  }

  void toggleConfirmPasswordVisibility() {
    obscureConfirmPassword.value = !obscureConfirmPassword.value;
  }

  Future<void> register() async {
    if (!(formKey.currentState?.validate() ?? false)) {
      return;
    }

    isLoading.value = true;
    try {
      await _authRepository.register(
        RegisterRequestModel(
          name: nameController.text.trim(),
          email: emailController.text.trim(),
          phone: phoneController.text.trim().isEmpty
              ? null
              : phoneController.text.trim(),
          companyName: companyNameController.text.trim(),
          password: passwordController.text,
          passwordConfirmation: confirmPasswordController.text,
        ),
      );

      AppSnackbar.success(
        'Registration submitted successfully. Please wait for approval before login.',
      );
      Get.off(() => const LoginScreen(), binding: LoginBinding());
    } finally {
      isLoading.value = false;
    }
  }
}
