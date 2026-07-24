import 'package:get/get.dart';

import '../controllers/dashboard/dashboard_controller.dart';
import '../controllers/masters/masters_shell_controller.dart';
import '../controllers/main/main_controller.dart';
import '../controllers/reports/report_lookup_controller.dart';
import '../controllers/settings/settings_controller.dart';
import '../controllers/transactions/transactions_shell_controller.dart';
import 'dashboard_binding.dart';
import 'masters_binding.dart';
import 'reports_binding.dart';
import 'transactions_binding.dart';

class MainBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<MainController>(() => MainController(), fenix: true);
    if (!Get.isRegistered<MastersShellController>()) {
      MastersBinding().dependencies();
    }
    if (!Get.isRegistered<DashboardController>()) {
      DashboardBinding().dependencies();
    }
    if (!Get.isRegistered<TransactionsShellController>()) {
      TransactionsBinding().dependencies();
    }
    if (!Get.isRegistered<ReportLookupController>()) {
      ReportsBinding().dependencies();
    }
    Get.lazyPut<SettingsController>(
      () => SettingsController(
        Get.find(),
        Get.find(),
        Get.find(),
        Get.find(),
        Get.find(),
        Get.find(),
      ),
      fenix: true,
    );
  }
}
