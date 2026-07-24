import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../bindings/masters_binding.dart';
import '../../controllers/masters/masters_shell_controller.dart';
import 'forms/account_form_sheet.dart';
import 'forms/category_form_sheet.dart';
import 'forms/item_form_sheet.dart';
import 'forms/party_form_sheet.dart';
import 'forms/tax_rate_form_sheet.dart';
import 'tabs/accounts_tab_screen.dart';
import 'tabs/categories_tab_screen.dart';
import 'tabs/items_tab_screen.dart';
import 'tabs/parties_tab_screen.dart';
import 'tabs/tax_rates_tab_screen.dart';
import 'widgets/masters_ui_components.dart';

class MastersScreen extends StatelessWidget {
  const MastersScreen({super.key});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<MastersShellController>()) {
      MastersBinding().dependencies();
    }

    return GetX<MastersShellController>(
      builder: (controller) {
        return Scaffold(

          appBar: AppBar(

            title: Text(
              'Masters',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                fontSize: 15
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
                  'AR / AP',
                  'Ledgers',
                  'Items',
                  'Item Categories',
                  'Tax Rates',
                ],
                value: controller.selectedTab.value.index,
                onChanged: (index) =>
                    controller.changeTab(MastersTab.values[index]),
              ),
              const SizedBox(height: 4),
              Expanded(
                child: IndexedStack(
                  index: controller.selectedTab.value.index,
                  children: const <Widget>[
                    PartiesTabScreen(),
                    AccountsTabScreen(),
                    ItemsTabScreen(),
                    CategoriesTabScreen(),
                    TaxRatesTabScreen(),
                  ],
                ),
              ),
            ],
          ),
          floatingActionButton: Obx(() {
            final selectedTab = controller.selectedTab.value;
            return MasterFab(
              label: switch (selectedTab) {
                MastersTab.parties => 'Add Party',
                MastersTab.accounts => 'Add Ledger',
                MastersTab.items => 'Add Item',
                MastersTab.categories => 'Add Category',
                MastersTab.taxes => 'Add Tax',
              },
              onPressed: () {
                switch (selectedTab) {
                  case MastersTab.parties:
                    Get.to(() => const PartyFormSheet());
                    break;
                  case MastersTab.accounts:
                    Get.to(() => const AccountFormSheet());
                    break;
                  case MastersTab.items:
                    Get.to(() => const ItemFormSheet());
                    break;
                  case MastersTab.categories:
                    Get.to(() => const CategoryFormSheet());
                    break;
                  case MastersTab.taxes:
                    Get.to(() => const TaxRateFormSheet());
                    break;
                }
              },
            );
          }),
        );
      },
    );
  }
}
