import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../controllers/transactions/service_sales_invoices_controller.dart';
import '../../masters/widgets/masters_ui_components.dart';
import '../details/transaction_detail_screen.dart';
import '../widgets/transaction_tab_content.dart';
import '../widgets/transactions_ui_components.dart';

class ServiceSalesInvoicesTabScreen
    extends GetView<ServiceSalesInvoicesController> {
  const ServiceSalesInvoicesTabScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return TransactionTabContent<ServiceSalesInvoicesController>(
      emptyText: 'No service sales invoices found',
      columnsBuilder: (context) => <DataColumn2>[
        masterColumn(context, 'Invoice #', size: ColumnSize.M),
        masterColumn(context, 'Date', size: ColumnSize.M),
        masterColumn(context, 'Customer', size: ColumnSize.L),
        masterColumn(context, 'Amount', size: ColumnSize.M),
        masterColumn(context, 'Status', fixedWidth: 120),
        masterColumn(context, 'Actions', fixedWidth: 96),
      ],
      rowBuilder: (context, item, index) => DataRow(
        cells: <DataCell>[
          masterTextCell(item.number.isEmpty ? '-' : item.number),
          masterTextCell(item.date.length >= 10 ? item.date.substring(0, 10) : item.date),
          masterTextCell(item.partyName.isEmpty ? '-' : item.partyName),
          masterTextCell('Rs ${item.amount.toStringAsFixed(2)}'),
          DataCell(Center(child: TransactionStatusChip(status: item.status))),
          DataCell(
            Center(
              child: MasterActionButton(
                icon: Icons.remove_red_eye_outlined,
                tooltip: 'View',
                color: Theme.of(context).colorScheme.primary,
                onTap: () => Get.to(
                  () => TransactionDetailScreen(record: item),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
