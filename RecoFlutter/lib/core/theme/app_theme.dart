import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:get_storage/get_storage.dart';

import 'app_colors.dart';

class AppTheme {
  static const _themeKey = 'is_dark_mode';
  static const _primaryColorKey = 'primary_color';
  static final _box = GetStorage();
  static final Rx<ThemeMode> mode =
      ((_box.read(_themeKey) ?? false) ? ThemeMode.dark : ThemeMode.light).obs;
  static final Rx<Color> primaryColor = Color(
    (_box.read(_primaryColorKey) ?? AppColors.primary.toARGB32()) as int,
  ).obs;

  static bool get isDark => mode.value == ThemeMode.dark;

  static void toggle() {
    final nextIsDark = !isDark;
    mode.value = nextIsDark ? ThemeMode.dark : ThemeMode.light;
    _box.write(_themeKey, nextIsDark);
    Get.changeThemeMode(mode.value);
  }

  static void updatePrimaryColor(Color color) {
    primaryColor.value = color;
    _box.write(_primaryColorKey, color.toARGB32());
    Get.changeTheme(light());
    Get.changeThemeMode(mode.value);
  }

  static ThemeData light() {
    final scheme = ColorScheme.fromSeed(
      seedColor: primaryColor.value,
      primary: primaryColor.value,
      surface: AppColors.background,
      brightness: Brightness.light,
    );
    return _base(scheme).copyWith(
      scaffoldBackgroundColor: AppColors.background,
      cardColor: AppColors.card,
    );
  }

  static ThemeData dark() {
    final scheme = ColorScheme.fromSeed(
      seedColor: primaryColor.value,
      primary: primaryColor.value,
      brightness: Brightness.dark,
      surface: const Color(0xFF0F172A),
    );
    return _base(scheme).copyWith(
      scaffoldBackgroundColor: const Color(0xFF0F172A),
      cardColor: const Color(0xFF172033),
    );
  }

  static ThemeData _base(ColorScheme scheme) {
    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      fontFamily: 'Roboto',
      appBarTheme: AppBarTheme(
        elevation: 0,
        centerTitle: false,
        scrolledUnderElevation: 0,
        backgroundColor: scheme.surface,
        foregroundColor: scheme.onSurface,
        titleTextStyle: TextStyle(
          color: scheme.onSurface,
          fontSize: 18,
          fontWeight: FontWeight.w700,
        ),
      ),
      textTheme: const TextTheme(
        headlineSmall: TextStyle(fontSize: 24, fontWeight: FontWeight.w800),
        titleLarge: TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
        titleMedium: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
        bodyMedium: TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
        bodySmall: TextStyle(fontSize: 12, fontWeight: FontWeight.w500),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: scheme.brightness == Brightness.dark
            ? const Color(0xFF111827)
            : Colors.white,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 14,
          vertical: 13,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: BorderSide(color: scheme.outlineVariant),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: BorderSide(color: scheme.outlineVariant),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: BorderSide(color: scheme.primary, width: 1.2),
        ),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
      floatingActionButtonTheme: FloatingActionButtonThemeData(
        backgroundColor: scheme.primary,
        foregroundColor: scheme.onPrimary,
      ),
      bottomNavigationBarTheme: BottomNavigationBarThemeData(
        type: BottomNavigationBarType.fixed,
        selectedItemColor: scheme.primary,
        unselectedItemColor: AppColors.muted,
        selectedLabelStyle: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
        ),
        unselectedLabelStyle: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}
