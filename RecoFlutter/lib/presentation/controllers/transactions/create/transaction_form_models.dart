import 'package:flutter/material.dart';

import '../../../../data/models/masters/master_entities.dart';

enum InvoiceCatalogOptionKind { item, service }

enum InvoicePartyOptionKind { party, cashBankOd }

class InvoicePartyOption {
  const InvoicePartyOption.party(
    this.party, {
    this.overrideLabel,
    this.overrideToken,
  })
    : kind = InvoicePartyOptionKind.party,
      account = null;

  const InvoicePartyOption.account(
    this.account, {
    this.overrideLabel,
    this.overrideToken,
  })
    : kind = InvoicePartyOptionKind.cashBankOd,
      party = null;

  final InvoicePartyOptionKind kind;
  final PartyEntity? party;
  final LookupOption? account;
  final String? overrideLabel;
  final String? overrideToken;

  bool get isParty => kind == InvoicePartyOptionKind.party;
  bool get isAccount => kind == InvoicePartyOptionKind.cashBankOd;

  String get token {
    final rawToken = overrideToken?.trim();
    if (rawToken != null && rawToken.isNotEmpty) {
      return rawToken;
    }
    if (isParty) {
      return 'party:${party?.id ?? ''}';
    }
    return 'account:${account?.id ?? ''}';
  }

  String get label {
    final rawLabel = overrideLabel?.trim();
    if (rawLabel != null && rawLabel.isNotEmpty) {
      return rawLabel;
    }
    if (isParty) {
      final entity = party;
      if (entity == null) {
        return '';
      }
      final code = entity.partyCode.trim();
      return code.isEmpty ? entity.name : '${entity.name} ($code)';
    }

    final option = account;
    if (option == null) {
      return '';
    }
    final code = option.code?.trim() ?? '';
    return code.isEmpty ? option.label : '${option.label} ($code)';
  }

  int? get partyId => isParty ? (party?.id ?? _tokenId(token, 'party:')) : null;
  int? get accountId =>
      isAccount ? (account?.id ?? _tokenId(token, 'account:')) : null;
  String get displayKindLabel => isParty ? 'Party' : 'Ledger';

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) {
      return true;
    }
    return other is InvoicePartyOption &&
        other.kind == kind &&
        other.token == token;
  }

  @override
  int get hashCode => Object.hash(kind, token);
}

int? _tokenId(String token, String prefix) {
  if (!token.startsWith(prefix)) {
    return null;
  }
  return int.tryParse(token.substring(prefix.length));
}

class InvoiceCatalogOption {
  const InvoiceCatalogOption.item(this.item)
    : kind = InvoiceCatalogOptionKind.item,
      serviceItem = null;

  const InvoiceCatalogOption.service(this.serviceItem)
    : kind = InvoiceCatalogOptionKind.service,
      item = null;

  final InvoiceCatalogOptionKind kind;
  final ItemEntity? item;
  final ItemEntity? serviceItem;

  bool get isItem => kind == InvoiceCatalogOptionKind.item;
  bool get isService => kind == InvoiceCatalogOptionKind.service;

  String get label {
    if (isItem) {
      final code = item?.itemCode.trim() ?? '';
      final name = item?.name ?? '';
      return code.isEmpty ? '[Item] $name' : '[Item] $code - $name';
    }
    final code = serviceItem?.itemCode.trim() ?? '';
    final name = serviceItem?.name ?? '';
    return code.isEmpty ? '[Service] $name' : '[Service] $code - $name';
  }

  String get identityKey {
    if (isItem) {
      return 'item:${item?.id ?? item?.itemCode ?? item?.name ?? ''}';
    }
    return 'service:${serviceItem?.id ?? serviceItem?.itemCode ?? serviceItem?.name ?? ''}';
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
    List<Map<String, dynamic>>? invoiceAllocations,
  }) : account = ValueNotifier<LookupOption?>(account),
       amountController = TextEditingController(text: amount),
       descriptionController = TextEditingController(text: description),
       invoiceAllocations = List<Map<String, dynamic>>.from(
         invoiceAllocations ?? const <Map<String, dynamic>>[],
       );

  final ValueNotifier<LookupOption?> account;
  final TextEditingController amountController;
  final TextEditingController descriptionController;
  final List<Map<String, dynamic>> invoiceAllocations;

  double get amount => double.tryParse(amountController.text.trim()) ?? 0;

  bool get isPartyParticular {
    final selected = account.value;
    if (selected == null) {
      return false;
    }
    final kind = (selected.kind ?? '').toLowerCase();
    return kind == 'party' || selected.valueKey.startsWith('party:');
  }

  double get allocatedTotal => invoiceAllocations.fold<double>(
        0,
        (sum, item) =>
            sum + (double.tryParse(item['amount']?.toString() ?? '') ?? 0),
      );

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
