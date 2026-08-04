import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';

import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/transactions/create/base_invoice_form_controller.dart';
import '../../../controllers/transactions/create/transaction_form_models.dart';
import '../../masters/forms/item_form_sheet.dart';
import '../../masters/forms/party_form_sheet.dart';
import '../../masters/forms/account_form_sheet.dart';
import '../../../widgets/common/custom_text_field.dart';
import 'widgets/transaction_form_components.dart';

class InvoiceFormScreen<T extends BaseInvoiceFormController> extends GetView<T> {
  const InvoiceFormScreen({super.key});

  String _partyOptionSearchText(InvoicePartyOption item) {
    if (item.isParty) {
      final type = (item.party?.type ?? '').toLowerCase();
      final typeLabel = type == 'creditor'
          ? 'Supplier Party'
          : type == 'debtor'
              ? 'Customer Party'
              : 'Party';
      return '${item.label} $typeLabel';
    }
    return '${item.label} Ledger Account Cash Bank OD';
  }

  Widget _buildPartyOptionMenuItem(
    BuildContext context,
    List<InvoicePartyOption> items,
    InvoicePartyOption item,
  ) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final index = items.indexOf(item);
    final previous = index > 0 ? items[index - 1] : null;
    final isParty = item.isParty;
    final type = (item.party?.type ?? '').toLowerCase();

