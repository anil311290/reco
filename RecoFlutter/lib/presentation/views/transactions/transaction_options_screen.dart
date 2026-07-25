import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../data/repositories/accounts/accounts_repository.dart';
import '../../../data/repositories/masters/items_repository.dart';
import '../../../data/repositories/masters/parties_repository.dart';
import '../../../data/repositories/masters/tax_rates_repository.dart';
import '../../../data/repositories/transactions/transactions_repository.dart';
import '../../controllers/transactions/transaction_options_controller.dart';
import '../../controllers/transactions/create/base_invoice_form_controller.dart';
import '../../controllers/transactions/create/base_voucher_form_controller.dart';
import '../../controllers/transactions/create/transaction_form_lookup_controller.dart';
import '../../widgets/common/common_button.dart';
import 'create/adjustment_voucher_screen.dart';
import 'create/payment_voucher_screen.dart';
import 'create/purchase_invoice_screen.dart';
import 'create/receipt_voucher_screen.dart';
import 'create/sales_invoice_screen.dart';
import 'widgets/transactions_ui_components.dart';

class TransactionOptionsScreen extends GetView<TransactionOptionsController> {
  const TransactionOptionsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Create Transaction',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      body: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: controller.items.length,
        separatorBuilder: (_, index) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          final item = controller.items[index];
          return TransactionOptionCard(
            item: item,
            onTap: () => _openForm(item.tag),
          );
        },
      ),
      bottomNavigationBar: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
          child: CommonButton(
            text: 'Close',
            onPressed: Get.back,
          ),
        ),
      ),
    );
  }

  void _openForm(String tag) {
    switch (tag) {
      case 'payment':
        Get.to(
          () => const PaymentVoucherScreen(),
          binding: BindingsBuilder(
            () {
              final lookup = Get.put(
                TransactionFormLookupController(
                  Get.find<PartiesRepository>(),
                  Get.find<AccountsRepository>(),
                  Get.find<ItemsRepository>(),
                  Get.find<TaxRatesRepository>(),
                ),
              );
              Get.put(
                PaymentVoucherFormController(
                  Get.find<TransactionsRepository>(),
                  lookup,
                ),
              );
            },
          ),
        );
        break;
      case 'receipt':
        Get.to(
          () => const ReceiptVoucherScreen(),
          binding: BindingsBuilder(
            () {
              final lookup = Get.put(
                TransactionFormLookupController(
                  Get.find<PartiesRepository>(),
                  Get.find<AccountsRepository>(),
                  Get.find<ItemsRepository>(),
                  Get.find<TaxRatesRepository>(),
                ),
              );
              Get.put(
                ReceiptVoucherFormController(
                  Get.find<TransactionsRepository>(),
                  lookup,
                ),
              );
            },
          ),
        );
        break;
      case 'adjustment':
        Get.to(
          () => const AdjustmentVoucherScreen(),
          binding: BindingsBuilder(
            () {
              final lookup = Get.put(
                TransactionFormLookupController(
                  Get.find<PartiesRepository>(),
                  Get.find<AccountsRepository>(),
                  Get.find<ItemsRepository>(),
                  Get.find<TaxRatesRepository>(),
                ),
              );
              Get.put(
                AdjustmentVoucherFormController(
                  Get.find<TransactionsRepository>(),
                  lookup,
                ),
              );
            },
          ),
        );
        break;
      case 'sales':
        Get.to(
          () => const SalesInvoiceScreen(),
          binding: BindingsBuilder(
            () {
              final lookup = Get.put(
                TransactionFormLookupController(
                  Get.find<PartiesRepository>(),
                  Get.find<AccountsRepository>(),
                  Get.find<ItemsRepository>(),
                  Get.find<TaxRatesRepository>(),
                ),
              );
              Get.put(
                SalesInvoiceFormController(
                  Get.find<TransactionsRepository>(),
                  lookup,
                ),
              );
            },
          ),
        );
        break;
      case 'purchase':
        Get.to(
          () => const PurchaseInvoiceScreen(),
          binding: BindingsBuilder(
            () {
              final lookup = Get.put(
                TransactionFormLookupController(
                  Get.find<PartiesRepository>(),
                  Get.find<AccountsRepository>(),
                  Get.find<ItemsRepository>(),
                  Get.find<TaxRatesRepository>(),
                ),
              );
              Get.put(
                PurchaseInvoiceFormController(
                  Get.find<TransactionsRepository>(),
                  lookup,
                ),
              );
            },
          ),
        );
        break;
    }
  }
}
