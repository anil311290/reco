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
  BaseInvoiceFormController(
    this.repository,
    this.lookupController, {
    this.initialPayload,
  });

  final TransactionsRepository repository;
  final TransactionFormLookupController lookupController;
  final Map<String, dynamic>? initialPayload;

  final formKey = GlobalKey<FormState>();
  final invoiceNumberController = TextEditingController();
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
  final draftInvoiceNumber = ''.obs;
  Map<String, dynamic>? _editingPayload;

  String get title;
  String get module;
  String get endpoint;
  String get partyType;
  String get serviceAccountType;
  String get temporaryPrefix;
  bool get supportsItems;
  bool get supportsServices;
  bool get isPurchaseInvoice => false;
  bool get isEditing => initialPayload != null;
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
    _initializeForm();
  }

  @override
  void onClose() {
    invoiceDateController.dispose();
    invoiceNumberController.dispose();
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

  Future<void> _initializeForm() async {
    final now = DateTime.now();
    invoiceDateController.text =
        '${now.year.toString().padLeft(4, '0')}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
    final dueDate = now.add(const Duration(days: 30));
    dueDateController.text =
        '${dueDate.year.toString().padLeft(4, '0')}-${dueDate.month.toString().padLeft(2, '0')}-${dueDate.day.toString().padLeft(2, '0')}';

    await _loadLookups();

    if (initialPayload != null) {
      _applyInitialPayload(initialPayload!);
    } else {
      draftInvoiceNumber.value =
          '$temporaryPrefix-${DateTime.now().millisecondsSinceEpoch}';
      invoiceNumberController.text = draftInvoiceNumber.value;
      _ensureMinimumRows();
    }
    update();
  }

  void _ensureMinimumRows() {
    if (supportsItems && itemRows.isEmpty) {
      itemRows.add(InvoiceItemRowModel());
    }
    if (supportsServices && !usesUnifiedSalesRows && serviceRows.isEmpty) {
      serviceRows.add(InvoiceServiceRowModel());
    }
  }

  void _applyInitialPayload(Map<String, dynamic> payload) {
    _editingPayload = Map<String, dynamic>.from(payload);
    draftInvoiceNumber.value = (payload['invoice_number'] ?? '').toString();
    invoiceNumberController.text = draftInvoiceNumber.value;
    invoiceDateController.text = _shortDate(payload['invoice_date']?.toString());
    dueDateController.text = _shortDate(payload['due_date']?.toString());
    referenceController.text = (payload['reference_number'] ?? '').toString();
    paymentTermsController.text = (payload['payment_terms'] ?? '').toString();
    notesController.text = (payload['notes'] ?? '').toString();
    discountController.text = formatAmount(_toDouble(payload['discount_percentage']));
    supplierInvoiceController.text =
        (payload['supplier_invoice_number'] ?? '').toString();

    final partyId = _toInt(payload['party_id']);
    if (partyId != null) {
      selectedParty.value = parties.firstWhereOrNull((item) => item.id == partyId);
    }

    for (final row in itemRows) {
      row.dispose();
    }
    for (final row in serviceRows) {
      row.dispose();
    }
    itemRows.clear();
    serviceRows.clear();

    final itemLineMaps = _extractLineMaps(payload, const <String>{'item'});
    final serviceLineMaps = _extractLineMaps(payload, const <String>{'service'});

    if (supportsItems) {
      for (final line in itemLineMaps) {
        final itemId = _toInt(line['item_id']);
        final taxRateId = _toInt(line['tax_rate_id']);
        final item = items.firstWhereOrNull((entry) => entry.id == itemId);
        final taxRate =
            taxRates.firstWhereOrNull((entry) => entry.id == taxRateId);
        itemRows.add(
          InvoiceItemRowModel(
            item: item,
            catalogOption: usesUnifiedSalesRows && item != null
                ? InvoiceCatalogOption.item(item)
                : null,
            taxRate: taxRate,
            description: (line['description'] ?? '').toString(),
            quantity: _formatEditableNumber(line['quantity'], fallback: '1'),
            unitPrice: _formatEditableNumber(line['unit_price']),
            discountPercentage: _formatEditableNumber(
              line['discount_percentage'],
              fallback: '0',
            ),
          ),
        );
      }
    }

    if (supportsServices) {
      final targetList = usesUnifiedSalesRows ? itemRows : serviceRows;
      for (final line in serviceLineMaps) {
        final accountId = _toInt(line['account_id']);
        final taxRateId = _toInt(line['tax_rate_id']);
        final account =
            serviceAccounts.firstWhereOrNull((entry) => entry.id == accountId);
        final taxRate =
            taxRates.firstWhereOrNull((entry) => entry.id == taxRateId);

        if (usesUnifiedSalesRows) {
          targetList.add(
            InvoiceItemRowModel(
              serviceAccount: account,
              catalogOption: account == null ? null : InvoiceCatalogOption.service(account),
              taxRate: taxRate,
              description: (line['description'] ?? '').toString(),
              quantity: '1',
              unitPrice: _formatEditableNumber(
                line['amount'] ?? line['unit_price'],
              ),
              discountPercentage: '0',
            ),
          );
        } else {
          serviceRows.add(
            InvoiceServiceRowModel(
              account: account,
              taxRate: taxRate,
              description: (line['description'] ?? '').toString(),
              amount: _formatEditableNumber(line['amount'] ?? line['unit_price']),
            ),
          );
        }
      }
    }

    _ensureMinimumRows();
  }

  List<Map<String, dynamic>> _extractLineMaps(
    Map<String, dynamic> payload,
    Set<String> allowedKinds,
  ) {
    final preferredKey = allowedKinds.contains('service') ? 'service_lines' : 'item_lines';
    final preferred = payload[preferredKey];
    if (preferred is List) {
      return preferred
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }

    final lines = payload['lines'];
    if (lines is! List) {
      return <Map<String, dynamic>>[];
    }

    return lines
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .where((item) {
          final kind = (item['kind'] ?? item['line_type'] ?? 'item').toString();
          if (allowedKinds.contains('service')) {
            return kind == 'service';
          }
          return kind != 'service';
        })
        .toList();
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

  void addItemRow() {
    itemRows.add(InvoiceItemRowModel());
    update();
  }

  void removeItemRow(InvoiceItemRowModel row) {
    if (itemRows.length == 1) {
      return;
    }
    itemRows.remove(row);
    row.dispose();
    update();
  }

  void addServiceRow() {
    serviceRows.add(InvoiceServiceRowModel());
    update();
  }

  void removeServiceRow(InvoiceServiceRowModel row) {
    if (serviceRows.length == 1) {
      return;
    }
    serviceRows.remove(row);
    row.dispose();
    update();
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
    row.descriptionController.text =
        item.description.trim().isEmpty ? item.name : item.description;
    row.unitPriceController.text = formatAmount(
      isPurchaseInvoice ? item.purchasePrice : item.sellingPrice,
    );
    row.taxRate.value = item.taxRateId == null
        ? null
        : taxRates.firstWhereOrNull((entry) => entry.id == item.taxRateId);
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
    row.unitPriceController.text = '0.00';
    row.discountController.text = '0';
    row.descriptionController.text = option.account?.label ?? '';
    update();
  }

  void onServiceAccountChanged(InvoiceServiceRowModel row, LookupOption? account) {
    row.account.value = account;
    row.descriptionController.text = account?.label ?? '';
    update();
  }

  Future<void> submit() async {
    FocusManager.instance.primaryFocus?.unfocus();
    if (!formKey.currentState!.validate()) {
      return;
    }
    if (selectedParty.value == null) {
      AppSnackbar.error(isPurchaseInvoice
          ? 'Please select a supplier.'
          : 'Please select a customer.');
      return;
    }
    if (dueDateController.text.compareTo(invoiceDateController.text) < 0) {
      AppSnackbar.error('Due date cannot be earlier than the invoice date.');
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
        AppSnackbar.error('Please add at least one line item or service row.');
        return;
      }
    } else if (supportsItems && validItemRows.isEmpty) {
      AppSnackbar.error('Please add at least one valid item row.');
      return;
    } else if (supportsServices && validServiceRows.isEmpty) {
      AppSnackbar.error('Please add at least one valid service row.');
      return;
    }

    isSubmitting.value = true;
    try {
      final payload = _buildPayload(
        validItemRows,
        validServiceRows,
        validMixedServiceRows,
      );
      if (isEditing) {
        final recordId = _toInt(_editingPayload?['id']);
        final localId = recordId == null ? null : 'remote-$module-$recordId';
        if (recordId == null || localId == null) {
          throw Exception('Unable to edit this invoice right now.');
        }
        await repository.updateRecord(
          module: module,
          endpoint: '$endpoint/$recordId',
          localId: localId,
          serverId: recordId.toString(),
          payload: payload,
        );
      } else {
        await repository.createRecord(
          module: module,
          endpoint: endpoint,
          payload: payload,
        );
      }
      await _refreshList();
      Get.back<void>();
      AppSnackbar.success(
        isEditing
            ? '$title was updated locally and will sync when online.'
            : '$title was saved locally and will sync when online.',
      );
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
    final recordId = _toInt(_editingPayload?['id']);
    final currentNumber = draftInvoiceNumber.value.trim();
    final tempNumber = currentNumber.isNotEmpty
        ? currentNumber
        : '$temporaryPrefix-${DateTime.now().millisecondsSinceEpoch}';
    final currentStatus = (_editingPayload?['status'] ?? 'draft').toString();
    final currentStatusLabel =
        (_editingPayload?['status_label'] ?? 'Draft').toString();
    return <String, dynamic>{
      if (recordId case final currentRecordId?) 'id': currentRecordId,
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
      'status': currentStatus,
      'status_label': currentStatusLabel,
      'total': summaryTotal,
      'amount_paid': _toDouble(_editingPayload?['amount_paid']),
      'balance_due':
          summaryTotal - _toDouble(_editingPayload?['amount_paid']),
      'party': selectedParty.value == null
          ? null
          : <String, dynamic>{
              'id': selectedParty.value!.id,
              'name': selectedParty.value!.name,
            },
    };
  }

  int? _toInt(dynamic value) {
    if (value == null) {
      return null;
    }
    if (value is int) {
      return value;
    }
    return int.tryParse(value.toString());
  }

  double _toDouble(dynamic value) {
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value?.toString() ?? '') ?? 0;
  }

  String _shortDate(String? value) {
    if (value == null || value.trim().isEmpty) {
      return '';
    }
    return value.length >= 10 ? value.substring(0, 10) : value;
  }

  String _formatEditableNumber(dynamic value, {String fallback = ''}) {
    final parsed = _toDouble(value);
    if (parsed == 0 && (value == null || value.toString().trim().isEmpty)) {
      return fallback;
    }
    if (parsed == parsed.roundToDouble()) {
      return parsed.toStringAsFixed(0);
    }
    return parsed.toStringAsFixed(2);
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
  SalesInvoiceFormController(
    super.repository,
    super.lookupController, {
    super.initialPayload,
  });

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

class ServiceSalesInvoiceFormController extends BaseInvoiceFormController {
  ServiceSalesInvoiceFormController(
    super.repository,
    super.lookupController, {
    super.initialPayload,
  });

  @override
  String get title => 'Service Sale Invoice';

  @override
  String get module => 'service_sales_invoices';

  @override
  String get endpoint => ApiEndpoints.serviceSalesInvoices;

  @override
  String get partyType => 'debtor';

  @override
  String get serviceAccountType => 'income';

  @override
  String get temporaryPrefix => 'SRV';

  @override
  bool get supportsItems => false;

  @override
  bool get supportsServices => true;

  @override
  bool get isServiceInvoice => true;
}

class PurchaseInvoiceFormController extends BaseInvoiceFormController {
  PurchaseInvoiceFormController(
    super.repository,
    super.lookupController, {
    super.initialPayload,
  });

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
