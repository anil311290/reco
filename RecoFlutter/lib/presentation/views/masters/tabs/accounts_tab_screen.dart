import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/utils/app_alert_dialog.dart';
import '../../../../data/repositories/settings/audit_logs_repository.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/masters/accounts_controller.dart';
import '../../../controllers/settings/audit_logs_controller.dart';
import '../../../widgets/common/custom_text_field.dart';
import '../../settings/audit_logs_screen.dart';
import '../forms/account_form_sheet.dart';
import '../widgets/masters_ui_components.dart';

class AccountsTabScreen extends GetView<AccountsController> {
  const AccountsTabScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Obx(
      () => MasterSectionPadding(
        child: Column(
          children: <Widget>[
            CompactSearchFilterBar(
              controller: controller.searchController,
              hint: 'Search account list...',
              onChanged: (_) => controller.onSearchChanged(),
              filterTooltip: 'Ledger filters',
              onFilterTap: () => _openFilters(context),
              onExcelTap: controller.exportExcel,
              onPdfTap: controller.exportPdf,
            ),
            const SizedBox(height: 12),
            Expanded(
              child: PaginatedTablePane(
                hasMore: controller.hasMore,
                isLoadingMore: controller.isLoadingMore.value,
                loadedCount: controller.accounts.length,
                totalCount: controller.total.value,
                onLoadMore: controller.loadMore,
                child: MastersTableShell(
                  isLoading: controller.isLoading.value,
                  emptyText: 'No accounts found',
                  minWidth: 980,
                  columns: <DataColumn2>[
                  masterColumn(
                    context,
                    '#',
                    fixedWidth: 52,
                    size: ColumnSize.S,
                  ),
                  masterColumn(context, 'Code', size: ColumnSize.S),
                  masterColumn(context, 'Name', size: ColumnSize.L),
                  masterColumn(context, 'Type', size: ColumnSize.S),
                  masterColumn(context, 'Is Cash/Bank/OD', size: ColumnSize.M),
                  masterColumn(context, 'Status', fixedWidth: 120),
                  masterColumn(context, 'Actions', fixedWidth: 170),
                ],
                  rows: List<DataRow>.generate(controller.filteredItems.length, (
                  index,
                ) {
                  final item = controller.filteredItems[index];
                  final canDelete =
                      item.entrySource.toLowerCase() != 'system';

                  return DataRow(
                    cells: <DataCell>[
                      masterTextCell('${index + 1}'),
                      masterTextCell(item.accountCode),
                      masterTextCell(item.accountName),
                      DataCell(
                        Center(
                          child: _MasterBadge(
                            label: _labelize(item.accountType),
                            color: _accountTypeColor(item.accountType),
                          ),
                        ),
                      ),
                      DataCell(
                        Center(
                          child: item.accountType == 'asset'
                              ? _MasterBadge(
                                  label: item.isCashBankOd ? 'Yes' : 'No',
                                  color: item.isCashBankOd
                                      ? const Color(0xFF23955B)
                                      : const Color(0xFF64748B),
                                )
                              : Text(
                                  '-',
                                  style: Theme.of(context)
                                      .textTheme
                                      .bodyMedium
                                      ?.copyWith(
                                        color: Theme.of(context)
                                            .colorScheme
                                            .onSurfaceVariant,
                                      ),
                                ),
                        ),
                      ),
                      DataCell(
                        Center(
                          child: FittedBox(
                            fit: BoxFit.scaleDown,
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: <Widget>[
                                Transform.scale(
                                  scale: .68,
                                  child: CupertinoSwitch(
                                    value: item.isActive,
                                    onChanged: (value) =>
                                        controller.toggleStatus(item, value),
                                  ),
                                ),
                                const SizedBox(width: 2),
                                Text(
                                  item.isActive ? 'Active' : 'Inactive',
                                  style: Theme.of(context)
                                      .textTheme
                                      .bodySmall
                                      ?.copyWith(fontWeight: FontWeight.w600),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      DataCell(
                        Center(
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: <Widget>[
                              const SizedBox(width: 8),
                              // MasterActionButton(
                              //   icon: Icons.article_outlined,
                              //   tooltip: 'Logs',
                              //   color: const Color(0xFF38BDF8),
                              //   onTap: item.id == null
                              //       ? null
                              //       : () => Get.to(
                              //             () => const AuditLogsScreen(),
                              //             binding: BindingsBuilder(() {
                              //               Get.put(
                              //                 AuditLogsController(
                              //                   Get.find<AuditLogsRepository>(),
                              //                   initialModule: 'accounts',
                              //                   initialRecordId:
                              //                       item.id?.toString(),
                              //                 ),
                              //               );
                              //             }),
                              //           ),
                              // ),
                              // const SizedBox(width: 8),
                              MasterActionButton(
                                icon: Icons.edit_outlined,
                                tooltip: 'Edit',
                                color: Theme.of(context).colorScheme.primary,
                                onTap: () => _openForm(context, entity: item),
                              ),
                              if (canDelete) ...<Widget>[
                                const SizedBox(width: 8),
                                MasterActionButton(
                                  icon: Icons.delete_outline_rounded,
                                  tooltip: 'Delete',
                                  color: Theme.of(context).colorScheme.error,
                                  onTap: () => _confirmDelete(item),
                                ),
                              ],
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

  Future<void> _openForm(BuildContext context, {AccountEntity? entity}) async {
    final result = await Get.to(() => AccountFormSheet(entity: entity));
    if (result == true) {
      await controller.refreshData(forceRemote: true);
    }
  }

  Future<void> _confirmDelete(AccountEntity item) async {
    final confirmed = await AppAlertDialog.confirmDelete(
      title: 'Delete Ledger',
      message:
          'Are you sure you want to delete "${item.accountName}"?',
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

String _labelize(String value) {
  if (value.isEmpty) return value;
  return '${value[0].toUpperCase()}${value.substring(1)}';
}

Color _accountTypeColor(String type) {
  switch (type.toLowerCase()) {
    case 'asset':
      return const Color(0xFF2E74F0);
    case 'liability':
      return const Color(0xFFE24B5B);
    case 'income':
      return const Color(0xFF23955B);
    case 'expense':
      return const Color(0xFFFFB703);
    case 'equity':
      return const Color(0xFF0EA5A4);
    default:
      return const Color(0xFF64748B);
  }
}

class _MasterBadge extends StatelessWidget {
  const _MasterBadge({
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
  final AccountsController controller;
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
                    'Ledger Filters',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w600,
                        ),
                  ),
                  const SizedBox(height: 12),
                  CustomDropdown<String>(
                    label: 'Account Type',
                    value: selectedType,
                    items: const <String>[
                      'All',
                      'asset',
                      'liability',
                      'income',
                      'expense',
                      'equity',
                    ],
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
