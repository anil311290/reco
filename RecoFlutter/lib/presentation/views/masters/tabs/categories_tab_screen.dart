import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/utils/app_alert_dialog.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/masters/categories_controller.dart';
import '../../../widgets/common/custom_text_field.dart';
import '../forms/category_form_sheet.dart';
import '../widgets/masters_ui_components.dart';

class CategoriesTabScreen extends GetView<CategoriesController> {
  const CategoriesTabScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Obx(
      () => MasterSectionPadding(
        child: Column(
          children: <Widget>[
            CompactSearchFilterBar(
              controller: controller.searchController,
              hint: 'Search category list...',
              onChanged: (_) => controller.onSearchChanged(),
              filterTooltip: 'Category filters',
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
                  emptyText: 'No categories found',
                  minWidth: 860,
                  columns: <DataColumn2>[
                  masterColumn(context, '#', fixedWidth: 52),
                  masterColumn(context, 'Name', size: ColumnSize.L),
                  masterColumn(context, 'Description', size: ColumnSize.L),
                  masterColumn(context, 'Sort Order', size: ColumnSize.S),
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
                      masterTextCell(item.name),
                      masterTextCell(
                        item.description.isEmpty ? '-' : item.description,
                      ),
                      masterTextCell('${item.sortOrder}'),
                      DataCell(
                        Center(
                          child: Transform.scale(
                            scale: .72,
                            child: CupertinoSwitch(
                              value: item.isActive,
                              onChanged: (value) =>
                                  controller.toggleStatus(item, value),
                            ),
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

  Future<void> _openForm(BuildContext context, {ItemCategoryEntity? entity}) async {
    final result = await Get.to(() => CategoryFormSheet(entity: entity));
    if (result == true) {
      await controller.refreshData(forceRemote: true);
    }
  }

  Future<void> _confirmDelete(ItemCategoryEntity item) async {
    final confirmed = await AppAlertDialog.confirmDelete(
      title: 'Delete Category',
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

class _MasterFilterSheet extends StatelessWidget {
  const _MasterFilterSheet({required this.controller});
  final CategoriesController controller;
  @override
  Widget build(BuildContext context) {
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
                    'Category Filters',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w600,
                        ),
                  ),
                  const SizedBox(height: 12),
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
                          child: const Text('Clear'),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: FilledButton(
                          onPressed: () {
                            controller.applyFilters(status: selectedStatus).then((_) {
                              if (context.mounted) {
                                Navigator.of(context).pop();
                              }
                            });
                          },
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
}
