import 'package:get/get.dart';

import '../controllers/auth/register_controller.dart';

class RegisterBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<RegisterController>(
      () => RegisterController(Get.find()),
      fenix: true,
    );
  }
}
