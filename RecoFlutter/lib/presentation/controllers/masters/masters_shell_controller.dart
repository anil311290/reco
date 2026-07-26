import 'package:get/get.dart';

import '../../../core/services/network_monitor_service.dart';
import '../../../core/services/sync_service.dart';
import 'accounts_controller.dart';
import 'categories_controller.dart';
import 'items_controller.dart';
import 'parties_controller.dart';
import 'tax_rates_controller.dart';

enum MastersTab { parties, accounts, items, categories, taxes }

class MastersShellController extends GetxController {
  final selectedTab = MastersTab.accounts.obs;
  final isRefreshing = false.obs;
  final refreshTurns = 0.0.obs;

  void changeTab(MastersTab tab) {
    selectedTab.value = tab;
  }

  Future<void> refreshAll() async {
    if (isRefreshing.value) {
      return;
    }
    isRefreshing.value = true;
    refreshTurns.value += 1;
    try {
      if (Get.isRegistered<NetworkMonitorService>() &&
          Get.find<NetworkMonitorService>().isOnline.value &&
          Get.isRegistered<SyncService>()) {
        await Get.find<SyncService>().syncPendingMutations(
          showSuccessMessage: false,
        );
      }

      await Future.wait(<Future<void>>[
        Get.find<AccountsController>().refreshData(forceRemote: true),
        Get.find<PartiesController>().refreshData(forceRemote: true),
        Get.find<ItemsController>().refreshData(forceRemote: true),
        Get.find<CategoriesController>().refreshData(forceRemote: true),
        Get.find<TaxRatesController>().refreshData(forceRemote: true),
      ]);
    } finally {
      isRefreshing.value = false;
    }
  }
}
