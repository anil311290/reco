import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/utils/app_alert_dialog.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/masters/parties_controller.dart';
import '../../../widgets/common/custom_text_field.dart';
import '../forms/party_form_sheet.dart';
import '../history/party_history_screen.dart';
import '../widgets/masters_ui_components.dart';

class PartiesTabScreen extends GetView<PartiesController> {
  const PartiesTabScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Obx(
      () => MasterSectionPadding(
        child: Column(
          children: <Widget>[
            CompactSearchFilterBar(
              hint: 'Search by name, code, or mobile...',
              controller: controller.searchController,
              onFilterTap: () => _openFilters(context),
              filterTooltip: 'Party filters',
              onExcelTap: controller.exportExcel,
              onPdfTap: controller.exportPdf,
            ),

            const SizedBox(height: 12),
            Expanded(
              child: MastersTableShell(
                isLoading: controller.isLoading.value,
                emptyText: 'No parties found',
                minWidth: 980,
                columns: <DataColumn2>[
                  masterColumn(
                    context,
                    '#',
                    fixedWidth: 52,
                    size: ColumnSize.S,
                  ),
                  masterColumn(context, 'Code', size: ColumnSize.M),
                  masterColumn(context, 'Name', size: ColumnSize.L),
                  masterColumn(context, 'Type', size: ColumnSize.S),
                  masterColumn(context, 'Mobile', size: ColumnSize.M),
                  masterColumn(context, 'Opening Balance', size: ColumnSize.M),
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
                      masterTextCell(item.partyCode),
                      DataCell(
                        Align(
                          alignment: Alignment.centerLeft,
                          child: item.id == null
                              ? Text(
                                  item.name,
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
                      DataCell(
                        Center(
                          child: _PartyBadge(
                            label: item.type == 'debtor' ? 'Debtor' : 'Creditor',
                            color: item.type == 'debtor'
                                ? const Color(0xFF23955B)
                                : const Color(0xFFE24B5B),
                          ),
                        ),
                      ),
                      masterTextCell(item.mobile.isEmpty ? '-' : item.mobile),
                      masterTextCell(
                        '₹${item.openingBalance.toStringAsFixed(2)}',
                      ),
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
          ],
        ),
      ),
    );
  }

  Future<void> _openForm(BuildContext context, {PartyEntity? entity}) async {
    final result = await Get.to(() => PartyFormSheet(entity: entity));
    if (result == true) {
      await controller.refreshData(forceRemote: true);
    }
  }

  Future<void> _openDetails(PartyEntity item) async {
    if (item.id == null) return;
    await Get.to(
      () => PartyHistoryScreen(
        partyId: item.id!,
        seedParty: item,
      ),
    );
  }

  Future<void> _confirmDelete(PartyEntity item) async {
    final confirmed = await AppAlertDialog.confirmDelete(
      title: 'Delete Party',
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
      builder: (_) => _PartyFilterSheet(controller: controller),
    );
  }
}

class _PartyBadge extends StatelessWidget {
  const _PartyBadge({
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

class _PartyFilterSheet extends StatelessWidget {
  const _PartyFilterSheet({required this.controller});

  final PartiesController controller;

  @override
  Widget build(BuildContext context) {
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
                    'Party Filters',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w600,
                        ),
                  ),
                  const SizedBox(height: 12),
                  CustomDropdown<String>(
                    label: 'Party Type',
                    value: selectedType,
                    items: const <String>['All', 'debtor', 'creditor'],
                    itemLabelBuilder: (value) => switch (value) {
                      'debtor' => 'Debtor',
                      'creditor' => 'Creditor',
                      _ => 'All Types',
                    },
                    onChanged: (value) => setModalState(
                      () => selectedType = value ?? 'All',
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
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () {
                            controller.clearFilters();
                            Navigator.of(context).pop();
                          },
                          style: OutlinedButton.styleFrom(
                            minimumSize: const Size.fromHeight(48),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                          child: const Text('Clear'),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: FilledButton(
                          onPressed: () {
                            controller.applyFilters(
                              type: selectedType,
                              status: selectedStatus,
                            );
                            Navigator.of(context).pop();
                          },
                          style: FilledButton.styleFrom(
                            minimumSize: const Size.fromHeight(48),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(8),
                            ),
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
}
