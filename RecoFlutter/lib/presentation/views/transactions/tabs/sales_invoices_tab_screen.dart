import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/utils/app_date_formatter.dart';
import '../../../../data/models/transactions/transaction_entities.dart';
import '../../../controllers/transactions/sales_invoices_controller.dart';
import '../../masters/widgets/masters_ui_components.dart';
import '../details/transaction_detail_screen.dart';
import '../utils/invoice_transaction_actions.dart';
import '../widgets/transaction_tab_content.dart';
import '../widgets/transactions_ui_components.dart';

class SalesInvoicesTabScreen extends GetView<SalesInvoicesController> {
  const SalesInvoicesTabScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return TransactionTabContent<SalesInvoicesController>(
      emptyText: 'No sales invoices found',
      columnsBuilder: (context) => <DataColumn2>[
        masterColumn(context, 'Invoice #', size: ColumnSize.M),
        masterColumn(context, 'Date', size: ColumnSize.M),
        masterColumn(context, 'Customer', size: ColumnSize.L),
        masterColumn(context, 'Total', size: ColumnSize.M),
        masterColumn(context, 'Paid', size: ColumnSize.M),
        masterColumn(context, 'Balance', size: ColumnSize.M),
        masterColumn(context, 'Due Date', size: ColumnSize.M),
        masterColumn(context, 'Status', fixedWidth: 120),
        masterColumn(context, 'Actions', fixedWidth: 80),
      ],
      rowBuilder: (context, item, index) => DataRow(
        cells: <DataCell>[
          DataCell(
            InkWell(
              onTap: () async {
                final detailRecord =
                    await resolveTransactionDetailRecord(item);
                await Get.to(
                  () => TransactionDetailScreen(
                    record: detailRecord,
                    onPost: item.status == 'draft'
                        ? () async {
                            final updated = await postInvoice(item);
                            if (updated) Get.back<void>();
                          }
                        : null,
                    onEdit: _canEdit(item)
                        ? () => openInvoiceEditor(item)
                        : null,
                    onRecordPayment: _canPay(item)
                        ? () async {
                            final updated =
                                await recordInvoicePayment(item);
                            if (updated) Get.back<void>();
                          }
                        : null,
                    onCancel: item.status != 'cancelled'
                        ? () async {
                            final updated = await cancelInvoice(item);
                            if (updated) Get.back<void>();
                          }
                        : null,
                    onDelete: item.status == 'draft'
                        ? () => deleteTransactionRecord(
                              controller: controller,
                              record: item,
                              closeAfterDelete: true,
                            )
                        : null,
                    onPrint: () => printSalesInvoice(item),
                  ),
                );
                await controller.refreshData(forceRemote: true);
              },
              child: Center(
                child: Text(
                  item.number.isEmpty ? '-' : item.number,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.primary,
                    fontWeight: FontWeight.w700,
                    fontSize: 13,
                    decoration: TextDecoration.underline,
                    decorationColor: Theme.of(context).colorScheme.primary,
                  ),
                ),
              ),
            ),
          ),
          masterTextCell(_formatDate(item.date)),
          masterTextCell(item.partyName.isEmpty ? '-' : item.partyName),
          masterTextCell('₹${item.amount.toStringAsFixed(2)}'),
          masterTextCell('₹${item.amountPaid.toStringAsFixed(2)}'),
          masterTextCell('₹${item.balanceDue.toStringAsFixed(2)}'),
          masterTextCell(_formatDate(item.dueDate)),
          DataCell(Center(child: TransactionStatusChip(status: item.status))),
          DataCell(
            Center(
              child: PopupMenuButton<String>(
                icon: Icon(
                  Icons.more_horiz_rounded,
                  color: Theme.of(context).colorScheme.primary,
                ),
                onSelected: (value) async {
                  switch (value) {
                    case 'view':
                      final detailRecord =
                          await resolveTransactionDetailRecord(item);
                      await Get.to(
                        () => TransactionDetailScreen(
                          record: detailRecord,
                          onPost: item.status == 'draft'
                              ? () async {
                                  final updated = await postInvoice(item);
                                  if (updated) Get.back<void>();
                                }
                              : null,
                          onEdit: _canEdit(item)
                              ? () => openInvoiceEditor(item)
                              : null,
                          onRecordPayment: _canPay(item)
                              ? () async {
                                  final updated =
                                      await recordInvoicePayment(item);
                                  if (updated) Get.back<void>();
                                }
                              : null,
                          onCancel: item.status != 'cancelled'
                              ? () async {
                                  final updated = await cancelInvoice(item);
                                  if (updated) Get.back<void>();
                                }
                              : null,
                          onDelete: item.status == 'draft'
                              ? () => deleteTransactionRecord(
                                    controller: controller,
                                    record: item,
                                    closeAfterDelete: true,
                                  )
                              : null,
                          onPrint: () => printSalesInvoice(item),
                        ),
                      );
                      await controller.refreshData(forceRemote: true);
                      break;
                    case 'post':
                      if (await postInvoice(item)) {
                        await controller.refreshData(forceRemote: true);
                      }
                      break;
                    case 'payment':
                      if (await recordInvoicePayment(item)) {
                        await controller.refreshData(forceRemote: true);
                      }
                      break;
                    case 'edit':
                      await openInvoiceEditor(item);
                      await controller.refreshData(forceRemote: true);
                      break;
                  }
                },
                itemBuilder: (context) => <PopupMenuEntry<String>>[
                  const PopupMenuItem<String>(
                    value: 'view',
                    child: Text('View'),
                  ),
                  if (item.status == 'draft')
                    const PopupMenuItem<String>(
                      value: 'post',
                      child: Text('Post'),
                    ),
                  if (_canPay(item))
                    const PopupMenuItem<String>(
                      value: 'payment',
                      child: Text('Record Payment'),
                    ),
                  if (_canEdit(item))
                    const PopupMenuItem<String>(
                      value: 'edit',
                      child: Text('Edit'),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  bool _canEdit(TransactionRecord item) =>
      item.status != 'cancelled' &&
      item.status != 'paid' &&
      item.status != 'partial';

  bool _canPay(TransactionRecord item) =>
      item.status != 'cancelled' &&
      item.status != 'paid' &&
      item.status != 'draft';

  String _formatDate(String value) => AppDateFormatter.formatDisplay(value);
}
