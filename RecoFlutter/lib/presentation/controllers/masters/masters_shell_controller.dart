import 'package:get/get.dart';

enum MastersTab { parties, accounts, items, categories, taxes }

class MastersShellController extends GetxController {
  final selectedTab = MastersTab.parties.obs;

  void changeTab(MastersTab tab) {
    selectedTab.value = tab;
  }
}
