import 'package:get/get.dart';

import '../controllers/splash/splash_controller.dart';

class SplashBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<SplashController>(
      () => SplashController(Get.find(), Get.find(), Get.find()),
      fenix: true,
    );
  }
}
