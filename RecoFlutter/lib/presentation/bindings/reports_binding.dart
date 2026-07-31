import 'package:get/get.dart';

import '../controllers/reports/balance_sheet_report_controller.dart';
import '../controllers/reports/creditors_outstanding_report_controller.dart';
import '../controllers/reports/day_book_report_controller.dart';
import '../controllers/reports/debtors_outstanding_report_controller.dart';
import '../controllers/reports/ledger_report_controller.dart';
import '../controllers/reports/profit_loss_report_controller.dart';
import '../controllers/reports/receipt_payment_report_controller.dart';
import '../controllers/reports/report_lookup_controller.dart';
import '../controllers/reports/trial_balance_report_controller.dart';

class ReportsBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<ReportLookupController>(
      () => ReportLookupController(Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<DayBookReportController>(
      () => DayBookReportController(Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<LedgerReportController>(
      () => LedgerReportController(Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<TrialBalanceReportController>(
      () => TrialBalanceReportController(Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<ProfitLossReportController>(
      () => ProfitLossReportController(Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<ReceiptPaymentReportController>(
      () => ReceiptPaymentReportController(Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<BalanceSheetReportController>(
      () => BalanceSheetReportController(Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<DebtorsOutstandingReportController>(
      () => DebtorsOutstandingReportController(Get.find(), Get.find()),
      fenix: true,
    );
    Get.lazyPut<CreditorsOutstandingReportController>(
      () => CreditorsOutstandingReportController(Get.find(), Get.find()),
      fenix: true,
    );
  }
}