    String? sectionTitle;
    if (item.isAccount && (previous == null || previous.isParty)) {
      sectionTitle = 'Ledger Accounts (Cash/Bank/OD)';
    } else if (isParty &&
        type == 'debtor' &&
        (previous == null ||
            !previous.isParty ||
            (previous.party?.type.toLowerCase() != 'debtor'))) {
      sectionTitle = 'Customers (Parties)';
    } else if (isParty &&
        type == 'creditor' &&
        (previous == null ||
            !previous.isParty ||
            (previous.party?.type.toLowerCase() != 'creditor'))) {
      sectionTitle = 'Suppliers (Parties)';
    }

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          if (sectionTitle != null) ...<Widget>[
            Text(
              sectionTitle,
              style: theme.textTheme.labelMedium?.copyWith(
                color: scheme.onSurfaceVariant,
                fontWeight: FontWeight.w800,
                letterSpacing: .4,
              ),
            ),
            const SizedBox(height: 8),
          ],
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            physics: const BouncingScrollPhysics(),
            child: Text(
              item.label,
              maxLines: 1,
              softWrap: false,
              style: theme.textTheme.bodyMedium?.copyWith(
                color: scheme.onSurface,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }

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
                        if (controller.isPurchaseInvoice) ...<Widget>[
                          CustomTextField(
                            label: 'Invoice Number',
                            controller: controller.invoiceNumberController,
                            readOnly: true,
                          ),
                          CustomTextField(
                            label: 'Supplier Invoice #',
                            controller: controller.supplierInvoiceController,
                            hintText: 'Supplier\'s ref',
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
                          _InlineQuickActions(
                            label: 'Supplier / Ledger',
                            primaryActionLabel: 'Quick Add Cash / Bank Ledger',
                            onPrimaryTap: () =>
                                _openQuickAddLedger(context, controller),
                            secondaryActionLabel: 'Quick Add Party',
                            onSecondaryTap: () =>
                                _openQuickAddParty(context, controller),
                          ),
                          CustomDropdown<InvoicePartyOption>(
                            label: 'Supplier / Ledger',
                            value: controller.selectedPartyOption.value,
                            items: controller.invoicePartyOptions,
                            itemLabelBuilder: (item) => item.label,
                            searchTextBuilder: _partyOptionSearchText,
                            menuItemBuilder: (context, item) =>
                                _buildPartyOptionMenuItem(
                                  context,
                                  controller.invoicePartyOptions,
                                  item,
                                ),
                            onChanged: (value) {
                              controller.selectedPartyOption.value = value;
                              controller.update();
                            },
                            requiredField: true,
                            isLoading:
                                controller.lookupController.isPartiesLoading.value ||
                                controller.lookupController
                                    .isCashBankAccountsLoading
                                    .value,
                            enabled:
                                !(controller.lookupController.isPartiesLoading.value ||
                                    controller.lookupController
                                        .isCashBankAccountsLoading
                                        .value),
                            hint: 'Select Supplier or Ledger Account',
                          ),
                          const SizedBox(height: 2),
                          Align(
                            alignment: Alignment.centerLeft,
                            child: Text(
                              'Choose an existing supplier party or select a ledger account for direct posting.',
                              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                color: Theme.of(context)
                                    .colorScheme
                                    .onSurfaceVariant,
                              ),
                            ),
                          ),
                          SizedBox(height: 8,),
                          CustomTextField(
                            label: 'Payment/Delivery Terms',
                            controller: controller.paymentTermsController,
                            hintText: 'e.g., Net 30, FOB',
                          ),
                          CustomTextField(
                            label: 'Notes',
                            controller: controller.notesController,
                            maxLines: 2,
                            hintText: '',
                          ),
                        ] else ...<Widget>[
                        CustomTextField(
                          label: 'Invoice Number',
                          controller: controller.invoiceNumberController,
                          readOnly: true,
                        ),
                        _InlineQuickActions(
                          label: 'Customer',
                          primaryActionLabel: 'Quick Add Cash / Bank Ledger',
                          onPrimaryTap: () =>
                              _openQuickAddLedger(context, controller),
                          secondaryActionLabel: 'Quick Add Party',
                          onSecondaryTap: () =>
                              _openQuickAddParty(context, controller),
                        ),
                        CustomDropdown<InvoicePartyOption>(
                          label: 'Customer',
                          value: controller.selectedPartyOption.value,
                          items: controller.invoicePartyOptions,
                          itemLabelBuilder: (item) => item.label,
                          searchTextBuilder: _partyOptionSearchText,
                          menuItemBuilder: (context, item) =>
                              _buildPartyOptionMenuItem(
                                context,
                                controller.invoicePartyOptions,
                                item,
                              ),
                          onChanged: (value) {
                            controller.selectedPartyOption.value = value;
                            controller.update();
                          },
                          requiredField: true,
                          isLoading:
                              controller.lookupController.isPartiesLoading.value ||
                              controller.lookupController
                                  .isCashBankAccountsLoading
                                  .value,
                          enabled:
                              !(controller.lookupController.isPartiesLoading.value ||
                                  controller.lookupController
                                      .isCashBankAccountsLoading
                                      .value),
                          hint: 'Select Customer',
                        ),
                          const SizedBox(height: 5),
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
      title: 'Line Items',
      action: Row(
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          OutlinedButton.icon(
            onPressed: () => _openQuickAddItem(context, controller),
            icon: const Icon(Icons.bolt_rounded, size: 16),
            label: const Text('Quick Add Item'),
            style: OutlinedButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
              side: BorderSide(
                color: Theme.of(context).colorScheme.primary,
              ),
              foregroundColor: Theme.of(context).colorScheme.primary,
              textStyle: Theme.of(context).textTheme.labelMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          const SizedBox(width: 8),
          FilledButton.tonalIcon(
            onPressed: controller.addItemRow,
            icon: const Icon(Icons.add_circle_outline_rounded, size: 16),
            label: const Text('Add Line'),
            style: FilledButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
              textStyle: Theme.of(context).textTheme.labelMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
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
              return Obx(() {
                if (controller.usesUnifiedSalesRows) {
                  final isCatalogLoading =
                      controller.lookupController.isItemsLoading.value ||
                      controller.lookupController.isServiceAccountsLoading.value;
                  return Column(
                    children: <Widget>[
                      CustomDropdown<InvoiceCatalogOption>(
                        label: 'Item / Service',
                        value: row.catalogOption.value,
                        items: controller.salesCatalogOptions,
                        itemLabelBuilder: (item) => item.label,
                        onChanged: (item) =>
                            controller.onSalesCatalogChanged(row, item),
                        hint: 'Select item or service',
                        requiredField: true,
                        isLoading: isCatalogLoading,
                        enabled: !isCatalogLoading,
                      ),
                      if (isCatalogLoading)
                        Align(
                          alignment: Alignment.centerLeft,
                          child: Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: Text(
                              'Loading item and service list...',
                              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                color: Theme.of(context)
                                    .colorScheme
                                    .onSurfaceVariant,
                              ),
                            ),
                          ),
                        ),
                    ],
                  );
                }

                final isItemsLoading =
                    controller.lookupController.isItemsLoading.value;
                return Column(
                  children: <Widget>[
                    CustomDropdown<ItemEntity>(
                      label: 'Item',
                      value: row.item.value,
                      items: controller.isPurchaseInvoice
                          ? controller.items
                                .where((item) => item.type != 'service')
                                .toList()
                          : controller.items,
                      itemLabelBuilder: (item) => item.name,
                      onChanged: (item) => controller.onItemChanged(row, item),
                      hint: 'Select Item',
                      requiredField: true,
                      isLoading: isItemsLoading,
                      enabled: !isItemsLoading,
                    ),
                    if (isItemsLoading)
                      Align(
                        alignment: Alignment.centerLeft,
                        child: Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: Text(
                            'Loading item list...',
                            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: Theme.of(context)
                                  .colorScheme
                                  .onSurfaceVariant,
                            ),
                          ),
                        ),
                      ),
                  ],
                );
              });
            },
          ),
          CustomTextField(
            label: 'Description',
            controller: row.descriptionController,
            hintText: 'Description',
            readOnly: true,
          ),
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
            label: 'Unit Price',
            controller: row.unitPriceController,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            inputFormatters: <TextInputFormatter>[
              FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
            ],
            onChanged: (_) => controller.update(),
          ),
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
              return Obx(() {
                final isServiceAccountsLoading =
                    controller.lookupController.isServiceAccountsLoading.value;
                return Column(
                  children: <Widget>[
                    CustomDropdown<LookupOption>(
                      label: 'Service Account',
                      value: value,
                      items: controller.serviceAccounts,
                      itemLabelBuilder: (item) => item.label,
                      onChanged: (account) =>
                          controller.onServiceAccountChanged(row, account),
                      requiredField: true,
                      isLoading: isServiceAccountsLoading,
                      enabled: !isServiceAccountsLoading,
                    ),
                    if (isServiceAccountsLoading)
                      Align(
                        alignment: Alignment.centerLeft,
                        child: Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: Text(
                            'Loading service account list...',
                            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: Theme.of(context)
                                  .colorScheme
                                  .onSurfaceVariant,
                            ),
                          ),
                        ),
                      ),
                  ],
                );
              });
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

