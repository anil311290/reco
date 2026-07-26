import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../data/models/masters/master_entities.dart';
import '../../../controllers/masters/item_history_controller.dart';
import '../../../widgets/common/custom_text_field.dart';
import '../widgets/masters_ui_components.dart';

class ItemHistoryScreen extends StatefulWidget {
  const ItemHistoryScreen({
    required this.itemId,
    this.seedItem,
    super.key,
  });

  final int itemId;
  final ItemEntity? seedItem;

  @override
  State<ItemHistoryScreen> createState() => _ItemHistoryScreenState();
}

class _ItemHistoryScreenState extends State<ItemHistoryScreen> {
  late final String _tag;
  late final ItemHistoryController controller;

  @override
  void initState() {
    super.initState();
    _tag = 'item-history-${widget.itemId}';
    controller = Get.put(
      ItemHistoryController(
        Get.find(),
        itemId: widget.itemId,
        seedItem: widget.seedItem,
      ),
      tag: _tag,
    );
  }

  @override
  void dispose() {
    if (Get.isRegistered<ItemHistoryController>(tag: _tag)) {
      Get.delete<ItemHistoryController>(tag: _tag);
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Item History',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      body: Obx(
        () => ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            _buildItemDetailCard(theme),
            const SizedBox(height: 16),
            _buildFilterRow(theme),
            const SizedBox(height: 16),
            _buildHistorySection(theme),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterRow(ThemeData theme) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: theme.dividerColor.withValues(alpha: .4)),
      ),
      child: Row(
        children: <Widget>[
          Expanded(
            child: CustomTextField(
              controller: controller.fromDateController,
              label: 'From Date',
              hintText: 'YYYY-MM-DD',
              readOnly: true,
              onTap: () => _pickDate(controller.fromDateController),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: CustomTextField(
              controller: controller.toDateController,
              label: 'To Date',
              hintText: 'YYYY-MM-DD',
              readOnly: true,
              onTap: () => _pickDate(controller.toDateController),
            ),
          ),
          const SizedBox(width: 10),
          SizedBox(
            height: 48,
            child: FilledButton(
              onPressed: controller.loadHistory,
              child: const Text('Apply'),
            ),
          ),
        ],
      ),
    );
  }

  // ─────────────────────────────────────────────
  // Item Detail Card (matches web item-summary-grid)
  // ─────────────────────────────────────────────
  Widget _buildItemDetailCard(ThemeData theme) {
    final item = controller.item.value;

    if (item == null) {
      return const Center(child: CircularProgressIndicator());
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: theme.dividerColor.withValues(alpha: .4)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      item.name,
                      style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${item.itemCode} • ${item.type.toUpperCase()}',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: item.isActive ? Colors.green.withValues(alpha: .12) : Colors.grey.withValues(alpha: .12),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  item.isActive ? 'Active' : 'Inactive',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: item.isActive ? Colors.green : Colors.grey,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Wrap(
            spacing: 16,
            runSpacing: 12,
            children: <Widget>[
              _detailTile(theme, 'Code', item.itemCode),
              _detailTile(theme, 'Type', item.type.toUpperCase()),
              _detailTile(theme, 'Category', item.categoryName.isEmpty ? '-' : item.categoryName),
              _detailTile(theme, 'HSN / SAC', item.hsnSacCode.isEmpty ? '-' : item.hsnSacCode),
              _detailTile(theme, 'Unit', item.unit.isEmpty ? '-' : item.unit),
              _detailTile(theme, 'Purchase Price', controller.formatCurrency(item.purchasePrice)),
              _detailTile(theme, 'Selling Price', controller.formatCurrency(item.sellingPrice)),
              _detailTile(theme, 'Tax Rate', item.taxLabel.isEmpty ? '-' : item.taxLabel),
              if (item.type == 'goods') ...<Widget>[
                _detailTile(theme, 'Opening Stock', controller.formatQuantity(item.openingStock)),
                _detailTile(theme, 'Current Stock', controller.formatQuantity(item.currentStock)),
              ],
              _detailTile(theme, 'Total Purchases', controller.formatCurrency(controller.totalPurchaseAmount.value)),
              _detailTile(theme, 'Total Sales', controller.formatCurrency(controller.totalSalesAmount.value)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _detailTile(ThemeData theme, String label, String value) {
    return SizedBox(
      width: 140,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(label, style: TextStyle(fontSize: 11, color: theme.colorScheme.onSurfaceVariant, fontWeight: FontWeight.w500)),
          const SizedBox(height: 2),
          Text(value, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }

  // ─────────────────────────────────────────────
  // History Section (matches web)
  // ─────────────────────────────────────────────
  Widget _buildHistorySection(ThemeData theme) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text(
          'Stock & Transaction History',
          style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 4),
        Text(
          'Qty In ${controller.formatQuantity(controller.totalIn.value)}'
          ' • Qty Out ${controller.formatQuantity(controller.totalOut.value)}'
          ' • Closing ${controller.formatQuantity(controller.closingQty.value)}',
          style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
        ),
        const SizedBox(height: 12),
        Wrap(
          spacing: 10,
          runSpacing: 10,
          children: <Widget>[
            _summaryChip(
              theme,
              'Total Purchases',
              controller.formatCurrency(controller.totalPurchaseAmount.value),
              const Color(0xFF15803D),
            ),
            _summaryChip(
              theme,
              'Total Sales',
              controller.formatCurrency(controller.totalSalesAmount.value),
              const Color(0xFF2563EB),
            ),
            _summaryChip(
              theme,
              'Closing Qty',
              controller.formatQuantity(controller.closingQty.value),
              const Color(0xFF7C3AED),
            ),
          ],
        ),
        const SizedBox(height: 12),
        _buildHistoryTable(theme),
        const SizedBox(height: 12),
        _buildPaginationFooter(theme),
      ],
    );
  }

  Widget _buildHistoryTable(ThemeData theme) {
    final rows = controller.transactions;

    if (rows.isEmpty && !controller.isLoading.value) {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.all(32),
        decoration: BoxDecoration(
          color: theme.cardColor,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: theme.dividerColor.withValues(alpha: .4)),
        ),
        child: Center(
          child: Text(
            'No transactions found for this item.',
            style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant),
          ),
        ),
      );
    }

    final tableRows = <DataRow>[
      ...List<DataRow>.generate(rows.length, (index) {
        final row = rows[index];
        return DataRow(
          cells: <DataCell>[
            masterTextCell(controller.formatDate((row['date'] ?? '').toString())),
            DataCell(Center(child: _typeBadge(theme, row))),
            masterTextCell((row['invoice_number'] ?? '-').toString()),
            masterTextCell((row['party_name'] ?? '-').toString()),
            DataCell(Center(child: Text(_fmtQty(row['qty_in']), textAlign: TextAlign.center, style: const TextStyle(fontSize: 13)))),
            DataCell(Center(child: Text(_fmtQty(row['qty_out']), textAlign: TextAlign.center, style: const TextStyle(fontSize: 13)))),
            DataCell(Center(child: Text(_fmtRate(row['rate']), textAlign: TextAlign.center, style: const TextStyle(fontSize: 13)))),
            DataCell(Center(child: Text(_fmtAmount(row['amount']), textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)))),
            DataCell(Center(child: Text(_fmtQty(row['running_qty']), textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)))),
          ],
        );
      }),
      // Total Footer Row (matches web <tfoot>)
      DataRow(
        color: WidgetStatePropertyAll(theme.colorScheme.surfaceContainerHighest.withValues(alpha: .5)),
        cells: <DataCell>[
          const DataCell(Text('')),
          const DataCell(Text('')),
          const DataCell(Text('')),
          DataCell(Center(child: Text('Total', style: TextStyle(fontWeight: FontWeight.w700, color: theme.colorScheme.onSurfaceVariant, fontSize: 13)))),
          DataCell(Center(child: Text(controller.formatQuantity(controller.totalIn.value), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)))),
          DataCell(Center(child: Text(controller.formatQuantity(controller.totalOut.value), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)))),
          const DataCell(Text('')),
          const DataCell(Text('')),
          DataCell(Center(child: Text(controller.formatQuantity(controller.closingQty.value), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)))),
        ],
      ),
    ];

    final calculatedHeight = 42.0 + ((rows.length + 1) * 52.0);
    final tableHeight = calculatedHeight.clamp(200.0, 550.0);

    return SizedBox(
      height: tableHeight,
      child: MastersTableShell(
        isLoading: controller.isLoading.value,
        emptyText: 'No related invoice rows found',
        minWidth: 1180,
        columns: <DataColumn2>[
          masterColumn(context, 'Date'),
          masterColumn(context, 'Type', fixedWidth: 90),
          masterColumn(context, 'Invoice #'),
          masterColumn(context, 'Party', size: ColumnSize.M),
          masterColumn(context, 'Qty In'),
          masterColumn(context, 'Qty Out'),
          masterColumn(context, 'Rate'),
          masterColumn(context, 'Amount'),
          masterColumn(context, 'Balance Qty'),
        ],
        rows: tableRows,
      ),
    );
  }

  Widget _buildPaginationFooter(ThemeData theme) {
    if (controller.totalRecords.value <= 0) {
      return const SizedBox.shrink();
    }

    return Row(
      children: <Widget>[
        Expanded(
          child: Text(
            'Showing ${controller.firstRecordIndex} to ${controller.lastRecordIndex} of ${controller.totalRecords.value} entries',
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
        ),
        OutlinedButton(
          onPressed: controller.currentPage.value > 1
              ? () => controller.loadHistory(page: controller.currentPage.value - 1)
              : null,
          child: const Text('Prev'),
        ),
        const SizedBox(width: 8),
        FilledButton.tonal(
          onPressed: controller.currentPage.value < controller.lastPage.value
              ? () => controller.loadHistory(page: controller.currentPage.value + 1)
              : null,
          child: const Text('Next'),
        ),
      ],
    );
  }

  Widget _summaryChip(ThemeData theme, String label, String value, Color accent) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: accent.withValues(alpha: .08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: accent.withValues(alpha: .14)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            label,
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            value,
            style: theme.textTheme.titleSmall?.copyWith(
              color: accent,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }

  Widget _typeBadge(ThemeData theme, Map<String, dynamic> row) {
    final type = (row['type'] ?? '').toString();
    final label = (row['type_label'] ?? '-').toString();
    final Color background;
    final Color foreground;
    switch (type) {
      case 'sale':
        background = const Color(0xFFDBEAFE);
        foreground = const Color(0xFF1D4ED8);
        break;
      case 'purchase':
        background = const Color(0xFFDCFCE7);
        foreground = const Color(0xFF15803D);
        break;
      default:
        background = theme.colorScheme.surfaceContainerHighest;
        foreground = theme.colorScheme.onSurfaceVariant;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: theme.textTheme.bodySmall?.copyWith(
          color: foreground,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }

  // ── Formatters ──
  String _fmtQty(dynamic value) {
    final q = value is num ? value.toDouble() : double.tryParse('$value') ?? 0;
    return q > 0 ? controller.formatQuantity(q) : '—';
  }

  String _fmtRate(dynamic value) {
    final r = value is num ? value.toDouble() : double.tryParse('$value') ?? 0;
    return r > 0 ? controller.formatCurrency(r) : '—';
  }

  String _fmtAmount(dynamic value) {
    final a = value is num ? value.toDouble() : double.tryParse('$value') ?? 0;
    return a > 0 ? controller.formatCurrency(a) : '—';
  }

  Future<void> _pickDate(TextEditingController target) async {
    final selected = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      target.text = selected.toIso8601String().substring(0, 10);
    }
  }
}
