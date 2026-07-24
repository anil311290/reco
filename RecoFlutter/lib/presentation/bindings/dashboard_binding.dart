import 'package:get/get.dart';

import '../../../core/services/network_monitor_service.dart';
import '../controllers/dashboard/dashboard_controller.dart';

class DashboardBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<DashboardController>(
      () => DashboardController(
        Get.find(),
        Get.find(),
        Get.find<NetworkMonitorService>(),
      ),
      fenix: true,
    );
  }
}
