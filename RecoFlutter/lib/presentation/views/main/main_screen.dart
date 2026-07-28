import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';

import '../../controllers/main/main_controller.dart';
import '../dashboard/dashboard_screen.dart';
import '../masters/masters_screen.dart';
import '../reports/reports_screen.dart';
import '../settings/settings_screen.dart';
import '../transactions/transactions_screen.dart';

class MainScreen extends GetView<MainController> {
  const MainScreen({super.key});

  @override
  Widget build(BuildContext context) {
    const List<Widget> pages = <Widget>[
      MastersScreen(),
      TransactionsScreen(),
      DashboardScreen(),
      ReportsScreen(),
      SettingsScreen(),
    ];

    return Obx(
      () => PopScope(
        canPop: false,
        onPopInvokedWithResult: (didPop, result) async {
          if (didPop) {
            return;
          }
          await _handleBackPress(context);
        },
        child: Scaffold(
          body: SafeArea(
            child: IndexedStack(
              index: controller.selectedTab.value,
              children: pages,
            ),
          ),
          bottomNavigationBar: BottomNavigationBar(
            currentIndex: controller.selectedTab.value,
            onTap: (index) => _handleTabTap(context, index),
            items: const <BottomNavigationBarItem>[
              BottomNavigationBarItem(
                icon: Icon(Icons.grid_view_outlined),
                activeIcon: Icon(Icons.grid_view_rounded),
                label: 'Masters',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.receipt_long_outlined),
                activeIcon: Icon(Icons.receipt_long),
                label: 'Transactions',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.dashboard_outlined),
                activeIcon: Icon(Icons.dashboard),
                label: 'Dashboard',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.bar_chart_outlined),
                activeIcon: Icon(Icons.bar_chart),
                label: 'Reports',
              ),
              BottomNavigationBarItem(
                icon: Icon(Icons.settings_outlined),
                activeIcon: Icon(Icons.settings),
                label: 'Settings',
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _handleTabTap(BuildContext context, int index) async {
    if (index == controller.selectedTab.value) {
      if (index == MainController.dashboardTabIndex) {
        final shouldExit = await _showExitConfirmation(context);
        if (shouldExit) {
          await SystemNavigator.pop();
        }
      }
      return;
    }

    controller.changeTab(index);
  }

  Future<void> _handleBackPress(BuildContext context) async {
    if (!controller.isDashboardSelected) {
      controller.openDashboard();
      return;
    }

    final shouldExit = await _showExitConfirmation(context);
    if (shouldExit) {
      await SystemNavigator.pop();
    }
  }

  Future<bool> _showExitConfirmation(BuildContext context) async {
    final result = await showCupertinoDialog<bool>(
      context: context,
      builder: (context) => CupertinoAlertDialog(
        title: const Text('Exit Reco ERP'),
        content: const Padding(
          padding: EdgeInsets.only(top: 10),
          child: Text('Do you want to close the app?'),
        ),
        actions: <Widget>[
          CupertinoDialogAction(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Cancel'),
          ),
          CupertinoDialogAction(
            isDestructiveAction: true,
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Exit'),
          ),
        ],
      ),
    );

    return result ?? false;
  }
}
