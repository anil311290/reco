import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/utils/app_alert_dialog.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/masters/tax_rates_controller.dart';
import '../../../widgets/common/custom_text_field.dart';
import '../forms/tax_rate_form_sheet.dart';
import '../widgets/masters_ui_components.dart';

class TaxRatesTabScreen extends GetView<TaxRatesController> {
  const TaxRatesTabScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Obx(
      () => MasterSectionPadding(
        child: Column(
          children: <Widget>[
            CompactSearchFilterBar(
              controller: controller.searchController,
              hint: 'Search tax list...',
              onChanged: (_) => controller.onSearchChanged(),
              filterTooltip: 'Tax filters',
              onFilterTap: () => _openFilters(context),
              onExcelTap: controller.exportExcel,
              onPdfTap: controller.exportPdf,
            ),
            const SizedBox(height: 12),
            Expanded(
              child: PaginatedTablePane(
                hasMore: controller.hasMore,
                isLoadingMore: controller.isLoadingMore.value,
                loadedCount: controller.items.length,
                totalCount: controller.total.value,
                onLoadMore: controller.loadMore,
                child: MastersTableShell(
                  isLoading: controller.isLoading.value,
                  emptyText: 'No taxes found',
                  minWidth: 960,
                  columns: <DataColumn2>[
                  masterColumn(context, '#', fixedWidth: 52),
                  masterColumn(context, 'Tax Code', size: ColumnSize.M),
                  masterColumn(context, 'Tax Name', size: ColumnSize.L),
                  masterColumn(context, 'Category', size: ColumnSize.M),
                  masterColumn(context, 'Type', size: ColumnSize.M),
                  masterColumn(context, 'Rate (%)', size: ColumnSize.S),
                  masterColumn(context, 'Status', fixedWidth: 120),
                  masterColumn(context, 'Actions', fixedWidth: 120),
                  ],
                  rows: List<DataRow>.generate(controller.filteredItems.length, (
                  index,
                ) {
                  final item = controller.filteredItems[index];
                  return DataRow(
                    cells: <DataCell>[
                      masterTextCell('${index + 1}'),
                      masterTextCell(item.taxCode),
                      masterTextCell(item.taxName),
                      masterTextCell(item.taxCategory),
                      masterTextCell(item.taxType),
                      masterTextCell(item.taxRate.toStringAsFixed(2)),
                      DataCell(
                        Center(
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: <Widget>[
                              Transform.scale(
                                scale: .72,
                                child: CupertinoSwitch(
                                  value: item.isActive,
                                  onChanged: (value) =>
                                      controller.toggleStatus(item, value),
                                ),
                              ),
                              const SizedBox(width: 4),
                              Text(item.isActive ? 'Active' : 'Inactive'),
                            ],
                          ),
                        ),
                      ),
                      DataCell(
                        Center(
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: <Widget>[
                              MasterActionButton(
                                icon: Icons.edit_outlined,
                                tooltip: 'Edit',
                                color: Theme.of(context).colorScheme.primary,
                                onTap: () => _openForm(context, entity: item),
                              ),
                              const SizedBox(width: 8),
                              MasterActionButton(
                                icon: Icons.delete_outline_rounded,
                                tooltip: 'Delete',
                                color: Theme.of(context).colorScheme.error,
                                onTap: () => _confirmDelete(item),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  );
                  }),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _openForm(BuildContext context, {TaxRateEntity? entity}) async {
    final result = await Get.to(() => TaxRateFormSheet(entity: entity));
    if (result == true) {
      await controller.refreshData(forceRemote: true);
    }
  }

  Future<void> _confirmDelete(TaxRateEntity item) async {
    final confirmed = await AppAlertDialog.confirmDelete(
      title: 'Delete Tax Rate',
      message: 'Are you sure you want to delete "${item.taxName}"?',
    );
    if (confirmed) {
      await controller.deleteItem(item);
    }
  }

  void _openFilters(BuildContext context) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => _MasterFilterSheet(controller: controller),
    );
  }
}

class _MasterFilterSheet extends StatelessWidget {
  const _MasterFilterSheet({required this.controller});
  final TaxRatesController controller;
  @override
  Widget build(BuildContext context) {
    var selectedCategory = controller.selectedCategory.value;
    var selectedType = controller.selectedType.value;
    var selectedStatus = controller.selectedStatus.value;
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
                    'Tax Filters',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w600,
                        ),
                  ),
                  const SizedBox(height: 12),
                  CustomDropdown<String>(
                    label: 'Category',
                    value: selectedCategory,
                    items: const <String>[
                      'All',
                      'GST',
                      'CGST',
                      'SGST',
                      'IGST',
                      'TDS',
                      'TCS',
                      'CESS',
                      'OTHER',
                    ],
                    itemLabelBuilder: (value) =>
                        value == 'All' ? 'All Categories' : value,
                    onChanged: (value) => setModalState(
                      () => selectedCategory = value ?? 'All',
                    ),
                    enableSearch: false,
                  ),
                  CustomDropdown<String>(
                    label: 'Type',
                    value: selectedType,
                    items: const <String>['All', 'addition', 'deduction'],
                    itemLabelBuilder: (value) =>
                        value == 'All' ? 'All Types' : value,
                    onChanged: (value) => setModalState(
                      () => selectedType = value ?? 'All',
                    ),
                    enableSearch: false,
                  ),
                  CustomDropdown<String>(
                    label: 'Status',
                    value: selectedStatus,
                    items: const <String>['All', 'Active', 'Inactive'],
                    itemLabelBuilder: (value) => value,
                    onChanged: (value) => setModalState(
                      () => selectedStatus = value ?? 'All',
                    ),
                    enableSearch: false,
                  ),
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () {
                            controller.clearFilters().then((_) {
                              if (context.mounted) {
                                Navigator.of(context).pop();
                              }
                            });
                          },
                          style: OutlinedButton.styleFrom(
                            minimumSize: const Size.fromHeight(45),
                            side: BorderSide(
                              color: Theme.of(context).colorScheme.primary,
                              width: 1.2,
                            ),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: const Text(
                            'Clear',
                            style: TextStyle(fontWeight: FontWeight.w700),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: FilledButton(

                          onPressed: () {
                            controller.applyFilters(
                              category: selectedCategory,
                              type: selectedType,
                              status: selectedStatus,
                            ).then((_) {
                              if (context.mounted) {
                                Navigator.of(context).pop();
                              }
                            });
                          },
                          style: FilledButton.styleFrom(
                            minimumSize: const Size.fromHeight(45),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: const Text(
                            'Apply',
                            style: TextStyle(fontWeight: FontWeight.w800),
                          ),
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
}
