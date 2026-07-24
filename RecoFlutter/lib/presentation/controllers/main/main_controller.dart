import 'package:get/get.dart';

class MainController extends GetxController {
  final selectedTab = 2.obs;

  void changeTab(int index) {
    selectedTab.value = index;
  }
}
