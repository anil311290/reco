import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../data/models/transactions/transaction_entities.dart';
import '../../../../presentation/widgets/common/custom_text_field.dart';
import '../../../controllers/transactions/all_vouchers_controller.dart';
import '../../../controllers/transactions/base_transactions_tab_controller.dart';
import '../../../controllers/transactions/transactions_lookup_controller.dart';
import '../../masters/widgets/masters_ui_components.dart';

class TransactionTabContent<T extends BaseTransactionsTabController>
    extends GetView<T> {
  const TransactionTabContent({
    required this.emptyText,
    required this.columnsBuilder,
    required this.rowBuilder,
    super.key,
  });

  final String emptyText;
  final List<DataColumn2> Function(BuildContext context) columnsBuilder;
  final DataRow Function(BuildContext context, TransactionRecord item, int index)
      rowBuilder;

  @override
  Widget build(BuildContext context) {
    return Obx(
      () => MasterSectionPadding(
        child: Column(
          children: <Widget>[
            CompactSearchFilterBar(
              hint: controller.searchHint,
              controller: controller.searchController,
              onChanged: (_) => controller.refreshData(),
              onFilterTap: () => _openFilters(context),
              filterTooltip: 'Transaction filters',
            ),
            const SizedBox(height: 12),
            Expanded(
              child: MastersTableShell(
                isLoading: controller.isLoading.value,
                emptyText: emptyText,
                minWidth: 980,
                columns: columnsBuilder(context),
                rows: List<DataRow>.generate(
                  controller.filteredItems.length,
                  (index) => rowBuilder(
                    context,
                    controller.filteredItems[index],
                    index,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _openFilters(BuildContext context) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => _TransactionFilterSheet<T>(controller: controller),
    );
  }
}

class _TransactionFilterSheet<T extends BaseTransactionsTabController>
    extends StatelessWidget {
  const _TransactionFilterSheet({required this.controller});

  final T controller;

  @override
  Widget build(BuildContext context) {
    final lookups = Get.find<TransactionsLookupController>();
    var status = controller.selectedStatus.value;
    var partyId = controller.selectedPartyId.value;
    var voucherType = controller is AllVouchersController
        ? (controller as AllVouchersController).selectedType.value
        : 'All';
    final fromDateController = TextEditingController(
      text: controller.selectedFromDate.value,
    );
    final toDateController = TextEditingController(
      text: controller.selectedToDate.value,
    );

    return StatefulBuilder(
      builder: (context, setModalState) {
        return Material(
          color: Theme.of(context).cardColor,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
          child: SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  const MasterBottomSheetHandle(),
                  const SizedBox(height: 14),
                  Text(
                    'Filters',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 12),
                  CustomDropdown<String>(
                    label: 'Status',
                    value: status,
                    items: controller.statusOptions,
                    itemLabelBuilder: (value) => value == 'All'
                        ? 'All Status'
                        : _titleCase(value),
                    onChanged: (value) =>
                        setModalState(() => status = value ?? 'All'),
                  ),
                  if (controller is AllVouchersController)
                    CustomDropdown<String>(
                      label: 'Type',
                      value: voucherType,
                      items: (controller as AllVouchersController).typeOptions,
                      itemLabelBuilder: (value) => switch (value) {
                        'All' => 'All Types',
                        'income' => 'Sales',
                        'expense' => 'Purchase',
                        'journal' => 'Adjustment',
                        _ => _titleCase(value),
                      },
                      onChanged: (value) =>
                          setModalState(() => voucherType = value ?? 'All'),
                    ),
                  if (controller.supportsPartyFilter)
                    Obx(
                      () => CustomDropdown<int>(
                        label: 'Party',
                        value: partyId == 0 ? null : partyId,
                        items: lookups.parties.map((item) => item.id).toList(),
                        hint: 'Select party',
                        itemLabelBuilder: (value) {
                          final option = lookups.parties.firstWhere(
                            (item) => item.id == value,
                            orElse: () => const TransactionLookupOption(
                              id: 0,
                              label: '',
                            ),
                          );
                          return option.label;
                        },
                        onChanged: (value) =>
                            setModalState(() => partyId = value ?? 0),
                      ),
                    ),
                  CustomTextField(
                    label: 'From Date',
                    controller: fromDateController,
                    readOnly: true,
                    hintText: 'YYYY-MM-DD',
                    suffixIcon: Icons.calendar_today_outlined,
                    onTap: () async {
                      final selected = await showDatePicker(
                        context: context,
                        initialDate: DateTime.now(),
                        firstDate: DateTime(2000),
                        lastDate: DateTime(2100),
                      );
                      if (selected != null) {
                        fromDateController.text = _formatDate(selected);
                      }
                    },
                  ),
                  CustomTextField(
                    label: 'To Date',
                    controller: toDateController,
                    readOnly: true,
                    hintText: 'YYYY-MM-DD',
                    suffixIcon: Icons.calendar_today_outlined,
                    onTap: () async {
                      final selected = await showDatePicker(
                        context: context,
                        initialDate: DateTime.now(),
                        firstDate: DateTime(2000),
                        lastDate: DateTime(2100),
                      );
                      if (selected != null) {
                        toDateController.text = _formatDate(selected);
                      }
                    },
                  ),
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () async {
                            await controller.clearFilters();
                            if (context.mounted) {
                              Navigator.of(context).pop();
                            }
                          },
                          style: OutlinedButton.styleFrom(
                            minimumSize: const Size.fromHeight(48),
                          ),
                          child: const Text('Clear'),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: FilledButton(
                          onPressed: () async {
                            if (controller is AllVouchersController) {
                              await (controller as AllVouchersController)
                                  .applyAllVoucherFilters(
                                type: voucherType,
                                status: status,
                                fromDate: fromDateController.text.trim(),
                                toDate: toDateController.text.trim(),
                              );
                            } else {
                              await controller.applyFilters(
                                status: status,
                                partyId: partyId,
                                fromDate: fromDateController.text.trim(),
                                toDate: toDateController.text.trim(),
                              );
                            }
                            if (context.mounted) {
                              Navigator.of(context).pop();
                            }
                          },
                          style: FilledButton.styleFrom(
                            minimumSize: const Size.fromHeight(48),
                          ),
                          child: const Text('Apply'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  String _formatDate(DateTime value) {
    return '${value.year.toString().padLeft(4, '0')}-${value.month.toString().padLeft(2, '0')}-${value.day.toString().padLeft(2, '0')}';
  }

  String _titleCase(String value) {
    if (value.isEmpty) {
      return value;
    }
    return value
        .split('_')
        .where((part) => part.isNotEmpty)
        .map((part) => '${part[0].toUpperCase()}${part.substring(1)}')
        .join(' ');
  }
}
