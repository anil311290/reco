import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../data/models/transactions/transaction_entities.dart';
import '../../../controllers/transactions/all_vouchers_controller.dart';
import '../../masters/widgets/masters_ui_components.dart';
import '../details/transaction_detail_screen.dart';
import '../widgets/transaction_tab_content.dart';
import '../widgets/transactions_ui_components.dart';

class AllVouchersTabScreen extends GetView<AllVouchersController> {
  const AllVouchersTabScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return TransactionTabContent<AllVouchersController>(
      emptyText: 'No vouchers found',
      columnsBuilder: (context) => <DataColumn2>[
        masterColumn(context, '#', fixedWidth: 52, size: ColumnSize.S),
        masterColumn(context, 'Voucher No', size: ColumnSize.M),
        masterColumn(context, 'Date', size: ColumnSize.M),
        masterColumn(context, 'Type', size: ColumnSize.S),
        masterColumn(context, 'Party', size: ColumnSize.L),
        masterColumn(context, 'Amount', size: ColumnSize.M),
        masterColumn(context, 'Status', fixedWidth: 120),
        masterColumn(context, 'Actions', fixedWidth: 160),
      ],
      rowBuilder: _buildVoucherRow,
    );
  }

  DataRow _buildVoucherRow(
    BuildContext context,
    TransactionRecord item,
    int index,
  ) {
    return DataRow(
      cells: <DataCell>[
        masterTextCell('${index + 1}'),
        masterTextCell(item.number.isEmpty ? '-' : item.number),
        masterTextCell(_formatDate(item.date)),
        masterTextCell(item.typeLabel.isEmpty ? '-' : item.typeLabel),
        masterTextCell(item.partyName.isEmpty ? '-' : item.partyName),
        masterTextCell(_currency(item.amount)),
        DataCell(Center(child: TransactionStatusChip(status: item.status))),
        DataCell(
          Center(
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                MasterActionButton(
                  icon: Icons.remove_red_eye_outlined,
                  tooltip: 'View',
                  color: Theme.of(context).colorScheme.primary,
                  onTap: () => Get.to(
                    () => TransactionDetailScreen(
                      record: item,
                      onPost: item.status == 'draft'
                          ? () => controller.postRecord(item)
                          : null,
                      onCancel: item.status == 'posted'
                          ? () => controller.cancelRecord(item)
                          : null,
                      onDelete: item.status == 'draft'
                          ? () => controller.deleteRecord(item)
                          : null,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                if (item.status == 'draft') ...<Widget>[
                  MasterActionButton(
                    icon: Icons.check_circle_outline_rounded,
                    tooltip: 'Post',
                    color: const Color(0xFF16A36A),
                    onTap: () => controller.postRecord(item),
                  ),
                  const SizedBox(width: 8),
                ],
                if (item.status == 'posted') ...<Widget>[
                  MasterActionButton(
                    icon: Icons.cancel_outlined,
                    tooltip: 'Cancel',
                    color: const Color(0xFFF29B38),
                    onTap: () => controller.cancelRecord(item),
                  ),
                  const SizedBox(width: 8),
                ],
                if (item.status == 'draft')
                  MasterActionButton(
                    icon: Icons.delete_outline_rounded,
                    tooltip: 'Delete',
                    color: Theme.of(context).colorScheme.error,
                    onTap: () => controller.deleteRecord(item),
                  ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  String _currency(double value) => 'Rs ${value.toStringAsFixed(2)}';

  String _formatDate(String value) =>
      value.length >= 10 ? value.substring(0, 10) : value;
}
