import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../bindings/transactions_binding.dart';
import '../../controllers/transactions/transactions_shell_controller.dart';
import 'tabs/adjustments_tab_screen.dart';
import 'tabs/all_vouchers_tab_screen.dart';
import 'tabs/payments_tab_screen.dart';
import 'tabs/purchase_invoices_tab_screen.dart';
import 'tabs/receipts_tab_screen.dart';
import 'tabs/sales_invoices_tab_screen.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'transaction_options_screen.dart';

class TransactionsScreen extends StatelessWidget {
  const TransactionsScreen({super.key});

  static const List<TransactionsTab> _tabOrder = <TransactionsTab>[
    TransactionsTab.all,
    TransactionsTab.sales,
    TransactionsTab.purchases,
    TransactionsTab.payments,
    TransactionsTab.receipts,
    TransactionsTab.adjustments,
  ];

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<TransactionsShellController>()) {
      TransactionsBinding().dependencies();
    }

    return GetX<TransactionsShellController>(
      builder: (controller) {
        return Scaffold(
          appBar: AppBar(
            title: Text(
              'Transactions',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w700,
                fontSize: 15,
              ),
            ),
            centerTitle: false,
            actions: <Widget>[
              AnimatedRotation(
                turns: controller.refreshTurns.value,
                duration: const Duration(milliseconds: 700),
                child: IconButton(
                  onPressed: controller.isRefreshing.value
                      ? null
                      : controller.refreshAll,
                  icon: Icon(
                    Icons.refresh_rounded,
                    color: controller.isRefreshing.value
                        ? Theme.of(context).colorScheme.primary
                        : null,
                    ),
                ),
              ),
              const SizedBox(width: 4),
            ],
          ),
          body: Column(
            children: <Widget>[
              MasterLineTabs(
                labels: const <String>[
                  'All',
                  'Sales Invoice',
                  'Purchase Invoices',
                  'Payments',
                  'Receipts',
                  'Adjustments',
                ],
                value: _tabOrder.indexOf(controller.selectedTab.value),
                onChanged: (index) =>
                    controller.changeTab(_tabOrder[index]),
              ),
              const SizedBox(height: 4),
              Expanded(
                child: IndexedStack(
                  index: _tabOrder.indexOf(controller.selectedTab.value),
                  children: const <Widget>[
                    AllVouchersTabScreen(),
                    SalesInvoicesTabScreen(),
                    PurchaseInvoicesTabScreen(),
                    PaymentsTabScreen(),
                    ReceiptsTabScreen(),
                    AdjustmentsTabScreen(),
                  ],
                ),
              ),
            ],
          ),
          floatingActionButton: MasterFab(
            label: 'Add Transaction',
            onPressed: () => Get.to(() => const TransactionOptionsScreen()),
          ),
        );
      },
    );
  }
}
