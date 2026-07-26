import 'package:get/get.dart';

import '../../../core/services/network_monitor_service.dart';
import '../../../core/services/sync_service.dart';
import 'adjustments_controller.dart';
import 'all_vouchers_controller.dart';
import 'payments_controller.dart';
import 'purchase_invoices_controller.dart';
import 'receipts_controller.dart';
import 'sales_invoices_controller.dart';

enum TransactionsTab {
  all,
  sales,
  purchases,
  payments,
  receipts,
  adjustments,
}

class TransactionsShellController extends GetxController {
  final selectedTab = TransactionsTab.all.obs;
  final isRefreshing = false.obs;
  final refreshTurns = 0.0.obs;

  void changeTab(TransactionsTab tab) {
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
        Get.find<AllVouchersController>().refreshData(forceRemote: true),
        Get.find<SalesInvoicesController>().refreshData(forceRemote: true),
        Get.find<PurchaseInvoicesController>().refreshData(forceRemote: true),
        Get.find<PaymentsController>().refreshData(forceRemote: true),
        Get.find<ReceiptsController>().refreshData(forceRemote: true),
        Get.find<AdjustmentsController>().refreshData(forceRemote: true),
      ]);
    } finally {
      isRefreshing.value = false;
    }
  }
}
