import 'package:get/get.dart';

enum MastersTab { parties, accounts, items, categories, taxes }

class MastersShellController extends GetxController {
  final selectedTab = MastersTab.accounts.obs;

  void changeTab(MastersTab tab) {
    selectedTab.value = tab;
  }
}
