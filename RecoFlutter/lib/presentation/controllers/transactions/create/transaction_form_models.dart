import 'package:flutter/material.dart';

import '../../../../data/models/masters/master_entities.dart';

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
    ItemEntity? item,
    TaxRateEntity? taxRate,
    String description = '',
    String quantity = '1',
    String unitPrice = '',
    String discountPercentage = '0',
  }) : item = ValueNotifier<ItemEntity?>(item),
       taxRate = ValueNotifier<TaxRateEntity?>(taxRate),
       descriptionController = TextEditingController(text: description),
       quantityController = TextEditingController(text: quantity),
       unitPriceController = TextEditingController(text: unitPrice),
       discountController = TextEditingController(text: discountPercentage);

  final ValueNotifier<ItemEntity?> item;
  final ValueNotifier<TaxRateEntity?> taxRate;
  final TextEditingController descriptionController;
  final TextEditingController quantityController;
  final TextEditingController unitPriceController;
  final TextEditingController discountController;

  double get quantity => double.tryParse(quantityController.text.trim()) ?? 0;
  double get unitPrice => double.tryParse(unitPriceController.text.trim()) ?? 0;
  double get discountPercentage =>
      double.tryParse(discountController.text.trim()) ?? 0;

  double get taxPercentage => taxRate.value?.taxRate ?? 0;

  double get baseAmount => quantity * unitPrice;
  double get discountAmount => baseAmount * (discountPercentage / 100);
  double get taxableAmount => baseAmount - discountAmount;
  double get taxAmount => taxableAmount * (taxPercentage / 100);
  double get totalAmount => taxableAmount + taxAmount;

  void dispose() {
    item.dispose();
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

