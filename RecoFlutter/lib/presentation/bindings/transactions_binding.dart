import 'package:get/get.dart';

import '../controllers/transactions/adjustments_controller.dart';
import '../controllers/transactions/all_vouchers_controller.dart';
import '../controllers/transactions/payments_controller.dart';
import '../controllers/transactions/purchase_invoices_controller.dart';
import '../controllers/transactions/receipts_controller.dart';
import '../controllers/transactions/sales_invoices_controller.dart';
import '../controllers/transactions/transaction_options_controller.dart';
import '../controllers/transactions/transactions_lookup_controller.dart';
import '../controllers/transactions/transactions_shell_controller.dart';

class TransactionsBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<TransactionsShellController>(
      () => TransactionsShellController(),
      fenix: true,
    );
    Get.lazyPut<TransactionsLookupController>(
      () => TransactionsLookupController(Get.find()),
      fenix: true,
    );
    Get.lazyPut<TransactionOptionsController>(
      () => TransactionOptionsController(),
      fenix: true,
    );
    Get.lazyPut<AllVouchersController>(
      () => AllVouchersController(Get.find(), Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<PaymentsController>(
      () => PaymentsController(Get.find(), Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<ReceiptsController>(
      () => ReceiptsController(Get.find(), Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<AdjustmentsController>(
      () => AdjustmentsController(Get.find(), Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<SalesInvoicesController>(
      () => SalesInvoicesController(Get.find(), Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<PurchaseInvoicesController>(
      () => PurchaseInvoicesController(Get.find(), Get.find(), Get.find()),
      fenix: true,
    );
  }
}
