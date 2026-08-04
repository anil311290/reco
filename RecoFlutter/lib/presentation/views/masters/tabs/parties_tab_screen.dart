import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/utils/app_alert_dialog.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../../data/repositories/settings/audit_logs_repository.dart';
import '../../../controllers/masters/parties_controller.dart';
import '../../../controllers/settings/audit_logs_controller.dart';
import '../../../widgets/common/custom_text_field.dart';
import '../forms/party_form_sheet.dart';
import '../history/party_history_screen.dart';
import '../widgets/masters_ui_components.dart';
import '../../settings/audit_logs_screen.dart';

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
              onChanged: (_) => controller.onSearchChanged(),
              onFilterTap: () => _openFilters(context),
              filterTooltip: 'Party filters',
              onExcelTap: controller.exportExcel,
              onPdfTap: controller.exportPdf,
            ),
            const SizedBox(height: 12),
            Expanded(
              child: PaginatedTablePane(
                hasMore: controller.hasMore,
                isLoadingMore: controller.isLoadingMore.value,
                loadedCount: controller.parties.length,
                totalCount: controller.total.value,
                onLoadMore: controller.loadMore,
                child: MastersTableShell(
                  isLoading: controller.isLoading.value,
                  emptyText: 'No parties found',
                  minWidth: 1080,
                  dataRowHeight: 80,
                  columns: <DataColumn2>[
                  masterColumn(
                    context,
                    '#',
                    fixedWidth: 52,
                    size: ColumnSize.S,
                  ),
                  masterColumn(context, 'Code', size: ColumnSize.M),
                  masterColumn(context, 'Name', size: ColumnSize.L),
                  masterColumn(context, 'Type', size: ColumnSize.M),
                  masterColumn(context, 'Linked Ledger', size: ColumnSize.L),
                  masterColumn(context, 'Mobile', size: ColumnSize.M),
                  masterColumn(context, 'Opening Balance', size: ColumnSize.M),
                  masterColumn(context, 'Status', fixedWidth: 120),
                  masterColumn(context, 'Actions', fixedWidth: 208),
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
                      DataCell(
                        Align(
                          alignment: Alignment.centerLeft,
                          child: Padding(
                            padding: const EdgeInsets.symmetric(vertical: 6),
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: <Widget>[
                                Text(
                                  item.linkedLedgerDisplay,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: Theme.of(context).textTheme.bodyMedium
                                      ?.copyWith(
                                        fontWeight: FontWeight.w600,
                                        fontSize: 13,
                                      ),
                                ),
                                const SizedBox(height: 4),
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 1,
                                  ),
                                  decoration: BoxDecoration(
                                    color: Theme.of(context)
                                        .colorScheme
                                        .surfaceContainerHighest,
                                    borderRadius: BorderRadius.circular(999),
                                    border: Border.all(
                                      color: Theme.of(context)
                                          .dividerColor
                                          .withValues(alpha: .6),
                                    ),
                                  ),
                                  child: Text(
                                    'Control Ledger',
                                    style: Theme.of(context)
                                        .textTheme
                                        .labelSmall
                                        ?.copyWith(
                                          fontWeight: FontWeight.w700,
                                          fontSize: 10.5,
                                          color: Theme.of(context)
                                              .colorScheme
                                              .onSurfaceVariant,
                                        ),
                                  ),
                                ),
                              ],
                            ),
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
                                icon: Icons.remove_red_eye_outlined,
                                tooltip: 'View',
                                color: const Color(0xFF64748B),
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
                                icon: Icons.history_rounded,
                                tooltip: 'Audit Logs',
                                color: const Color(0xFF38BDF8),
                                onTap: item.id == null
                                    ? null
                                    : () => _openAuditLogs(item),
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

  Future<void> _openAuditLogs(PartyEntity item) async {
    if (item.id == null) return;
    await Get.to(
      () => const AuditLogsScreen(),
      binding: BindingsBuilder(() {
        Get.put(
          AuditLogsController(
            Get.find<AuditLogsRepository>(),
            initialModule: 'parties',
            initialRecordId: item.id!.toString(),
          ),
        );
      }),
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
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: Theme.of(context).textTheme.labelMedium?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w700,
              fontSize: 11,
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
                            controller.clearFilters().then((_) {
                              if (context.mounted) {
                                Navigator.of(context).pop();
                              }
                            });
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
                            ).then((_) {
                              if (context.mounted) {
                                Navigator.of(context).pop();
                              }
                            });
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
