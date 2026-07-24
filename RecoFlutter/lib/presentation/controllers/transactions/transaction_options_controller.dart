import 'package:flutter/material.dart';
import 'package:get/get.dart';

class TransactionOptionItem {
  const TransactionOptionItem({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.tag,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final String tag;
}

class TransactionOptionsController extends GetxController {
  List<TransactionOptionItem> get items => const <TransactionOptionItem>[
        TransactionOptionItem(
          title: 'Payment Voucher',
          subtitle: 'Cash or bank outflow voucher',
          icon: Icons.wallet_outlined,
          tag: 'payment',
        ),
        TransactionOptionItem(
          title: 'Receipt Voucher',
          subtitle: 'Cash or bank inflow voucher',
          icon: Icons.account_balance_wallet_outlined,
          tag: 'receipt',
        ),
        TransactionOptionItem(
          title: 'Adjustment Voucher',
          subtitle: 'Journal or adjustment entry',
          icon: Icons.auto_stories_outlined,
          tag: 'adjustment',
        ),
        TransactionOptionItem(
          title: 'Item Sale Invoice',
          subtitle: 'Goods invoice for customers',
          icon: Icons.request_quote_outlined,
          tag: 'sales',
        ),
        TransactionOptionItem(
          title: 'Service Sale Invoice',
          subtitle: 'Service billing invoice',
          icon: Icons.design_services_outlined,
          tag: 'service_sales',
        ),
        TransactionOptionItem(
          title: 'Purchase Invoice',
          subtitle: 'Supplier purchase bill',
          icon: Icons.inventory_2_outlined,
          tag: 'purchase',
        ),
      ];
}
