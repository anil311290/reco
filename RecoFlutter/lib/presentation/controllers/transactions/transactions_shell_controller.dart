import 'package:get/get.dart';

enum TransactionsTab {
  payments,
  receipts,
  adjustments,
  sales,
  purchases,
}

class TransactionsShellController extends GetxController {
  final selectedTab = TransactionsTab.payments.obs;

  void changeTab(TransactionsTab tab) {
    selectedTab.value = tab;
  }
}
