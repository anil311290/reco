import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_snackbar.dart';
import '../../../data/repositories/settings/security_repository.dart';

class SecuritySettingsController extends GetxController {
  SecuritySettingsController(this._repository);

  final SecurityRepository _repository;

  final isLoading = false.obs;
  final isSaving = false.obs;
  final hasPin = false.obs;
  final appLockEnabled = false.obs;
  final biometricEnabled = false.obs;
  final autoLockTimeout = 5.obs;

  final pinController = TextEditingController();
  final confirmPinController = TextEditingController();

  @override
  void onInit() {
    super.onInit();
    loadSettings();
  }

  @override
  void onClose() {
    pinController.dispose();
    confirmPinController.dispose();
    super.onClose();
  }

  Future<void> loadSettings() async {
    isLoading.value = true;
    try {
      final data = await _repository.fetchSettings();
      hasPin.value = data['has_pin'] == true;
      appLockEnabled.value = data['app_lock_enabled'] == true;
      biometricEnabled.value = data['biometric_enabled'] == true;
      autoLockTimeout.value =
          int.tryParse(data['auto_lock_timeout']?.toString() ?? '5') ?? 5;
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> saveSecurity() async {
    isSaving.value = true;
    try {
      await _repository.toggleAppLock(appLockEnabled.value);
      await _repository.updateSettings(<String, dynamic>{
        'biometric_enabled': biometricEnabled.value,
        'auto_lock_timeout': autoLockTimeout.value,
      });
      if (pinController.text.trim().isNotEmpty) {
        await _repository.setPin(
          pin: pinController.text.trim(),
          confirmPin: confirmPinController.text.trim(),
        );
        hasPin.value = true;
        pinController.clear();
        confirmPinController.clear();
      }
      AppSnackbar.success('Security settings updated successfully.');
    } finally {
      isSaving.value = false;
    }
  }
}

