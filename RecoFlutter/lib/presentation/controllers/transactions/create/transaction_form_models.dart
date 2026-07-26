import 'package:flutter/material.dart';

import '../../../../data/models/masters/master_entities.dart';

enum InvoiceCatalogOptionKind { item, service }

class InvoiceCatalogOption {
  const InvoiceCatalogOption.item(this.item)
    : kind = InvoiceCatalogOptionKind.item,
      account = null;

  const InvoiceCatalogOption.service(this.account)
    : kind = InvoiceCatalogOptionKind.service,
      item = null;

  final InvoiceCatalogOptionKind kind;
  final ItemEntity? item;
  final LookupOption? account;

  bool get isItem => kind == InvoiceCatalogOptionKind.item;
  bool get isService => kind == InvoiceCatalogOptionKind.service;

  String get label {
    if (isItem) {
      final code = item?.itemCode.trim() ?? '';
      final name = item?.name ?? '';
      return code.isEmpty ? '[Item] $name' : '[Item] $code - $name';
    }
    return '[Service] ${account?.label ?? ''}';
  }

  String get identityKey {
    if (isItem) {
      return 'item:${item?.id ?? item?.itemCode ?? item?.name ?? ''}';
    }
    return 'service:${account?.valueKey ?? account?.id ?? account?.label ?? ''}';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) {
      return true;
    }
    return other is InvoiceCatalogOption &&
        other.kind == kind &&
        other.identityKey == identityKey;
  }

  @override
  int get hashCode => Object.hash(kind, identityKey);
}

class PaymentVoucherRowModel {
  PaymentVoucherRowModel({
    LookupOption? account,
    String amount = '',
    String description = '',
  }) : account = ValueNotifier<LookupOption?>(account),
       amountController = TextEditingController(text: amount),
       descriptionController = TextEditingController(text: description);

  final ValueNotifier<LookupOption?> account;
  final TextEditingController amountController;
  final TextEditingController descriptionController;

  double get amount => double.tryParse(amountController.text.trim()) ?? 0;

  void dispose() {
    account.dispose();
    amountController.dispose();
    descriptionController.dispose();
  }
}

class AdjustmentVoucherRowModel {
  AdjustmentVoucherRowModel({
    LookupOption? account,
    String entryType = 'debit',
    String amount = '',
    String description = '',
  }) : account = ValueNotifier<LookupOption?>(account),
       entryType = ValueNotifier<String>(entryType),
       amountController = TextEditingController(text: amount),
       descriptionController = TextEditingController(text: description);

  final ValueNotifier<LookupOption?> account;
  final ValueNotifier<String> entryType;
  final TextEditingController amountController;
  final TextEditingController descriptionController;

  double get amount => double.tryParse(amountController.text.trim()) ?? 0;

  void dispose() {
    account.dispose();
    entryType.dispose();
    amountController.dispose();
    descriptionController.dispose();
  }
}

class InvoiceItemRowModel {
  InvoiceItemRowModel({
    InvoiceCatalogOption? catalogOption,
    ItemEntity? item,
    LookupOption? serviceAccount,
    TaxRateEntity? taxRate,
    String description = '',
    String quantity = '1',
    String unitPrice = '',
    String discountPercentage = '0',
  }) : item = ValueNotifier<ItemEntity?>(item),
       serviceAccount = ValueNotifier<LookupOption?>(serviceAccount),
       catalogOption = ValueNotifier<InvoiceCatalogOption?>(catalogOption),
       taxRate = ValueNotifier<TaxRateEntity?>(taxRate),
       descriptionController = TextEditingController(text: description),
       quantityController = TextEditingController(text: quantity),
       unitPriceController = TextEditingController(text: unitPrice),
       discountController = TextEditingController(text: discountPercentage);

  final ValueNotifier<ItemEntity?> item;
  final ValueNotifier<LookupOption?> serviceAccount;
  final ValueNotifier<InvoiceCatalogOption?> catalogOption;
  final ValueNotifier<TaxRateEntity?> taxRate;
  final TextEditingController descriptionController;
  final TextEditingController quantityController;
  final TextEditingController unitPriceController;
  final TextEditingController discountController;

  bool get isServiceSelection => catalogOption.value?.isService == true;
  bool get isItemSelection =>
      catalogOption.value == null || catalogOption.value?.isItem == true;

  double get quantity => double.tryParse(quantityController.text.trim()) ?? 0;
  double get unitPrice => double.tryParse(unitPriceController.text.trim()) ?? 0;
  double get discountPercentage =>
      double.tryParse(discountController.text.trim()) ?? 0;

  double get taxPercentage => taxRate.value?.taxRate ?? 0;

  double get baseAmount => (isServiceSelection ? 1 : quantity) * unitPrice;
  double get discountAmount =>
      isServiceSelection ? 0 : baseAmount * (discountPercentage / 100);
  double get taxableAmount => baseAmount - discountAmount;
  double get taxAmount => taxableAmount * (taxPercentage / 100);
  double get totalAmount => taxableAmount + taxAmount;

  void dispose() {
    item.dispose();
    serviceAccount.dispose();
    catalogOption.dispose();
    taxRate.dispose();
    descriptionController.dispose();
    quantityController.dispose();
    unitPriceController.dispose();
    discountController.dispose();
  }
}

class InvoiceServiceRowModel {
  InvoiceServiceRowModel({
    LookupOption? account,
    TaxRateEntity? taxRate,
    String description = '',
    String amount = '',
  }) : account = ValueNotifier<LookupOption?>(account),
       taxRate = ValueNotifier<TaxRateEntity?>(taxRate),
       descriptionController = TextEditingController(text: description),
       amountController = TextEditingController(text: amount);

  final ValueNotifier<LookupOption?> account;
  final ValueNotifier<TaxRateEntity?> taxRate;
  final TextEditingController descriptionController;
  final TextEditingController amountController;

  double get amount => double.tryParse(amountController.text.trim()) ?? 0;
  double get taxPercentage => taxRate.value?.taxRate ?? 0;
  double get taxAmount => amount * (taxPercentage / 100);
  double get totalAmount => amount + taxAmount;

  void dispose() {
    account.dispose();
    taxRate.dispose();
    descriptionController.dispose();
    amountController.dispose();
  }
}

String formatAmount(double value) => value.toStringAsFixed(2);
