import 'package:get/get.dart';

class MainController extends GetxController {
  static const int dashboardTabIndex = 2;
  final selectedTab = 2.obs;

  void changeTab(int index) {
    if (index < 0) {
      return;
    }
    selectedTab.value = index;
  }

  bool get isDashboardSelected => selectedTab.value == dashboardTabIndex;

  void openDashboard() {
    selectedTab.value = dashboardTabIndex;
  }
}
