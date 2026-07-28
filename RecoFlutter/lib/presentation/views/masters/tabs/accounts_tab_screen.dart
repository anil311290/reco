import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/utils/app_alert_dialog.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/masters/accounts_controller.dart';
import '../../../widgets/common/custom_text_field.dart';
import '../../reports/ledger_report_screen.dart';
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
              filterTooltip: 'Ledger filters',
              onFilterTap: () => _openFilters(context),
              onExcelTap: controller.exportExcel,
              onPdfTap: controller.exportPdf,
            ),
            const SizedBox(height: 12),
            Expanded(
              child: MastersTableShell(
                isLoading: controller.isLoading.value,
                emptyText: 'No accounts found',
                minWidth: 980,
                columns: <DataColumn2>[
                  masterColumn(context, 'Code', size: ColumnSize.S),
                  masterColumn(context, 'Name', size: ColumnSize.L),
                  masterColumn(context, 'Type', size: ColumnSize.M),
                  masterColumn(context, 'Mode', size: ColumnSize.S),
                  masterColumn(context, 'Opening Balance', size: ColumnSize.M),
                  masterColumn(context, 'Status', fixedWidth: 120),
                  masterColumn(context, 'Actions', fixedWidth: 170),
                ],
                rows: controller.filteredItems.map((item) {
                  return DataRow(
                    cells: <DataCell>[
                      masterTextCell(item.accountCode),
                      masterTextCell(item.accountName),
                      masterTextCell(item.accountType),
                      masterTextCell(
                        item.transactionMode.isEmpty
                            ? '-'
                            : item.transactionMode,
                      ),
                      masterTextCell(
                        '${item.openingBalance.toStringAsFixed(2)} ${item.balanceType.toUpperCase()}',
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
                                icon: Icons.assessment_outlined,
                                tooltip: 'Ledger Report',
                                color: const Color(0xFF2563EB),
                                onTap: item.id == null
                                    ? null
                                    : () => Get.to(
                                          () => LedgerReportScreen(
                                            initialAccountId: item.id,
                                          ),
                                        ),
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
                }).toList(),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _openForm(BuildContext context, {AccountEntity? entity}) async {
    await Get.to(() => AccountFormSheet(entity: entity));
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
                            controller.clearFilters();
                            Navigator.of(context).pop();
                          },
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
