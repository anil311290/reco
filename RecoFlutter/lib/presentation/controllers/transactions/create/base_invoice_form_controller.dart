import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/config/api_endpoints.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../../data/repositories/transactions/transactions_repository.dart';
import '../purchase_invoices_controller.dart';
import '../sales_invoices_controller.dart';
import 'transaction_form_lookup_controller.dart';
import 'transaction_form_models.dart';

abstract class BaseInvoiceFormController extends GetxController {
  BaseInvoiceFormController(this.repository, this.lookupController);

  final TransactionsRepository repository;
  final TransactionFormLookupController lookupController;

  final formKey = GlobalKey<FormState>();
  final invoiceDateController = TextEditingController();
  final dueDateController = TextEditingController();
  final referenceController = TextEditingController();
  final paymentTermsController = TextEditingController();
  final notesController = TextEditingController();
  final discountController = TextEditingController(text: '0');
  final supplierInvoiceController = TextEditingController();
  final selectedParty = Rxn<PartyEntity>();
  final itemRows = <InvoiceItemRowModel>[].obs;
  final serviceRows = <InvoiceServiceRowModel>[].obs;
  final isSubmitting = false.obs;

  String get title;
  String get module;
  String get endpoint;
  String get partyType;
  String get serviceAccountType;
  String get temporaryPrefix;
  bool get supportsItems;
  bool get supportsServices;
  bool get isPurchaseInvoice => false;
  bool get usesUnifiedSalesRows =>
      supportsItems && supportsServices && !isPurchaseInvoice;

  List<PartyEntity> get parties => lookupController.parties;
  List<ItemEntity> get items => lookupController.items;
  List<TaxRateEntity> get taxRates => lookupController.taxRates;
  List<LookupOption> get serviceAccounts => lookupController.serviceAccounts;
  List<InvoiceCatalogOption> get salesCatalogOptions => <InvoiceCatalogOption>[
    ...items.map(InvoiceCatalogOption.item),
    ...serviceAccounts.map(InvoiceCatalogOption.service),
  ];

  double get summarySubtotal {
    final itemSubtotal =
        itemRows.fold<double>(0, (sum, row) => sum + row.taxableAmount);
    final serviceSubtotal =
        serviceRows.fold<double>(0, (sum, row) => sum + row.amount);
    return itemSubtotal + serviceSubtotal;
  }

  double get summaryDiscount =>
      itemRows.fold<double>(0, (sum, row) => sum + row.discountAmount);

  double get summaryTax {
    final itemTax = itemRows.fold<double>(0, (sum, row) => sum + row.taxAmount);
    final serviceTax =
        serviceRows.fold<double>(0, (sum, row) => sum + row.taxAmount);
    return itemTax + serviceTax;
  }

  double get summaryTotal => summarySubtotal + summaryTax;

  @override
  void onInit() {
    super.onInit();
    final now = DateTime.now();
    invoiceDateController.text =
        '${now.year.toString().padLeft(4, '0')}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
    final dueDate = now.add(const Duration(days: 30));
    dueDateController.text =
        '${dueDate.year.toString().padLeft(4, '0')}-${dueDate.month.toString().padLeft(2, '0')}-${dueDate.day.toString().padLeft(2, '0')}';
    if (supportsItems) {
      itemRows.add(InvoiceItemRowModel());
    }
    if (supportsServices && !usesUnifiedSalesRows) {
      serviceRows.add(InvoiceServiceRowModel());
    }
    _loadLookups();
  }

  @override
  void onClose() {
    invoiceDateController.dispose();
    dueDateController.dispose();
    referenceController.dispose();
    paymentTermsController.dispose();
    notesController.dispose();
    discountController.dispose();
    supplierInvoiceController.dispose();
    for (final row in itemRows) {
      row.dispose();
    }
    for (final row in serviceRows) {
      row.dispose();
    }
    super.onClose();
  }

  Future<void> _loadLookups() {
    return lookupController.loadInvoiceLookups(
      partyType: partyType,
      serviceAccountType: serviceAccountType,
      includeItems: supportsItems,
    );
  }

