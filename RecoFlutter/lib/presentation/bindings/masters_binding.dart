import 'package:get/get.dart';

import '../controllers/masters/accounts_controller.dart';
import '../controllers/masters/categories_controller.dart';
import '../controllers/masters/items_controller.dart';
import '../controllers/masters/masters_lookup_controller.dart';
import '../controllers/masters/masters_shell_controller.dart';
import '../controllers/masters/parties_controller.dart';
import '../controllers/masters/tax_rates_controller.dart';

class MastersBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<MastersShellController>(
      () => MastersShellController(),
      fenix: true,
    );
    Get.lazyPut<MastersLookupController>(
      () => MastersLookupController(
        Get.find(),
        Get.find(),
        Get.find(),
        Get.find(),
      ),
      fenix: true,
    );
    Get.lazyPut<PartiesController>(
      () => PartiesController(Get.find(), Get.find(), Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<AccountsController>(
      () => AccountsController(Get.find(), Get.find(), Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<CategoriesController>(
      () => CategoriesController(Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<TaxRatesController>(
      () => TaxRatesController(Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<ItemsController>(
      () => ItemsController(
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
