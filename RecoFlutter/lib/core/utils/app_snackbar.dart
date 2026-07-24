import 'package:flutter/material.dart';
import 'package:get/get.dart';

class AppSnackbar {
  AppSnackbar._();

  static void success(String message) {
    _show(message, 'Success');
  }

  static void error(String message) {
    _show(message, 'Error');
  }

  static void _show(String message, String title) {
    if (!Get.isSnackbarOpen) {
      Get.snackbar(
        title,
        message,
        snackPosition: SnackPosition.BOTTOM,
        margin: const EdgeInsets.all(16),
        backgroundColor: Get.theme.colorScheme.surface,
        colorText: Get.theme.colorScheme.onSurface,
      );
    }
  }
}
