import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../services/local_storage_service.dart';

class ThemeController extends GetxController {
  ThemeMode _themeMode = ThemeMode.light;

  ThemeMode get themeMode => _themeMode;

  bool get isDarkMode => _themeMode == ThemeMode.dark;

  @override
  void onInit() {
    super.onInit();
    if (Get.isRegistered<LocalStorageService>()) {
      final stored = Get.find<LocalStorageService>().savedThemeMode;
      _themeMode = stored == 'dark' ? ThemeMode.dark : ThemeMode.light;
    }
  }

  Future<void> toggleTheme() async {
    _themeMode = isDarkMode ? ThemeMode.light : ThemeMode.dark;
    update();
    if (Get.isRegistered<LocalStorageService>()) {
      await Get.find<LocalStorageService>().saveThemeMode(
        isDarkMode ? 'dark' : 'light',
      );
    }
  }
}
