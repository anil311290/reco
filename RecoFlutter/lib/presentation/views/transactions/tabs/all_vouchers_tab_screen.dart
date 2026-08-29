import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/utils/app_date_formatter.dart';
import '../../../../data/models/transactions/transaction_entities.dart';
import '../../../controllers/transactions/all_vouchers_controller.dart';
import '../../masters/widgets/masters_ui_components.dart';
import '../details/transaction_detail_screen.dart';
import '../utils/invoice_transaction_actions.dart';
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
        masterColumn(context, 'Voucher Number', size: ColumnSize.L),
        masterColumn(context, 'Date', size: ColumnSize.M),
        masterColumn(context, 'Type', size: ColumnSize.M),
        masterColumn(context, 'Party', size: ColumnSize.L),
        masterColumn(context, 'Amount', size: ColumnSize.M),
        masterColumn(context, 'Status', fixedWidth: 120),
        masterColumn(context, 'Actions', fixedWidth: 220),
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
        DataCell(Center(child: VoucherTypeChip(type: item.type))),
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
                  color: const Color(0xFF38BDF8),
                  onTap: () async {
                    final detailRecord =
                        await resolveTransactionDetailRecord(item);
                    await Get.to(
                      () => TransactionDetailScreen(
                        record: detailRecord,
                        onPost: item.status == 'draft'
                            ? () => controller.postRecord(item)
                            : null,
                        onCancel: item.status == 'posted'
                            ? () => controller.cancelRecord(item)
                            : null,
                        onEdit: canEditVoucherRecord(item)
                            ? () => openVoucherEditor(item)
                            : null,
                        onDelete: item.status == 'draft'
                            ? () => deleteTransactionRecord(
                                  controller: controller,
                                  record: item,
                                  closeAfterDelete: true,
                                )
                            : null,
                        onPrint: () => printVoucher(item),
                      ),
                    );
                    await controller.refreshData(forceRemote: true);
                  },
                ),
                const SizedBox(width: 8),
                if (canEditVoucherRecord(item)) ...<Widget>[
                  MasterActionButton(
                    icon: Icons.edit_outlined,
                    tooltip: 'Edit',
                    color: Theme.of(context).colorScheme.primary,
                    onTap: () async {
                      await openVoucherEditor(item);
                      await controller.refreshData(forceRemote: true);
                    },
                  ),
                  const SizedBox(width: 8),
                ],
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
                MasterActionButton(
                  icon: Icons.picture_as_pdf_outlined,
                  tooltip: 'PDF',
                  color: const Color(0xFFDC2626),
                  onTap: () => printVoucher(item),
                ),
                if (item.status == 'draft') ...<Widget>[
                  const SizedBox(width: 8),
                  MasterActionButton(
                    icon: Icons.delete_outline_rounded,
                    tooltip: 'Delete',
                    color: Theme.of(context).colorScheme.error,
                    onTap: () => deleteTransactionRecord(
                      controller: controller,
                      record: item,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ],
    );
  }

  String _currency(double value) => '₹${value.toStringAsFixed(2)}';

  String _formatDate(String value) {
    return AppDateFormatter.formatDisplay(value);
  }
}
