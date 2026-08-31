import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../data/models/masters/master_entities.dart';
import '../../../../data/models/transactions/transaction_entities.dart';
import '../../../controllers/masters/item_history_controller.dart';
import '../history/party_history_screen.dart';
import '../../reports/widgets/report_ui_components.dart';
import '../../transactions/details/transaction_detail_screen.dart';
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
          'Item Details',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      body: Obx(
        () => ListView(
          padding: const EdgeInsets.all(10),
          children: <Widget>[
            _buildItemDetailCard(theme),
            const SizedBox(height: 12),
            _buildHistorySection(theme),
          ],
        ),
      ),
    );
  }
  Widget _buildItemDetailCard(ThemeData theme) {
    final item = controller.item.value;

    if (item == null) {
      return const Center(child: CircularProgressIndicator());
    }

    final accentColor = item.type == 'service'
        ? const Color(0xFF0284C7)
        : theme.colorScheme.primary;
    final isGoods = item.type == 'goods';

    final fields = <_ItemDetailField>[
      _ItemDetailField('Code', item.itemCode),
      _ItemDetailField('Type', _titleCase(item.type)),
      _ItemDetailField(
        'Category',
        item.categoryName.isEmpty ? '-' : item.categoryName,
      ),
      _ItemDetailField(
        'HSN / SAC',
        item.hsnSacCode.isEmpty ? '-' : item.hsnSacCode,
      ),
      _ItemDetailField(
        'Unit',
        item.unit.isEmpty ? '-' : item.unit.toUpperCase(),
      ),
      _ItemDetailField(
        'Purchase Price',
        isGoods ? _plainCurrency(item.purchasePrice) : '-',
      ),
      _ItemDetailField(
        'Selling Price',
        _plainCurrency(item.sellingPrice),
      ),
      _ItemDetailField(
        'Tax Rate',
        item.taxLabel.isEmpty ? '-' : item.taxLabel,
      ),
      _ItemDetailField(
        'Status',
        item.isActive ? 'Active' : 'Inactive',
        isBadge: true,
        badgeColor: item.isActive
            ? const Color(0xFF15803D)
            : const Color(0xFF6B7280),
      ),
      _ItemDetailField(
        'Opening Stock',
        isGoods
            ? '${controller.formatQuantity(item.openingStock)} ${item.unit}'
            : '-',
      ),
      _ItemDetailField(
        'Current Stock',
        isGoods
            ? '${controller.formatQuantity(item.currentStock)} ${item.unit}'
            : '-',
      ),
      _ItemDetailField(
        'Total Purchases',
        _plainCurrency(controller.totalPurchaseAmount.value),
      ),
      _ItemDetailField(
        'Total Sales',
        _plainCurrency(controller.totalSalesAmount.value),
      ),
    ];

    return Column(
      children: <Widget>[
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            gradient: LinearGradient(
              colors: <Color>[
                accentColor.withValues(alpha: .12),
                theme.colorScheme.surface,
              ],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            border: Border.all(
              color: accentColor.withValues(alpha: .18),
            ),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: theme.colorScheme.surface,
                  borderRadius: BorderRadius.circular(16),
                ),
                alignment: Alignment.center,
                child: Icon(
                  item.type == 'service'
                      ? Icons.design_services_rounded
                      : Icons.inventory_2_rounded,
                  color: accentColor,
                  size: 26,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      item.name,
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${item.itemCode} · ${_titleCase(item.type)}',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    if (item.description.trim().isNotEmpty) ...<Widget>[
                      const SizedBox(height: 8),
                      Text(
                        item.description.trim(),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                          height: 1.35,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: theme.cardColor,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(
              color: theme.dividerColor.withValues(alpha: .35),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Text(
                'Item Details',
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 12),
              LayoutBuilder(
                builder: (context, constraints) {
                  final crossAxis = constraints.maxWidth >= 720
                      ? 4
                      : constraints.maxWidth >= 460
                          ? 3
                          : 2;
                  final spacing = 10.0;
                  final cellWidth =
                      (constraints.maxWidth - (spacing * (crossAxis - 1))) /
                          crossAxis;
                  return Wrap(
                    spacing: spacing,
                    runSpacing: spacing,
                    children: fields
                        .map(
                          (field) => SizedBox(
                            width: cellWidth,
                            child: _detailTile(theme, field),
                          ),
                        )
                        .toList(),
                  );
                },
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _detailTile(ThemeData theme, _ItemDetailField field) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest.withValues(alpha: .35),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            field.label,
            style: theme.textTheme.labelSmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 4),
          if (field.isBadge)
            Container(
              padding: const EdgeInsets.symmetric(
                horizontal: 10,
                vertical: 3,
              ),
              decoration: BoxDecoration(
                color: (field.badgeColor ?? const Color(0xFF6B7280))
                    .withValues(alpha: .14),
                borderRadius: BorderRadius.circular(999),
              ),
              child: Text(
                field.value,
                style: TextStyle(
                  fontSize: 11.5,
                  fontWeight: FontWeight.w800,
                  color: field.badgeColor,
                ),
              ),
            )
          else
            Text(
              field.value.isEmpty ? '-' : field.value,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildHistorySection(ThemeData theme) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: theme.dividerColor.withValues(alpha: .35)),
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
                      'Stock & Transaction History',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Qty In ${controller.formatQuantity(controller.totalIn.value)}'
                      ' • Qty Out ${controller.formatQuantity(controller.totalOut.value)}'
                      ' • Closing ${controller.formatQuantity(controller.closingQty.value)}',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                        fontSize: 11.5,
                      ),
                    ),
                  ],
                ),
              ),
              Row(
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  _ExportActionChip(
                    label: 'Excel',
                    icon: Icons.table_view_rounded,
                    color: const Color(0xFF15803D),
                    onTap: controller.exportExcel,
                  ),
                  const SizedBox(width: 8),
                  _ExportActionChip(
                    label: 'PDF',
                    icon: Icons.picture_as_pdf_rounded,
                    color: const Color(0xFFDC2626),
                    onTap: controller.exportPdf,
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 12),
          _buildHistoryTable(theme),
          const SizedBox(height: 12),
          _buildPaginationFooter(theme),
        ],
      ),
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
          borderRadius: BorderRadius.circular(18),
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
            masterTextCell(_formatWebDate((row['date'] ?? '').toString())),
            DataCell(Center(child: _typeBadge(theme, row))),
            DataCell(
              _buildLinkedText(
                theme,
                label: (row['invoice_number'] ?? '—').toString(),
                onTap: _invoiceRecord(row) == null
                    ? null
                    : () => Get.to(
                          () => TransactionDetailScreen(
                            record: _invoiceRecord(row)!,
                          ),
                        ),
              ),
            ),
            DataCell(
              _buildLinkedText(
                theme,
                label: (row['party_name'] ?? '—').toString(),
                onTap: _partyId(row) == null
                    ? null
                    : () => Get.to(
                          () => PartyHistoryScreen(
                            partyId: _partyId(row)!,
                          ),
                        ),
              ),
            ),
            DataCell(Center(child: Text(_fmtQty(row['qty_in']), textAlign: TextAlign.center, style: const TextStyle(fontSize: 13)))),
            DataCell(Center(child: Text(_fmtQty(row['qty_out']), textAlign: TextAlign.center, style: const TextStyle(fontSize: 13)))),
            DataCell(Center(child: Text(_fmtRatePlain(row['rate']), textAlign: TextAlign.center, style: const TextStyle(fontSize: 13)))),
            DataCell(Center(child: Text(_fmtAmountPlain(row['amount']), textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)))),
            DataCell(Center(child: Text(controller.formatQuantity(_num(row['running_qty'])), textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)))),
          ],
        );
      }),
      // Total Footer Row (matches web <tfoot>)
      DataRow(
        color: reportTotalRowColor(context),
        cells: <DataCell>[
          const DataCell(Text('')),
          const DataCell(Text('')),
          const DataCell(Text('')),
          DataCell(Center(child: Text('Total', style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13)))),
          DataCell(Center(child: Text(controller.formatQuantity(controller.totalIn.value), style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13)))),
          DataCell(Center(child: Text(controller.formatQuantity(controller.totalOut.value), style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13)))),
          const DataCell(Text('')),
          const DataCell(Text('')),
          DataCell(Center(child: Text(controller.formatQuantity(controller.closingQty.value), style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13)))),
        ],
      ),
    ];

    final calculatedHeight = 42.0 + ((rows.length + 1) * 52.0);
    final tableHeight = calculatedHeight.clamp(200.0, 550.0);

    return SizedBox(
      height: tableHeight,
        child: MastersTableShell(
          isLoading: controller.isLoading.value,
          emptyText: 'No transactions found for this item.',
          minWidth: 1180,
          columns: <DataColumn2>[
            masterColumn(context, 'Date'),
            masterColumn(context, 'Type', fixedWidth: 130),
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
          style: OutlinedButton.styleFrom(
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          child: const Text('Prev'),
        ),
        const SizedBox(width: 8),
        FilledButton.tonal(
          onPressed: controller.currentPage.value < controller.lastPage.value
              ? () => controller.loadHistory(page: controller.currentPage.value + 1)
              : null,
          style: FilledButton.styleFrom(
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          child: const Text('Next'),
        ),
      ],
    );
  }

  Widget _typeBadge(ThemeData theme, Map<String, dynamic> row) {
    final type = (row['type'] ?? '').toString();
    final label = (row['type_label'] ?? '-').toString();
    final Color color;
    switch (type) {
      case 'sale':
        color = const Color(0xFF3B82F6);
        break;
      case 'purchase':
        color = const Color(0xFF10B981);
        break;
      case 'opening':
        color = const Color(0xFF6366F1);
        break;
      default:
        color = const Color(0xFF6B7280);
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .14),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w800,
          fontSize: 11.5,
        ),
      ),
    );
  }

  // ── Formatters ──
  String _fmtQty(dynamic value) {
    final q = value is num ? value.toDouble() : double.tryParse('$value') ?? 0;
    return q > 0 ? controller.formatQuantity(q) : '—';
  }

  String _plainCurrency(num value) => '₹ ${value.toStringAsFixed(2)}';

  String _fmtRatePlain(dynamic value) {
    final r = _num(value);
    return r > 0 ? r.toStringAsFixed(2) : '—';
  }

  String _fmtAmountPlain(dynamic value) {
    final a = _num(value);
    return a > 0 ? a.toStringAsFixed(2) : '—';
  }

  double _num(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse('$value') ?? 0;
  }

  String _titleCase(String value) {
    if (value.isEmpty) return value;
    return value[0].toUpperCase() + value.substring(1);
  }

  String _formatWebDate(String value) {
    final parsed = DateTime.tryParse(value);
    if (parsed == null) {
      return controller.formatDate(value);
    }
    const months = <String>[
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'May',
      'Jun',
      'Jul',
      'Aug',
      'Sep',
      'Oct',
      'Nov',
      'Dec',
    ];
    final day = parsed.day.toString().padLeft(2, '0');
    return '$day ${months[parsed.month - 1]} ${parsed.year}';
  }

  Widget _buildLinkedText(
    ThemeData theme, {
    required String label,
    required VoidCallback? onTap,
  }) {
    final text = label.trim().isEmpty ? '—' : label.trim();
    if (onTap == null || text == '—') {
      return Text(
        text,
        maxLines: 2,
        overflow: TextOverflow.ellipsis,
        style: theme.textTheme.bodyMedium,
      );
    }
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 2),
        child: Text(
          text,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: theme.textTheme.bodyMedium?.copyWith(
            color: theme.colorScheme.primary,
            fontWeight: FontWeight.w700,
            decoration: TextDecoration.underline,
          ),
        ),
      ),
    );
  }

  int? _partyId(Map<String, dynamic> row) {
    final value = row['party_id'];
    if (value is int) return value;
    return int.tryParse(value?.toString() ?? '');
  }

  TransactionRecord? _invoiceRecord(Map<String, dynamic> row) {
    final route = (row['invoice_route'] ?? '').toString();
    final idValue = row['invoice_id'];
    final id = idValue is int ? idValue : int.tryParse(idValue?.toString() ?? '');
    if (id == null || route.isEmpty) return null;
    final type = route.contains('sales') ? 'income' : 'expense';
    return TransactionRecord.fromVoucher(
      <String, dynamic>{
        'payload': <String, dynamic>{
          'id': id,
          'voucher_number': (row['invoice_number'] ?? '').toString(),
          'voucher_type': type,
          'type_label': route.contains('sales') ? 'Sales' : 'Purchase',
          'voucher_date': (row['date'] ?? '').toString(),
          'party_id': row['party_id'],
          'party': <String, dynamic>{
            'id': row['party_id'],
            'name': (row['party_name'] ?? '').toString(),
          },
          'total_debit': _num(row['amount']),
          'total_credit': _num(row['amount']),
          'status': 'posted',
        },
      },
    );
  }
}

class _ItemDetailField {
  const _ItemDetailField(
    this.label,
    this.value, {
    this.isBadge = false,
    this.badgeColor,
  });

  final String label;
  final String value;
  final bool isBadge;
  final Color? badgeColor;
}

class _ExportActionChip extends StatelessWidget {
  const _ExportActionChip({
    required this.label,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        height: 34,
        padding: const EdgeInsets.symmetric(horizontal: 10),
        decoration: BoxDecoration(
          color: color.withValues(alpha: .10),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: color.withValues(alpha: .24)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            Icon(icon, size: 16, color: color),
            const SizedBox(width: 6),
            Text(
              label,
              style: Theme.of(context).textTheme.labelMedium?.copyWith(
                    color: color,
                    fontWeight: FontWeight.w700,
                  ),
            ),
          ],
        ),
      ),
    );
  }
}
