import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../bindings/transactions_binding.dart';
import '../../controllers/transactions/transactions_shell_controller.dart';
import 'tabs/adjustments_tab_screen.dart';
import 'tabs/payments_tab_screen.dart';
import 'tabs/purchase_invoices_tab_screen.dart';
import 'tabs/receipts_tab_screen.dart';
import 'tabs/sales_invoices_tab_screen.dart';
import 'tabs/service_sales_invoices_tab_screen.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'transaction_options_screen.dart';

class TransactionsScreen extends StatelessWidget {
  const TransactionsScreen({super.key});

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
              IconButton(
                onPressed: () {},
                icon: const Icon(Icons.notifications_none_rounded),
              ),
              const SizedBox(width: 4),
            ],
          ),
          body: Column(
            children: <Widget>[
              MasterLineTabs(
                labels: const <String>[
                  'Payments',
                  'Receipts',
                  'Adjustments',
                  'Sales',
                  'Service Sales',
                  'Purchase',
                ],
                value: controller.selectedTab.value.index,
                onChanged: (index) =>
                    controller.changeTab(TransactionsTab.values[index]),
              ),
              const SizedBox(height: 4),
              Expanded(
                child: IndexedStack(
                  index: controller.selectedTab.value.index,
                  children: const <Widget>[
                    PaymentsTabScreen(),
                    ReceiptsTabScreen(),
                    AdjustmentsTabScreen(),
                    SalesInvoicesTabScreen(),
                    ServiceSalesInvoicesTabScreen(),
                    PurchaseInvoicesTabScreen(),
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