class _InlineQuickActions extends StatelessWidget {
  const _InlineQuickActions({
    required this.label,
    required this.primaryActionLabel,
    required this.onPrimaryTap,
    this.secondaryActionLabel,
    this.onSecondaryTap,
  });

  final String label;
  final String primaryActionLabel;
  final VoidCallback onPrimaryTap;
  final String? secondaryActionLabel;
  final VoidCallback? onSecondaryTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            '$label *',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 4),
          Align(
            alignment: Alignment.centerRight,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: <Widget>[
                _QuickActionLink(
                  label: primaryActionLabel,
                  onTap: onPrimaryTap,
                ),
                if (secondaryActionLabel != null && onSecondaryTap != null) ...<Widget>[
                  const SizedBox(height: 2),
                  _QuickActionLink(
                    label: secondaryActionLabel!,
                    onTap: onSecondaryTap!,
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _QuickActionLink extends StatelessWidget {
  const _QuickActionLink({
    required this.label,
    required this.onTap,
  });

  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(6),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 2, vertical: 1),
        child: Text(
          label,
          textAlign: TextAlign.right,
          style: theme.textTheme.labelMedium?.copyWith(
            color: theme.colorScheme.primary,
            fontWeight: FontWeight.w700,
            decoration: TextDecoration.underline,
            decorationColor: theme.colorScheme.primary,
          ),
        ),
      ),
    );
  }
}

Future<void> _openQuickAddParty(
  BuildContext context,
  BaseInvoiceFormController controller,
) async {
  final result = await Get.to(
    () => PartyFormSheet(
      initialType: controller.isPurchaseInvoice ? 'creditor' : 'debtor',
    ),
  );
  if (result == null) {
    return;
  }
  await controller.lookupController.loadInvoiceLookups(
    partyType: controller.partyType,
    serviceAccountType: controller.serviceAccountType,
    includeItems: controller.supportsItems,
  );
  controller.update();
}

Future<void> _openQuickAddLedger(
  BuildContext context,
  BaseInvoiceFormController controller,
) async {
  final result = await Get.to(() => const AccountFormSheet());
  if (result == null) {
    return;
  }
  await controller.lookupController.loadInvoiceLookups(
    partyType: controller.partyType,
    serviceAccountType: controller.serviceAccountType,
    includeItems: controller.supportsItems,
  );
  controller.update();
}

Future<void> _openQuickAddItem(
  BuildContext context,
  BaseInvoiceFormController controller,
) async {
  final result = await Get.to(
    () => const ItemFormSheet(initialType: 'goods'),
  );
  if (result != null) {
    await controller.lookupController.loadInvoiceLookups(
      partyType: controller.partyType,
      serviceAccountType: controller.serviceAccountType,
      includeItems: controller.supportsItems,
    );
    controller.update();
  }
}
