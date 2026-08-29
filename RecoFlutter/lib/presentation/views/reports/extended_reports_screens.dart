import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_date_formatter.dart';
import '../../controllers/reports/extended_reports_controllers.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'settlement_details_sheet.dart';
import 'widgets/report_ui_components.dart';
import '../../widgets/common/custom_text_field.dart';

class UnappliedReceiptsReportScreen
    extends GetView<UnappliedReceiptsReportController> {
  const UnappliedReceiptsReportScreen({super.key});

  static const Color _primaryColor = Color(0xFF0D9488);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: ReportPageTitle(
          title: 'Unapplied Receipts',
          icon: FontAwesomeIcons.circleDollarToSlot.data,
          color: _primaryColor,
        ),
      ),
      body: Obx(() {
        if (controller.shouldShowInitialLoader) {
          return const ReportLoadingView();
        }
        final receipts = controller.listFor('receipts');
        final payments = controller.listFor('payments');

        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle:
                  'Receipts and payments not fully allocated to invoices.',
              icon: FontAwesomeIcons.sliders.data,
              iconColor: _primaryColor,
              child: Column(
                children: <Widget>[
                  ReportDateRangeRow(
                    fromController: controller.fromDateController,
                    toController: controller.toDateController,
                    onFromTap: () =>
                        _pickDate(context, controller.fromDateController),
                    onToTap: () =>
                        _pickDate(context, controller.toDateController),
                  ),
                  const SizedBox(height: 12),
                  ReportActionBar(
                    children: <Widget>[
                      ReportPrimaryButton(
                        label: 'Filter',
                        icon: FontAwesomeIcons.sliders.data,
                        onTap: controller.loadReport,
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: <Widget>[
                Expanded(
                  child: ReportStatCard(
                    label: 'Unapplied Receipts',
                    value: '${receipts.length}',
                    note: 'Customer receipts with free balance',
                    color: const Color(0xFF16A34A),
                    icon: FontAwesomeIcons.arrowDown.data,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: ReportStatCard(
                    label: 'Unapplied Payments',
                    value: '${payments.length}',
                    note: 'Supplier payments with free balance',
                    color: const Color(0xFFDC2626),
                    icon: FontAwesomeIcons.arrowUp.data,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            _SectionTable(
              title: 'Receipts',
              color: const Color(0xFF16A34A),
              rows: receipts,
              formatCurrency: controller.formatCurrency,
              formatDate: controller.formatDate,
            ),
            const SizedBox(height: 12),
            _SectionTable(
              title: 'Payments',
              color: const Color(0xFFDC2626),
              rows: payments,
              formatCurrency: controller.formatCurrency,
              formatDate: controller.formatDate,
            ),
          ],
        );
      }),
    );
  }

  Future<void> _pickDate(
    BuildContext context,
    TextEditingController target,
  ) async {
    final initial = AppDateFormatter.parse(target.text) ?? DateTime.now();
    final selected = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      target.text = AppDateFormatter.formatDisplay(selected);
    }
  }
}

class _SectionTable extends StatelessWidget {
  const _SectionTable({
    required this.title,
    required this.color,
    required this.rows,
    required this.formatCurrency,
    required this.formatDate,
  });

  final String title;
  final Color color;
  final List<Map<String, dynamic>> rows;
  final String Function(dynamic) formatCurrency;
  final String Function(String) formatDate;

  @override
  Widget build(BuildContext context) {
    return ReportSectionCard(
      title: title,
      icon: FontAwesomeIcons.tableList.data,
      iconColor: color,
      child: rows.isEmpty
          ? Padding(
              padding: const EdgeInsets.all(20),
              child: Center(
                child: Text(
                  'No unapplied ${title.toLowerCase()}',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                ),
              ),
            )
          : SizedBox(
              height: (42.0 + (rows.length * 52.0)).clamp(140.0, 420.0),
              child: MastersTableShell(
                isLoading: false,
                emptyText: 'No rows',
                minWidth: 900,
                columns: <DataColumn2>[
                  masterColumn(context, '#', fixedWidth: 48),
                  masterColumn(context, 'Voucher', size: ColumnSize.M),
                  masterColumn(context, 'Date', size: ColumnSize.S),
                  masterColumn(context, 'Party', size: ColumnSize.L),
                  masterColumn(context, 'Amount', size: ColumnSize.M),
                  masterColumn(context, 'Allocated', size: ColumnSize.M),
                  masterColumn(context, 'Unapplied', size: ColumnSize.M),
                ],
                rows: List<DataRow>.generate(rows.length, (index) {
                  final row = rows[index];
                  final party = row['party'] is Map
                      ? Map<String, dynamic>.from(row['party'] as Map)
                      : <String, dynamic>{};
                  return DataRow(
                    cells: <DataCell>[
                      masterTextCell('${index + 1}'),
                      DataCell(
                        Center(
                          child: ReportLinkText(
                            (row['voucher_number'] ?? '-').toString(),
                            onTap: row['voucher_id'] == null
                                ? null
                                : () => showPaymentSettlementDetails(
                                      voucherId: row['voucher_id'],
                                      title:
                                          (row['voucher_number'] ?? '').toString(),
                                    ),
                          ),
                        ),
                      ),
                      masterTextCell(
                        formatDate((row['voucher_date'] ?? '').toString()),
                      ),
                      masterTextCell((party['name'] ?? '-').toString()),
                      masterTextCell(formatCurrency(row['voucher_amount'])),
                      masterTextCell(formatCurrency(row['allocated_amount'])),
                      DataCell(
                        Center(
                          child: Text(
                            formatCurrency(row['unapplied_amount']),
                            style: TextStyle(
                              fontWeight: FontWeight.w800,
                              color: color,
                            ),
                          ),
                        ),
                      ),
                    ],
                  );
                }),
              ),
            ),
    );
  }
}

class StockRegisterReportScreen extends GetView<StockRegisterReportController> {
  const StockRegisterReportScreen({super.key});

  static const Color _primaryColor = Color(0xFF0284C7);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: ReportPageTitle(
          title: 'Stock Register',
          icon: FontAwesomeIcons.boxesStacked.data,
          color: _primaryColor,
        ),
      ),
      body: Obx(() {
        if (controller.shouldShowInitialLoader) {
          return const ReportLoadingView();
        }
        final data = controller.reportData['data'];
        final rows = controller.rows;
        final report = data is Map ? data : <String, dynamic>{};

        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle: 'Select an item to view stock movements for the period.',
              icon: FontAwesomeIcons.sliders.data,
              iconColor: _primaryColor,
              child: Column(
                children: <Widget>[
                  CustomDropdown<int>(
                    label: 'Item',
                    value: controller.itemId.value,
                    items: controller.itemOptions
                        .map((item) =>
                            int.tryParse(item['id']?.toString() ?? ''))
                        .whereType<int>()
                        .toList(),
                    itemLabelBuilder: (value) {
                      final item = controller.itemOptions.firstWhere(
                        (row) =>
                            int.tryParse(row['id']?.toString() ?? '') == value,
                        orElse: () => <String, dynamic>{},
                      );
                      return (item['text'] ??
                              item['name'] ??
                              item['label'] ??
                              'Item')
                          .toString();
                    },
                    onChanged: (value) => controller.itemId.value = value,
                  ),
                  ReportDateRangeRow(
                    fromController: controller.fromDateController,
                    toController: controller.toDateController,
                    onFromTap: () =>
                        _pickDate(context, controller.fromDateController),
                    onToTap: () =>
                        _pickDate(context, controller.toDateController),
                  ),
                  const SizedBox(height: 12),
                  ReportActionBar(
                    children: <Widget>[
                      ReportPrimaryButton(
                        label: 'Filter',
                        icon: FontAwesomeIcons.sliders.data,
                        onTap: controller.loadReport,
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: <Widget>[
                Expanded(
                  child: ReportStatCard(
                    label: 'In',
                    value: '${report['total_in'] ?? 0}',
                    note: 'Quantity in',
                    color: const Color(0xFF16A34A),
                    icon: FontAwesomeIcons.arrowDown.data,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ReportStatCard(
                    label: 'Out',
                    value: '${report['total_out'] ?? 0}',
                    note: 'Quantity out',
                    color: const Color(0xFFDC2626),
                    icon: FontAwesomeIcons.arrowUp.data,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ReportStatCard(
                    label: 'Closing',
                    value: '${report['closing_quantity'] ?? 0}',
                    note: 'Closing qty',
                    color: _primaryColor,
                    icon: FontAwesomeIcons.warehouse.data,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            ReportSectionCard(
              title: 'Movements',
              icon: FontAwesomeIcons.tableList.data,
              iconColor: _primaryColor,
              child: controller.itemId.value == null
                  ? const Padding(
                      padding: EdgeInsets.all(24),
                      child: Center(
                        child: Text('Select an item to load movements.'),
                      ),
                    )
                  : rows.isEmpty
                      ? const Padding(
                          padding: EdgeInsets.all(24),
                          child: Center(child: Text('No stock movements')),
                        )
                      : SizedBox(
                          height:
                              (42.0 + (rows.length * 52.0)).clamp(140.0, 480.0),
                          child: MastersTableShell(
                            isLoading: false,
                            emptyText: 'No movements',
                            minWidth: 1020,
                            columns: <DataColumn2>[
                              masterColumn(context, '#', fixedWidth: 48),
                              masterColumn(context, 'Date', size: ColumnSize.S),
                              masterColumn(context, 'Item', size: ColumnSize.M),
                              masterColumn(context, 'Movement', size: ColumnSize.S),
                              masterColumn(context, 'Reference', size: ColumnSize.M),
                              masterColumn(context, 'Party', size: ColumnSize.M),
                              masterColumn(context, 'In', size: ColumnSize.S),
                              masterColumn(context, 'Out', size: ColumnSize.S),
                              masterColumn(context, 'Balance', size: ColumnSize.S),
                            ],
                            rows: List<DataRow>.generate(rows.length, (i) {
                              final row = rows[i];
                              final partyName = row['party_name'] ??
                                  (row['party'] is Map
                                      ? row['party']['name']
                                      : null);
                              return DataRow(
                                cells: <DataCell>[
                                  masterTextCell('${i + 1}'),
                                  masterTextCell(
                                    controller.formatDate(
                                      (row['date'] ?? '').toString(),
                                    ),
                                  ),
                                  masterTextCell(
                                    (row['stock_reference'] ??
                                            row['item_name'] ??
                                            '-')
                                        .toString(),
                                  ),
                                  masterTextCell(
                                    (row['type_label'] ??
                                            row['type'] ??
                                            row['movement_type'] ??
                                            '-')
                                        .toString(),
                                  ),
                                  masterTextCell(
                                    (row['invoice_number'] ??
                                            row['document_number'] ??
                                            row['reference'] ??
                                            row['voucher_number'] ??
                                            '-')
                                        .toString(),
                                  ),
                                  masterTextCell((partyName ?? '-').toString()),
                                  masterTextCell(
                                    (row['qty_in'] ?? row['in'] ?? '0')
                                        .toString(),
                                  ),
                                  masterTextCell(
                                    (row['qty_out'] ?? row['out'] ?? '0')
                                        .toString(),
                                  ),
                                  masterTextCell(
                                    (row['running_qty'] ?? row['balance'] ?? '0')
                                        .toString(),
                                  ),
                                ],
                              );
                            }),
                          ),
                        ),
            ),
          ],
        );
      }),
    );
  }

  Future<void> _pickDate(
    BuildContext context,
    TextEditingController target,
  ) async {
    final initial = AppDateFormatter.parse(target.text) ?? DateTime.now();
    final selected = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      target.text = AppDateFormatter.formatDisplay(selected);
    }
  }
}

class SettlementAuditReportScreen
    extends GetView<SettlementAuditReportController> {
  const SettlementAuditReportScreen({super.key});

  static const Color _primaryColor = Color(0xFF4338CA);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: ReportPageTitle(
          title: 'Settlement Audit',
          icon: FontAwesomeIcons.fileCircleCheck.data,
          color: _primaryColor,
        ),
      ),
      body: Obx(() {
        if (controller.shouldShowInitialLoader) {
          return const ReportLoadingView();
        }
        final summary = controller.summary;
        final mappings = controller.mappings;

        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle: 'Payment–invoice mapping audit trail.',
              icon: FontAwesomeIcons.sliders.data,
              iconColor: _primaryColor,
              child: Column(
                children: <Widget>[
                  ReportDateRangeRow(
                    fromController: controller.fromDateController,
                    toController: controller.toDateController,
                    onFromTap: () =>
                        _pickDate(context, controller.fromDateController),
                    onToTap: () =>
                        _pickDate(context, controller.toDateController),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: CustomDropdown<String>(
                          label: 'Status',
                          value: controller.statusFilter.value,
                          items: SettlementAuditReportController.statusOptions
                              .map((e) => e.key)
                              .toList(),
                          itemLabelBuilder: (value) =>
                              SettlementAuditReportController.statusOptions
                                  .firstWhere(
                                    (e) => e.key == value,
                                    orElse: () => MapEntry(value, value),
                                  )
                                  .value,
                          onChanged: (value) {
                            if (value != null) {
                              controller.statusFilter.value = value;
                            }
                          },
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: CustomDropdown<String>(
                          label: 'Type',
                          value: controller.typeFilter.value,
                          items: SettlementAuditReportController.typeOptions
                              .map((e) => e.key)
                              .toList(),
                          itemLabelBuilder: (value) =>
                              SettlementAuditReportController.typeOptions
                                  .firstWhere(
                                    (e) => e.key == value,
                                    orElse: () => MapEntry(value, value),
                                  )
                                  .value,
                          onChanged: (value) {
                            if (value != null) {
                              controller.typeFilter.value = value;
                            }
                          },
                        ),
                      ),
                    ],
                  ),
                  ReportActionBar(
                    children: <Widget>[
                      ReportPrimaryButton(
                        label: 'Filter',
                        icon: FontAwesomeIcons.sliders.data,
                        onTap: controller.loadReport,
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: <Widget>[
                Expanded(
                  child: ReportStatCard(
                    label: 'Mappings',
                    value: '${summary['total_mappings'] ?? mappings.length}',
                    note: 'Total settlement links',
                    color: _primaryColor,
                    icon: FontAwesomeIcons.link.data,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ReportStatCard(
                    label: 'Allocated',
                    value: controller.formatCurrency(summary['total_allocated']),
                    note: 'Mapped amount',
                    color: const Color(0xFF2563EB),
                    icon: FontAwesomeIcons.sackDollar.data,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: <Widget>[
                Expanded(
                  child: ReportStatCard(
                    label: 'Settled',
                    value: controller.formatCurrency(summary['total_settled']),
                    note: 'Fully applied amount',
                    color: const Color(0xFF16A34A),
                    icon: FontAwesomeIcons.circleCheck.data,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ReportStatCard(
                    label: 'Outstanding',
                    value:
                        controller.formatCurrency(summary['total_outstanding']),
                    note: 'Still open after mapping',
                    color: const Color(0xFFDC2626),
                    icon: FontAwesomeIcons.hourglassHalf.data,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            ReportSectionCard(
              title: 'Mappings',
              icon: FontAwesomeIcons.tableList.data,
              iconColor: _primaryColor,
              child: mappings.isEmpty
                  ? const Padding(
                      padding: EdgeInsets.all(24),
                      child: Center(child: Text('No settlement mappings')),
                    )
                  : SizedBox(
                      height:
                          (42.0 + (mappings.length * 52.0)).clamp(140.0, 520.0),
                      child: MastersTableShell(
                        isLoading: false,
                        emptyText: 'No mappings',
                        minWidth: 1100,
                        columns: <DataColumn2>[
                          masterColumn(context, '#', fixedWidth: 48),
                          masterColumn(context, 'Voucher', size: ColumnSize.M),
                          masterColumn(context, 'Invoice', size: ColumnSize.M),
                          masterColumn(context, 'Party', size: ColumnSize.L),
                          masterColumn(context, 'Type', fixedWidth: 88),
                          masterColumn(context, 'Allocated', size: ColumnSize.M),
                          masterColumn(context, 'Settled', size: ColumnSize.M),
                          masterColumn(
                            context,
                            'Outstanding',
                            size: ColumnSize.M,
                          ),
                          masterColumn(context, 'Status', size: ColumnSize.S),
                          masterColumn(context, 'Date', size: ColumnSize.S),
                        ],
                        rows: List<DataRow>.generate(mappings.length, (i) {
                          final row = mappings[i];
                          final voucherNo = (row['payment_voucher_number'] ??
                                  row['voucher_number'] ??
                                  '-')
                              .toString();
                          final voucherId =
                              row['payment_voucher_id'] ?? row['voucher_id'];
                          return DataRow(
                            cells: <DataCell>[
                              masterTextCell('${i + 1}'),
                              DataCell(
                                Center(
                                  child: ReportLinkText(
                                    voucherNo,
                                    onTap: voucherId == null
                                        ? null
                                        : () => showPaymentSettlementDetails(
                                              voucherId: voucherId,
                                              title: voucherNo,
                                            ),
                                  ),
                                ),
                              ),
                              DataCell(
                                Center(
                                  child: ReportLinkText(
                                    (row['invoice_number'] ?? '-').toString(),
                                    onTap: row['invoice_id'] == null
                                        ? null
                                        : () => showInvoiceSettlementDetails(
                                              invoiceType:
                                                  (row['invoice_type'] ??
                                                          'sales')
                                                      .toString(),
                                              invoiceId: row['invoice_id'],
                                              title: (row['invoice_number'] ??
                                                      '')
                                                  .toString(),
                                            ),
                                  ),
                                ),
                              ),
                              masterTextCell(
                                (row['party_name'] ?? '-').toString(),
                              ),
                              masterTextCell(
                                (row['invoice_type'] ?? '-').toString(),
                              ),
                              masterTextCell(
                                controller.formatCurrency(
                                  row['amount_allocated'] ?? row['amount'],
                                ),
                              ),
                              masterTextCell(
                                controller.formatCurrency(row['amount_settled']),
                              ),
                              masterTextCell(
                                controller.formatCurrency(row['outstanding']),
                              ),
                              masterTextCell((row['status'] ?? '-').toString()),
                              masterTextCell(
                                controller.formatDate(
                                  (row['payment_date'] ??
                                          row['settled_at'] ??
                                          row['voucher_date'] ??
                                          row['created_at'] ??
                                          '')
                                      .toString(),
                                ),
                              ),
                            ],
                          );
                        }),
                      ),
                    ),
            ),
          ],
        );
      }),
    );
  }

  Future<void> _pickDate(
    BuildContext context,
    TextEditingController target,
  ) async {
    final initial = AppDateFormatter.parse(target.text) ?? DateTime.now();
    final selected = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      target.text = AppDateFormatter.formatDisplay(selected);
    }
  }
}
