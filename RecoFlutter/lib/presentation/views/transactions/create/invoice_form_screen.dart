import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';

import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/transactions/create/base_invoice_form_controller.dart';
import '../../../controllers/transactions/create/transaction_form_models.dart';
import '../../../widgets/common/custom_text_field.dart';
import 'widgets/transaction_form_components.dart';

class InvoiceFormScreen<T extends BaseInvoiceFormController> extends GetView<T> {
  const InvoiceFormScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          controller.isEditing ? 'Edit ${controller.title}' : controller.title,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      bottomNavigationBar: Obx(
        () => TransactionSubmitBar(
          text: controller.isEditing
              ? 'Update ${controller.title}'
              : 'Save ${controller.title}',
          isLoading: controller.isSubmitting.value,
          onPressed: controller.submit,
        ),
      ),
      body: Form(
        key: controller.formKey,
        child: GetBuilder<T>(
          builder: (_) {
            return SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: <Widget>[
                  TransactionFormSectionCard(
                    title: 'Invoice Details',
                    child: Column(
                      children: <Widget>[
                        CustomTextField(
                          label: 'Invoice Number',
                          controller: controller.invoiceNumberController,
                          readOnly: true,
                        ),
                        CustomDropdown<PartyEntity>(
                          label: controller.isPurchaseInvoice
                              ? 'Supplier'
                              : 'Customer',
                          value: controller.selectedParty.value,
                          items: controller.parties,
                          itemLabelBuilder: (item) {
                            final code = item.partyCode.trim();
                            if (code.isEmpty) {
                              return item.name;
                            }
                            return '${item.name} ($code)';
                          },
                          onChanged: (value) {
                            controller.selectedParty.value = value;
                            controller.update();
                          },
                          requiredField: true,
                          isLoading: controller.lookupController.isPartiesLoading.value,
                          enabled: !controller.lookupController.isPartiesLoading.value,
                        ),
                        if (controller.isPurchaseInvoice)
                          CustomTextField(
                            label: 'Supplier Invoice #',
                            controller: controller.supplierInvoiceController,
                            hintText: 'Supplier reference',
                          )
                        else
                          CustomTextField(
                            label: 'Reference #',
                            controller: controller.referenceController,
                            hintText: 'PO / REF',
                          ),
                        CustomTextField(
                          label: 'Invoice Date',
                          controller: controller.invoiceDateController,
                          readOnly: true,
                          requiredField: true,
                          suffixIcon: Icons.calendar_today_outlined,
                          onSuffixTap: () => controller.pickDate(
                            context,
                            controller.invoiceDateController,
                          ),
                          onTap: () => controller.pickDate(
                            context,
                            controller.invoiceDateController,
                          ),
                          validator: (value) =>
                              (value == null || value.trim().isEmpty)
                              ? 'Invoice date required'
                              : null,
                        ),
                        CustomTextField(
                          label: 'Due Date',
                          controller: controller.dueDateController,
                          readOnly: true,
                          requiredField: true,
                          suffixIcon: Icons.calendar_today_outlined,
                          onSuffixTap: () => controller.pickDate(
                            context,
                            controller.dueDateController,
                          ),
                          onTap: () => controller.pickDate(
                            context,
                            controller.dueDateController,
                          ),
                          validator: (value) =>
                              (value == null || value.trim().isEmpty)
                              ? 'Due date required'
                              : null,
                        ),
                        CustomTextField(
                          label: 'Payment / Delivery Terms',
                          controller: controller.paymentTermsController,
                          hintText: 'e.g. Net 30, FOB',
                        ),
                        CustomTextField(
                          label: 'Notes',
                          controller: controller.notesController,
                          maxLines: 3,
                          hintText: 'Additional notes',
                        ),
                      ],
                    ),
                  ),
                  if (controller.supportsItems) _ItemLinesSection<T>(),
                  if (controller.supportsServices && !controller.usesUnifiedSalesRows)
                    _ServiceLinesSection<T>(),
                  TransactionFormSectionCard(
                    title: 'Summary',
                    child: Column(
                      children: <Widget>[
                        TransactionAmountPill(
                          label: 'Subtotal',
                          value: 'Rs ${formatAmount(controller.summarySubtotal)}',
                        ),
                        const SizedBox(height: 10),
                        TransactionAmountPill(
                          label: 'Discount',
                          value: 'Rs ${formatAmount(controller.summaryDiscount)}',
                          valueColor: const Color(0xFFEF5B62),
                        ),
                        const SizedBox(height: 10),
                        TransactionAmountPill(
                          label: 'Tax',
                          value: 'Rs ${formatAmount(controller.summaryTax)}',
                          valueColor: const Color(0xFF2E7BEF),
                        ),
                        const SizedBox(height: 10),
                        TransactionAmountPill(
                          label: 'Total',
                          value: 'Rs ${formatAmount(controller.summaryTotal)}',
                          valueColor: Theme.of(context).colorScheme.primary,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}

class _ItemLinesSection<T extends BaseInvoiceFormController> extends GetView<T> {
  @override
  Widget build(BuildContext context) {
    return TransactionFormSectionCard(
      title: controller.usesUnifiedSalesRows ? 'Sales Lines' : 'Line Items',
      action: IconButton(
        onPressed: controller.addItemRow,
        icon: const Icon(Icons.add_circle_outline_rounded),
      ),
      child: Column(
        children: controller.itemRows
            .map((row) => _ItemRowCard<T>(row: row))
            .toList(),
      ),
    );
  }
}

class _ItemRowCard<T extends BaseInvoiceFormController> extends GetView<T> {
  const _ItemRowCard({required this.row});

  final InvoiceItemRowModel row;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Theme.of(context).dividerColor.withValues(alpha: .45),
        ),
      ),
      child: Column(
        children: <Widget>[
          ValueListenableBuilder<Object?>(
            valueListenable: controller.usesUnifiedSalesRows
                ? row.catalogOption
                : row.item,
            builder: (context, value, _) {
              if (controller.usesUnifiedSalesRows) {
                return CustomDropdown<InvoiceCatalogOption>(
                  label: 'Item / Service',
                  value: row.catalogOption.value,
                  items: controller.salesCatalogOptions,
                  itemLabelBuilder: (item) => item.label,
                  onChanged: (item) => controller.onSalesCatalogChanged(row, item),
                  hint: 'Select item or service',
                  requiredField: true,
                  isLoading: controller.lookupController.isItemsLoading.value ||
                      controller.lookupController.isServiceAccountsLoading.value,
                  enabled: !(controller.lookupController.isItemsLoading.value ||
                      controller.lookupController.isServiceAccountsLoading.value),
                );
              }

              return CustomDropdown<ItemEntity>(
                label: 'Item',
                value: row.item.value,
                items: controller.items,
                itemLabelBuilder: (item) => item.name,
                onChanged: (item) => controller.onItemChanged(row, item),
                requiredField: true,
                isLoading: controller.lookupController.isItemsLoading.value,
                enabled: !controller.lookupController.isItemsLoading.value,
              );
            },
          ),
          CustomTextField(
            label: 'Description',
            controller: row.descriptionController,
            hintText: row.isServiceSelection
                ? 'Service description'
                : 'Item description',
            readOnly: row.isServiceSelection,
          ),
          if (!row.isServiceSelection)
            CustomTextField(
              label: 'Quantity',
              controller: row.quantityController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              inputFormatters: <TextInputFormatter>[
                FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,3}')),
              ],
              onChanged: (_) => controller.update(),
            ),
          CustomTextField(
            label: row.isServiceSelection ? 'Amount' : 'Unit Price',
            controller: row.unitPriceController,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            inputFormatters: <TextInputFormatter>[
              FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
            ],
            onChanged: (_) => controller.update(),
          ),
          if (!row.isServiceSelection)
            CustomTextField(
              label: 'Discount %',
              controller: row.discountController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              inputFormatters: <TextInputFormatter>[
                FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
              ],
              onChanged: (_) => controller.update(),
            ),
          ValueListenableBuilder<TaxRateEntity?>(
            valueListenable: row.taxRate,
            builder: (context, value, _) {
              return CustomDropdown<TaxRateEntity>(
                label: 'Tax',
                value: value,
                items: controller.taxRates,
                itemLabelBuilder: (item) =>
                    '${item.taxName} (${formatAmount(item.taxRate)}%)',
                onChanged: (tax) {
                  row.taxRate.value = tax;
                  controller.update();
                },
                hint: 'No Tax',
                isLoading: controller.lookupController.isTaxRatesLoading.value,
                enabled: !controller.lookupController.isTaxRatesLoading.value,
              );
            },
          ),
          TransactionAmountPill(
            label: 'Line Total',
            value: 'Rs ${formatAmount(row.totalAmount)}',
          ),
          if (controller.itemRows.length > 1)
            Align(
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                onPressed: () => controller.removeItemRow(row),
                icon: const Icon(Icons.delete_outline_rounded),
                label: const Text('Remove'),
              ),
            ),
        ],
      ),
    );
  }
}

class _ServiceLinesSection<T extends BaseInvoiceFormController>
    extends GetView<T> {
  @override
  Widget build(BuildContext context) {
    return TransactionFormSectionCard(
      title: 'Service Lines',
      action: IconButton(
        onPressed: controller.addServiceRow,
        icon: const Icon(Icons.add_circle_outline_rounded),
      ),
      child: Column(
        children: controller.serviceRows
            .map((row) => _ServiceRowCard<T>(row: row))
            .toList(),
      ),
    );
  }
}

class _ServiceRowCard<T extends BaseInvoiceFormController> extends GetView<T> {
  const _ServiceRowCard({required this.row});

  final InvoiceServiceRowModel row;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Theme.of(context).dividerColor.withValues(alpha: .45),
        ),
      ),
      child: Column(
        children: <Widget>[
          ValueListenableBuilder<LookupOption?>(
            valueListenable: row.account,
            builder: (context, value, _) {
              return CustomDropdown<LookupOption>(
                label: 'Service Account',
                value: value,
                items: controller.serviceAccounts,
                itemLabelBuilder: (item) => item.label,
                onChanged: (account) =>
                    controller.onServiceAccountChanged(row, account),
                requiredField: true,
                isLoading: controller.lookupController.isServiceAccountsLoading.value,
                enabled: !controller.lookupController.isServiceAccountsLoading.value,
              );
            },
          ),
          CustomTextField(
            label: 'Description',
            controller: row.descriptionController,
            hintText: 'Service description',
          ),
          CustomTextField(
            label: 'Amount',
            controller: row.amountController,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            inputFormatters: <TextInputFormatter>[
              FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
            ],
            onChanged: (_) => controller.update(),
          ),
          ValueListenableBuilder<TaxRateEntity?>(
            valueListenable: row.taxRate,
            builder: (context, value, _) {
              return CustomDropdown<TaxRateEntity>(
                label: 'Tax',
                value: value,
                items: controller.taxRates,
                itemLabelBuilder: (item) =>
                    '${item.taxName} (${formatAmount(item.taxRate)}%)',
                onChanged: (tax) {
                  row.taxRate.value = tax;
                  controller.update();
                },
                hint: 'No Tax',
                isLoading: controller.lookupController.isTaxRatesLoading.value,
                enabled: !controller.lookupController.isTaxRatesLoading.value,
              );
            },
          ),
          TransactionAmountPill(
            label: 'Line Total',
            value: 'Rs ${formatAmount(row.totalAmount)}',
          ),
          if (controller.serviceRows.length > 1)
            Align(
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                onPressed: () => controller.removeServiceRow(row),
                icon: const Icon(Icons.delete_outline_rounded),
                label: const Text('Remove'),
              ),
            ),
        ],
      ),
    );
  }
}