  Future<void> pickDate(
    BuildContext context,
    TextEditingController controller,
  ) async {
    final initial = DateTime.tryParse(controller.text) ?? DateTime.now();
    final selected = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      controller.text =
          '${selected.year.toString().padLeft(4, '0')}-${selected.month.toString().padLeft(2, '0')}-${selected.day.toString().padLeft(2, '0')}';
    }
  }

  void addItemRow() => itemRows.add(InvoiceItemRowModel());

  void removeItemRow(InvoiceItemRowModel row) {
    if (itemRows.length == 1) {
      return;
    }
    itemRows.remove(row);
    row.dispose();
  }

  void addServiceRow() => serviceRows.add(InvoiceServiceRowModel());

  void removeServiceRow(InvoiceServiceRowModel row) {
    if (serviceRows.length == 1) {
      return;
    }
    serviceRows.remove(row);
    row.dispose();
  }

  void onItemChanged(InvoiceItemRowModel row, ItemEntity? item) {
    row.catalogOption.value = item == null ? null : InvoiceCatalogOption.item(item);
    row.item.value = item;
    row.serviceAccount.value = null;
    if (item == null) {
      row.descriptionController.clear();
      row.unitPriceController.clear();
      row.taxRate.value = null;
      return;
    }
    if (row.descriptionController.text.trim().isEmpty) {
      row.descriptionController.text =
          item.description.trim().isEmpty ? item.name : item.description;
    }
    row.unitPriceController.text = formatAmount(
      isPurchaseInvoice ? item.purchasePrice : item.sellingPrice,
    );
    if (item.taxRateId != null) {
      final taxRate = taxRates.firstWhereOrNull((entry) => entry.id == item.taxRateId);
      if (taxRate != null) {
        row.taxRate.value = taxRate;
      }
    }
    update();
  }

  void onSalesCatalogChanged(
    InvoiceItemRowModel row,
    InvoiceCatalogOption? option,
  ) {
    row.catalogOption.value = option;
    row.taxRate.value = null;

    if (option == null) {
      row.item.value = null;
      row.serviceAccount.value = null;
      row.descriptionController.clear();
      row.quantityController.text = '1';
      row.unitPriceController.clear();
      row.discountController.text = '0';
      update();
      return;
    }

    if (option.isItem) {
      row.discountController.text =
          row.discountController.text.trim().isEmpty ? '0' : row.discountController.text;
      row.quantityController.text =
          row.quantityController.text.trim().isEmpty ? '1' : row.quantityController.text;
      onItemChanged(row, option.item);
      return;
    }

    row.item.value = null;
    row.serviceAccount.value = option.account;
    row.quantityController.text = '1';
    row.discountController.text = '0';
    row.descriptionController.text = option.account?.label ?? '';
    update();
  }

  void onServiceAccountChanged(InvoiceServiceRowModel row, LookupOption? account) {
    row.account.value = account;
    if (account != null && row.descriptionController.text.trim().isEmpty) {
      row.descriptionController.text = account.label;
    }
    update();
  }

  Future<void> submit() async {
    FocusManager.instance.primaryFocus?.unfocus();
    if (!formKey.currentState!.validate()) {
      return;
    }
    if (selectedParty.value == null) {
      AppSnackbar.error(isPurchaseInvoice
          ? 'Supplier select karein.'
          : 'Customer select karein.');
      return;
    }
    if (dueDateController.text.compareTo(invoiceDateController.text) < 0) {
      AppSnackbar.error('Due date invoice date se chhoti nahi ho sakti.');
      return;
    }
    final validItemRows = itemRows
        .where(
          (row) =>
              row.isItemSelection &&
              row.item.value != null &&
              row.quantity > 0 &&
              row.unitPrice >= 0,
        )
        .toList();
    final validMixedServiceRows = itemRows
        .where(
          (row) =>
              row.isServiceSelection &&
              row.serviceAccount.value != null &&
              row.unitPrice > 0,
        )
        .toList();
    final validServiceRows = <InvoiceServiceRowModel>[
      ...serviceRows.where((row) => row.account.value != null && row.amount > 0),
    ];
    if (supportsItems && supportsServices) {
      if (validItemRows.isEmpty &&
          validServiceRows.isEmpty &&
          validMixedServiceRows.isEmpty) {
        AppSnackbar.error('Kam se kam ek line item ya service row add karein.');
        return;
      }
    } else if (supportsItems && validItemRows.isEmpty) {
      AppSnackbar.error('Kam se kam ek valid item row add karein.');
      return;
    } else if (supportsServices && validServiceRows.isEmpty) {
      AppSnackbar.error('Kam se kam ek valid service row add karein.');
      return;
    }

    isSubmitting.value = true;
    try {
      final payload = _buildPayload(
        validItemRows,
        validServiceRows,
        validMixedServiceRows,
      );
      await repository.createRecord(
        module: module,
        endpoint: endpoint,
        payload: payload,
      );
      await _refreshList();
      Get.back<void>();
      AppSnackbar.success('$title local me save ho gaya. Sync online hone par ho jayega.');
    } catch (error) {
      AppSnackbar.error(error.toString());
    } finally {
      isSubmitting.value = false;
    }
  }

  Map<String, dynamic> _buildPayload(
    List<InvoiceItemRowModel> validItemRows,
    List<InvoiceServiceRowModel> validServiceRows,
    List<InvoiceItemRowModel> validMixedServiceRows,
  ) {
    final tempNumber = '$temporaryPrefix-${DateTime.now().millisecondsSinceEpoch}';
    return <String, dynamic>{
      'party_id': selectedParty.value?.id,
      'invoice_date': invoiceDateController.text.trim(),
      'due_date': dueDateController.text.trim(),
      if (isPurchaseInvoice)
        'supplier_invoice_number': supplierInvoiceController.text.trim().isEmpty
            ? null
            : supplierInvoiceController.text.trim()
      else
        'reference_number': referenceController.text.trim().isEmpty
            ? null
            : referenceController.text.trim(),
      'payment_terms': paymentTermsController.text.trim().isEmpty
          ? null
          : paymentTermsController.text.trim(),
      'delivery_terms': paymentTermsController.text.trim().isEmpty
          ? null
          : paymentTermsController.text.trim(),
      'notes': notesController.text.trim().isEmpty
          ? null
          : notesController.text.trim(),
      'discount_percentage': double.tryParse(discountController.text.trim()) ?? 0,
      'lines': validItemRows
          .map(
            (row) => <String, dynamic>{
              'item_id': row.item.value?.id,
              if (isPurchaseInvoice)
                'account_id': row.item.value?.expenseAccountId
              else
                'account_id': row.item.value?.incomeAccountId,
              'tax_rate_id': row.taxRate.value?.id,
              'description': row.descriptionController.text.trim().isEmpty
                  ? null
                  : row.descriptionController.text.trim(),
              'quantity': row.quantity,
              'unit_price': row.unitPrice,
              'discount_percentage': row.discountPercentage,
            },
          )
          .toList(),
      'service_lines': validServiceRows
          .map(
            (row) => <String, dynamic>{
              'account_id': row.account.value?.id,
              'tax_rate_id': row.taxRate.value?.id,
              'description': row.descriptionController.text.trim().isEmpty
                  ? null
                  : row.descriptionController.text.trim(),
              'amount': row.amount,
            },
          )
          .followedBy(
            validMixedServiceRows.map(
              (row) => <String, dynamic>{
                'account_id': row.serviceAccount.value?.id,
                'tax_rate_id': row.taxRate.value?.id,
                'description': row.descriptionController.text.trim().isEmpty
                    ? row.serviceAccount.value?.label
                    : row.descriptionController.text.trim(),
                'amount': row.unitPrice,
              },
            ),
          )
          .toList(),
      'invoice_number': tempNumber,
      'status': 'draft',
      'status_label': 'Draft',
      'total': summaryTotal,
      'amount_paid': 0,
      'balance_due': summaryTotal,
      'party': selectedParty.value == null
          ? null
          : <String, dynamic>{
              'id': selectedParty.value!.id,
              'name': selectedParty.value!.name,
            },
    };
  }

  Future<void> _refreshList() async {
    if (module == 'sales_invoices' && Get.isRegistered<SalesInvoicesController>()) {
      await Get.find<SalesInvoicesController>().refreshData();
    }
    if (module == 'purchase_invoices' &&
        Get.isRegistered<PurchaseInvoicesController>()) {
      await Get.find<PurchaseInvoicesController>().refreshData();
    }
  }
}

class SalesInvoiceFormController extends BaseInvoiceFormController {
  SalesInvoiceFormController(super.repository, super.lookupController);

  @override
  String get title => 'Sales Invoice';

  @override
  String get module => 'sales_invoices';

  @override
  String get endpoint => ApiEndpoints.salesInvoices;

  @override
  String get partyType => 'debtor';

  @override
  String get serviceAccountType => 'income';

  @override
  String get temporaryPrefix => 'INV';

  @override
  bool get supportsItems => true;

  @override
  bool get supportsServices => true;
}

class PurchaseInvoiceFormController extends BaseInvoiceFormController {
  PurchaseInvoiceFormController(super.repository, super.lookupController);

  @override
  String get title => 'Purchase Invoice';

  @override
  String get module => 'purchase_invoices';

  @override
  String get endpoint => ApiEndpoints.purchaseInvoices;

  @override
  String get partyType => 'creditor';

  @override
  String get serviceAccountType => 'expense';

  @override
  String get temporaryPrefix => 'PUR';

  @override
  bool get supportsItems => true;

  @override
  bool get supportsServices => true;

  @override
  bool get isPurchaseInvoice => true;
}
