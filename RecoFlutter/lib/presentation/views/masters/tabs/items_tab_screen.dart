import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/utils/app_alert_dialog.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/masters/items_controller.dart';
import '../../../widgets/common/custom_text_field.dart';
import '../forms/item_form_sheet.dart';
import '../history/item_history_screen.dart';
import '../widgets/masters_ui_components.dart';

class ItemsTabScreen extends GetView<ItemsController> {
  const ItemsTabScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Obx(
      () => MasterSectionPadding(
        child: Column(
          children: <Widget>[
            CompactSearchFilterBar(
              controller: controller.searchController,
              hint: 'Search by name, code, barcode, HSN...',
              onChanged: (_) => controller.onSearchChanged(),
              filterTooltip: 'Item filters',
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
                  emptyText: 'No items found',
                  minWidth: 1120,
                  columns: <DataColumn2>[
                  masterColumn(
                    context,
                    '#',
                    fixedWidth: 52,
                    size: ColumnSize.S,
                  ),
                  masterColumn(context, 'Code', size: ColumnSize.M),
                  masterColumn(context, 'Name', size: ColumnSize.L),
                  masterColumn(context, 'Category', size: ColumnSize.M),
                  masterColumn(context, 'Type', size: ColumnSize.S),
                  masterColumn(context, 'HSN/SAC', size: ColumnSize.S),
                  masterColumn(context, 'Selling Price', size: ColumnSize.M),
                  masterColumn(context, 'Stock', size: ColumnSize.S),
                  masterColumn(context, 'Status', fixedWidth: 120),
                  masterColumn(context, 'Actions', fixedWidth: 170),
                ],
                  rows: List<DataRow>.generate(controller.filteredItems.length, (
                  index,
                ) {
                  final item = controller.filteredItems[index];
                  return DataRow(
                    cells: <DataCell>[
                      masterTextCell('${index + 1}'),
                      masterTextCell(item.itemCode),
                      DataCell(
                        Center(
                          child: item.id == null
                              ? Text(
                                  item.name,
                                  textAlign: TextAlign.center,
                                  style: Theme.of(context).textTheme.bodyMedium
                                      ?.copyWith(
                                        fontWeight: FontWeight.w600,
                                      ),
                                )
                              : InkWell(
                                  borderRadius: BorderRadius.circular(8),
                                  onTap: () => _openDetails(item),
                                  child: Padding(
                                    padding: const EdgeInsets.symmetric(
                                      vertical: 4,
                                    ),
                                    child: Text(
                                      item.name,
                                      maxLines: 2,
                                      textAlign: TextAlign.center,
                                      overflow: TextOverflow.ellipsis,
                                      style: Theme.of(context)
                                          .textTheme
                                          .bodyMedium
                                          ?.copyWith(
                                            color: const Color(0xFF2563EB),
                                            fontWeight: FontWeight.w700,
                                            decoration:
                                                TextDecoration.underline,
                                            decorationColor:
                                                const Color(0xFF2563EB),
                                          ),
                                    ),
                                  ),
                                ),
                        ),
                      ),
                      masterTextCell(
                        item.categoryName.isEmpty ? '-' : item.categoryName,
                      ),
                      DataCell(
                        Center(
                          child: _ItemBadge(
                            label: item.type == 'service' ? 'Service' : 'Goods',
                            color: item.type == 'service'
                                ? const Color(0xFF0EA5E9)
                                : const Color(0xFF2E74F0),
                          ),
                        ),
                      ),
                      masterTextCell(
                        item.hsnSacCode.isEmpty ? 'NA' : item.hsnSacCode,
                      ),
                      masterTextCell('₹${item.sellingPrice.toStringAsFixed(2)}'),
                      masterTextCell(
                        item.type == 'service' || item.isStockable == false
                            ? '-'
                            : item.currentStock.toStringAsFixed(3),
                      ),
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
                                icon: Icons.receipt_long_outlined,
                                tooltip: 'History',
                                color: const Color(0xFF2563EB),
                                onTap: item.id == null
                                    ? null
                                    : () => _openDetails(item),
                              ),
                              const SizedBox(width: 8),
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

  Future<void> _openForm(BuildContext context, {ItemEntity? entity}) async {
    final result = await Get.to<bool>(() => ItemFormSheet(entity: entity));
    if (result == true) {
      await controller.refreshData();
    }
  }

  Future<void> _openDetails(ItemEntity item) async {
    if (item.id == null) return;
    await Get.to(
      () => ItemHistoryScreen(
        itemId: item.id!,
        seedItem: item,
      ),
    );
  }

  Future<void> _confirmDelete(ItemEntity item) async {
    final confirmed = await AppAlertDialog.confirmDelete(
      title: 'Delete Item',
      message: 'Are you sure you want to delete "${item.name}"?',
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

class _ItemBadge extends StatelessWidget {
  const _ItemBadge({
    required this.label,
    required this.color,
  });

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: Theme.of(context).textTheme.labelMedium?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w700,
            ),
      ),
    );
  }
}

class _MasterFilterSheet extends StatelessWidget {
  const _MasterFilterSheet({required this.controller});
  final ItemsController controller;
  @override
  Widget build(BuildContext context) {
    var selectedType = controller.selectedType.value;
    var selectedCategory = controller.selectedCategory.value;
    var selectedStatus = controller.selectedStatus.value;
    final categories = <String>[
      'All',
      ...controller.items
          .map((item) => item.categoryName)
          .where((value) => value.isNotEmpty)
          .toSet(),
    ];
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
                    'Item Filters',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w600,
                        ),
                  ),
                  const SizedBox(height: 12),
                  CustomDropdown<String>(
                    label: 'Type',
                    value: selectedType,
                    items: const <String>['All', 'goods', 'service'],
                    itemLabelBuilder: (value) =>
                        value == 'All' ? 'All Types' : value,
                    onChanged: (value) => setModalState(
                      () => selectedType = value ?? 'All',
                    ),
                    enableSearch: false,
                  ),
                  CustomDropdown<String>(
                    label: 'Category',
                    value: selectedCategory,
                    items: categories,
                    itemLabelBuilder: (value) => value,
                    onChanged: (value) => setModalState(
                      () => selectedCategory = value ?? 'All',
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
                              type: selectedType,
                              category: selectedCategory,
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
