import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:get/get.dart';

import '../bindings/initial_binding.dart';
import '../theme/app_theme.dart';
import '../theme/theme_controller.dart';
import '../../presentation/views/splash/splash_screen.dart';

class RecoApp extends StatelessWidget {
  const RecoApp({super.key});

  @override
  Widget build(BuildContext context) {
    return GetBuilder<ThemeController>(
      builder: (themeController) {
        return ScreenUtilInit(
          designSize: const Size(390, 844),
          minTextAdapt: true,
          splitScreenMode: true,
          builder: (_, child) {
            return GetMaterialApp(
              debugShowCheckedModeBanner: false,
              title: 'Reco ERP',
              initialBinding: InitialBinding(),
              theme: AppTheme.light(),
              darkTheme: AppTheme.dark(),
              themeMode: AppTheme.mode.value,
              home: const SplashScreen(),
            );
          },
        );
      },
    );
  }
}
