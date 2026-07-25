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

  static const List<MastersTab> _tabOrder = <MastersTab>[
    MastersTab.accounts,
    MastersTab.parties,
    MastersTab.items,
    MastersTab.categories,
    MastersTab.taxes,
  ];

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
                  'Ledgers',
                  'AR / AP',
                  'Items',
                  'Item Categories',
                  'Tax Rates',
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
                    AccountsTabScreen(),
                    PartiesTabScreen(),
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
                MastersTab.accounts => 'Add Ledger',
                MastersTab.parties => 'Add Party',
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
                    _showItemTypeDialog(context);
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

  void _showItemTypeDialog(BuildContext context) {
    final theme = Theme.of(context);
    final primary = theme.colorScheme.primary;
    showDialog<void>(
      context: context,
      barrierDismissible: true,
      builder: (ctx) => AlertDialog(
        backgroundColor: theme.cardColor,
        insetPadding: const EdgeInsets.all(20),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        title: Text(
          'Select Item Type',
          style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
        ),
        content: Text(
          'What would you like to create?',
          style: theme.textTheme.bodyMedium?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
        actionsPadding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
        actions: <Widget>[
          Row(
            children: <Widget>[
              Expanded(
                child: FilledButton.icon(
                  onPressed: () {
                    Navigator.of(ctx).pop();
                    Get.to(() => const ItemFormSheet());
                  },
                  icon: const Icon(Icons.inventory_2_outlined, size: 18),
                  label: const Text('Goods'),
                  style: FilledButton.styleFrom(
                    backgroundColor: primary,
                    minimumSize: const Size(0, 48),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: FilledButton.icon(
                  onPressed: () {
                    Navigator.of(ctx).pop();
                    Get.to(() => const AccountFormSheet());
                  },
                  icon: const Icon(Icons.miscellaneous_services_outlined, size: 18),
                  label: const Text('Service'),
                  style: FilledButton.styleFrom(
                    backgroundColor: primary,
                    minimumSize: const Size(0, 48),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
