import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/network/api_client.dart';
import '../../../core/utils/app_date_formatter.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/models/transactions/transaction_entities.dart';
import '../../controllers/reports/extended_reports_controllers.dart';
import '../masters/widgets/masters_ui_components.dart';
import '../transactions/details/transaction_detail_screen.dart';
import '../transactions/utils/invoice_transaction_actions.dart';
import 'settlement_details_sheet.dart';
import 'widgets/report_ui_components.dart';
import '../../widgets/common/custom_text_field.dart';

const Color _kUnappliedPrimaryColor = Color(0xFF0D9488);

class UnappliedReceiptsReportScreen
    extends GetView<UnappliedReceiptsReportController> {
  const UnappliedReceiptsReportScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: ReportPageTitle(
          title: 'Unapplied Cash',
          icon: FontAwesomeIcons.circleDollarToSlot.data,
          color: _kUnappliedPrimaryColor,
        ),
      ),
      body: _UnappliedReportBody(controller: controller),
    );
  }
}

class _UnappliedReportBody extends StatefulWidget {
  const _UnappliedReportBody({required this.controller});

  final UnappliedReceiptsReportController controller;

  @override
  State<_UnappliedReportBody> createState() => _UnappliedReportBodyState();
}

class _UnappliedReportBodyState extends State<_UnappliedReportBody> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final controller = widget.controller;
    return Obx(() {
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
            iconColor: _kUnappliedPrimaryColor,
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
          _UnappliedTabBar(
            index: _index,
            receiptsCount: receipts.length,
            paymentsCount: payments.length,
            onChanged: (next) => setState(() => _index = next),
          ),
          const SizedBox(height: 12),
          _UnappliedSection(
            index: _index,
            receipts: receipts,
            payments: payments,
            formatCurrency: controller.formatCurrency,
            formatDate: controller.formatDate,
            onChanged: controller.loadReport,
          ),
        ],
      );
    });
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

class _UnappliedSection extends StatefulWidget {
  const _UnappliedSection({
    required this.index,
    required this.receipts,
    required this.payments,
    required this.formatCurrency,
    required this.formatDate,
    required this.onChanged,
  });

  final int index;
  final List<Map<String, dynamic>> receipts;
  final List<Map<String, dynamic>> payments;
  final String Function(dynamic) formatCurrency;
  final String Function(String) formatDate;
  final Future<void> Function() onChanged;

  @override
  State<_UnappliedSection> createState() => _UnappliedSectionState();
}

class _UnappliedSectionState extends State<_UnappliedSection> {
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final rows = widget.index == 0 ? widget.receipts : widget.payments;
    final tabColor =
        widget.index == 0 ? const Color(0xFF16A34A) : const Color(0xFFDC2626);
    final tabTitle = widget.index == 0 ? 'Receipts' : 'Payments';
    final voucherType = widget.index == 0 ? 'receipt' : 'payment';

    final totalVoucher = rows.fold<double>(
      0,
      (sum, row) =>
          sum + (double.tryParse(row['voucher_amount']?.toString() ?? '') ?? 0),
    );
    final totalUnapplied = rows.fold<double>(
      0,
      (sum, row) =>
          sum + (double.tryParse(row['unapplied_amount']?.toString() ?? '') ?? 0),
    );

    final tableRows = <DataRow>[
      ...List<DataRow>.generate(rows.length, (index) {
        final row = rows[index];
        final party = row['party'] is Map
            ? Map<String, dynamic>.from(row['party'] as Map)
            : <String, dynamic>{};
        return DataRow(
          cells: <DataCell>[
            DataCell(
              Center(
                child: ReportLinkText(
                  (row['voucher_number'] ?? '-').toString(),
                  onTap: row['voucher_id'] == null
                      ? null
                      : () => showPaymentSettlementDetails(
                            voucherId: row['voucher_id'],
                            title: (row['voucher_number'] ?? '').toString(),
                          ),
                ),
              ),
            ),
            masterTextCell(
              widget.formatDate(
                (row['voucher_date'] ?? '').toString(),
              ),
            ),
            masterTextCell((party['name'] ?? '-').toString()),
            masterTextCell(_titleCase(voucherType)),
            DataCell(
              Center(
                child: Text(
                  widget.formatCurrency(row['voucher_amount']),
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 13,
                  ),
                ),
              ),
            ),
            DataCell(
              Center(
                child: Text(
                  widget.formatCurrency(row['unapplied_amount']),
                  style: TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 13,
                    color: tabColor,
                  ),
                ),
              ),
            ),
            DataCell(
              Center(
                child: _ApplyAllocationCell(
                  row: row,
                  voucherType: voucherType,
                  onApplied: () async {
                    await widget.onChanged();
                    if (mounted) setState(() {});
                  },
                ),
              ),
            ),
          ],
        );
      }),
    ];

    return ReportSectionCard(
      title: 'Unapplied Payments & Receipts',
      icon: FontAwesomeIcons.circleDollarToSlot.data,
      iconColor: _kUnappliedPrimaryColor,
      child: rows.isEmpty
          ? Padding(
              padding: const EdgeInsets.all(28),
              child: Center(
                child: Text(
                  'No unapplied ${tabTitle.toLowerCase()} found for this date range.',
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ),
            )
          : Column(
              children: <Widget>[
                Row(
                  children: <Widget>[
                    Expanded(
                      child: ReportStatCard(
                        label: '$tabTitle Count',
                        value: '${rows.length}',
                        note: 'Vouchers with unapplied balance',
                        color: tabColor,
                        icon: FontAwesomeIcons.receipt.data,
                      ),
                    ),
                    const SizedBox(width: 5),
                    Expanded(
                      child: ReportStatCard(
                        label: 'Voucher Total',
                        value: widget.formatCurrency(totalVoucher),
                        note: 'Sum of voucher amounts',
                        color: _kUnappliedPrimaryColor,
                        icon: FontAwesomeIcons.indianRupeeSign.data,
                      ),
                    ),
                    const SizedBox(width: 5),
                    Expanded(
                      child: ReportStatCard(
                        label: 'Unapplied Total',
                        value: widget.formatCurrency(totalUnapplied),
                        note: 'Sum of unapplied balance',
                        color: tabColor,
                        icon: FontAwesomeIcons.circleDollarToSlot.data,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                SizedBox(
                  height:
                      (42.0 + (rows.length * 52.0)).clamp(94.0, 520.0),
                  child: MastersTableShell(
                    isLoading: false,
                    emptyText: 'No rows',
                    minWidth: 1180,
                    columns: <DataColumn2>[
                      masterColumn(context, 'Voucher', size: ColumnSize.M),
                      masterColumn(context, 'Date', size: ColumnSize.S),
                      masterColumn(context, 'Party', size: ColumnSize.L),
                      masterColumn(context, 'Type', size: ColumnSize.S),
                      masterColumn(
                        context,
                        'Voucher Amount',
                        size: ColumnSize.M,
                      ),
                      masterColumn(context, 'Unapplied', size: ColumnSize.M),
                      masterColumn(
                        context,
                        'Apply to Bill',
                        size: ColumnSize.L,
                      ),
                    ],
                    rows: tableRows,
                  ),
                ),
              ],
            ),
    );
  }

  String _titleCase(String value) {
    if (value.isEmpty) return value;
    return '${value[0].toUpperCase()}${value.substring(1)}';
  }
}

class _ApplyAllocationCell extends StatelessWidget {
  const _ApplyAllocationCell({
    required this.row,
    required this.voucherType,
    required this.onApplied,
  });

  final Map<String, dynamic> row;
  final String voucherType;
  final Future<void> Function() onApplied;

  @override
  Widget build(BuildContext context) {
    final party = row['party'] is Map
        ? Map<String, dynamic>.from(row['party'] as Map)
        : <String, dynamic>{};
    final partyId = party['id'] as int?;
    final invoices = (row['invoices'] is List)
        ? (row['invoices'] as List)
            .whereType<Map>()
            .map((e) => Map<String, dynamic>.from(e))
            .toList()
        : <Map<String, dynamic>>[];
    final unapplied = double.tryParse(
          row['unapplied_amount']?.toString() ?? '',
        ) ??
        0;
    final hasVoucher = row['voucher_id'] != null;

    if (!hasVoucher) {
      return const Text('-', style: TextStyle(fontSize: 13));
    }
    if (partyId == null || invoices.isEmpty) {
      return Text(
        'No open bills for this party',
        style: TextStyle(
          fontSize: 12,
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
      );
    }
    return FilledButton.icon(
      style: FilledButton.styleFrom(
        backgroundColor: const Color(0xFF16A34A),
        foregroundColor: Colors.white,
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        textStyle: const TextStyle(
          fontWeight: FontWeight.w800,
          fontSize: 12,
        ),
      ),
      icon: const Icon(Icons.check_circle_outline, size: 14),
      label: const Text('Apply'),
      onPressed: () async {
        final applied = await showModalBottomSheet<bool>(
          context: context,
          isScrollControlled: true,
          backgroundColor: Colors.transparent,
          builder: (sheetContext) => _ApplyAllocationSheet(
            row: row,
            party: party,
            invoices: invoices,
            unappliedAmount: unapplied,
            voucherType: voucherType,
          ),
        );
        if (applied == true) {
          await onApplied();
        }
      },
    );
  }
}

class _ApplyAllocationSheet extends StatefulWidget {
  const _ApplyAllocationSheet({
    required this.row,
    required this.party,
    required this.invoices,
    required this.unappliedAmount,
    required this.voucherType,
  });

  final Map<String, dynamic> row;
  final Map<String, dynamic> party;
  final List<Map<String, dynamic>> invoices;
  final double unappliedAmount;
  final String voucherType;

  @override
  State<_ApplyAllocationSheet> createState() => _ApplyAllocationSheetState();
}

class _ApplyAllocationSheetState extends State<_ApplyAllocationSheet> {
  late Map<String, dynamic>? _selectedInvoice = widget.invoices.isNotEmpty
      ? widget.invoices.first
      : null;
  late final TextEditingController _amountController =
      TextEditingController(text: _initialAmount());
  bool _submitting = false;

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }

  String _initialAmount() {
    final first = widget.invoices.isNotEmpty ? widget.invoices.first : null;
    final balance =
        double.tryParse(first?['balance_due']?.toString() ?? '') ?? 0;
    final value = _twoDecimals(
      balance > 0
          ? (balance < widget.unappliedAmount ? balance : widget.unappliedAmount)
          : widget.unappliedAmount,
    );
    return value.toStringAsFixed(2);
  }

  double _twoDecimals(double value) => (value * 100).round() / 100;

  void _onInvoiceChanged(Map<String, dynamic>? invoice) {
    setState(() {
      _selectedInvoice = invoice;
      if (invoice != null) {
        final balance =
            double.tryParse(invoice['balance_due']?.toString() ?? '') ?? 0;
        final next = balance > 0
            ? (balance < widget.unappliedAmount
                ? balance
                : widget.unappliedAmount)
            : widget.unappliedAmount;
        _amountController.text = _twoDecimals(next).toStringAsFixed(2);
      }
    });
  }

  Future<void> _submit() async {
    final partyId = widget.party['id'];
    final voucherId = widget.row['voucher_id'];
    final invoice = _selectedInvoice;
    if (partyId == null || voucherId == null || invoice == null) {
      AppSnackbar.error('Select a bill before applying.');
      return;
    }
    final amount =
        double.tryParse(_amountController.text.trim()) ?? 0;
    if (amount <= 0) {
      AppSnackbar.error('Enter a valid amount greater than zero.');
      return;
    }
    setState(() => _submitting = true);
    try {
      final apiClient = Get.find<ApiClient>();
      final source = (widget.row['allocation_source'] ?? 'voucher').toString();
      final response = await apiClient.post<Map<String, dynamic>>(
        ApiEndpoints.partyApplyUnapplied(partyId),
        data: <String, dynamic>{
          'invoice_id': invoice['id'],
          'amount': amount,
          'source': source,
          'voucher_id': voucherId,
        },
      );
      final dynamic data = response.data;
      final Map<String, dynamic> body = data is Map<String, dynamic>
          ? data
          : (data is Map
              ? Map<String, dynamic>.from(data)
              : <String, dynamic>{});
      final ok = body['success'] != false;
      final message = body['message'] is String
          ? body['message'] as String
          : 'Amount applied successfully.';
      if (ok) {
        AppSnackbar.success(message);
        if (mounted) Navigator.of(context).pop(true);
      } else {
        AppSnackbar.error(message);
      }
    } catch (e) {
      AppSnackbar.error('Unable to apply amount: $e');
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final invoiceOptions = widget.invoices
        .map<DropdownMenuItem<Map<String, dynamic>>>(
          (inv) => DropdownMenuItem<Map<String, dynamic>>(
            value: inv,
            child: Text(
              '${inv['invoice_number']} — Balance: ${_currency(inv['balance_due'])}',
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        )
        .toList();

    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SafeArea(
        top: false,
        child: Material(
          color: theme.colorScheme.surface,
          shape: const RoundedRectangleBorder(
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Center(
                  child: Container(
                    width: 42,
                    height: 4,
                    margin: const EdgeInsets.only(bottom: 12),
                    decoration: BoxDecoration(
                      color: theme.dividerColor,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                Row(
                  children: <Widget>[
                    Icon(
                      Icons.check_circle_outline,
                      color: _kUnappliedPrimaryColor,
                      size: 20,
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Apply to Bill',
                        style: theme.textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  '${widget.row['voucher_number'] ?? '-'} · ${widget.party['name'] ?? '-'}',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 8,
                  ),
                  decoration: BoxDecoration(
                    color: theme.colorScheme.surfaceContainerHighest
                        .withValues(alpha: .5),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Row(
                    children: <Widget>[
                      const Icon(Icons.info_outline, size: 16),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Unapplied: ${_currency(widget.unappliedAmount)}',
                          style: const TextStyle(
                            fontWeight: FontWeight.w700,
                            fontSize: 13,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 14),
                Text(
                  'Select bill',
                  style: theme.textTheme.labelLarge?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 6),
                DropdownButtonFormField<Map<String, dynamic>>(
                  initialValue: _selectedInvoice,
                  isExpanded: true,
                  decoration: InputDecoration(
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 10,
                    ),
                  ),
                  items: invoiceOptions,
                  onChanged: _onInvoiceChanged,
                ),
                const SizedBox(height: 14),
                Text(
                  'Amount',
                  style: theme.textTheme.labelLarge?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: _amountController,
                  keyboardType:
                      const TextInputType.numberWithOptions(decimal: true),
                  decoration: InputDecoration(
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 10,
                    ),
                    hintText: '0.00',
                    suffixText: widget.voucherType == 'receipt' ? 'Dr' : 'Cr',
                  ),
                ),
                const SizedBox(height: 18),
                Row(
                  children: <Widget>[
                    Expanded(
                      child: OutlinedButton(
                        onPressed: _submitting
                            ? null
                            : () => Navigator.of(context).pop(false),
                        style: OutlinedButton.styleFrom(
                          padding:
                              const EdgeInsets.symmetric(vertical: 12),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                        ),
                        child: const Text(
                          'Cancel',
                          style: TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: _submitting ? null : _submit,
                        icon: _submitting
                            ? const SizedBox(
                                width: 14,
                                height: 14,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              )
                            : const Icon(
                                Icons.check_circle_outline,
                                size: 16,
                              ),
                        label: Text(_submitting ? 'Applying…' : 'Apply'),
                        style: FilledButton.styleFrom(
                          backgroundColor: _kUnappliedPrimaryColor,
                          foregroundColor: Colors.white,
                          padding:
                              const EdgeInsets.symmetric(vertical: 12),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                          textStyle: const TextStyle(
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  String _currency(dynamic value) {
    if (value == null) return '0.00';
    final num = double.tryParse(value.toString()) ?? 0;
    return '₹${num.toStringAsFixed(2)}';
  }
}

class _UnappliedTabBar extends StatelessWidget {
  const _UnappliedTabBar({
    required this.index,
    required this.receiptsCount,
    required this.paymentsCount,
    required this.onChanged,
  });

  final int index;
  final int receiptsCount;
  final int paymentsCount;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Container(
        padding: const EdgeInsets.all(4),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surfaceContainerHighest
              .withValues(alpha: .5),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: Theme.of(context).dividerColor.withValues(alpha: .4),
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            _TabItem(
              label: 'Unapplied Receipts',
              count: receiptsCount,
              selected: index == 0,
              color: const Color(0xFF16A34A),
              onTap: () => onChanged(0),
            ),
            _TabItem(
              label: 'Unapplied Payments',
              count: paymentsCount,
              selected: index == 1,
              color: const Color(0xFFDC2626),
              onTap: () => onChanged(1),
            ),
          ],
        ),
      ),
    );
  }
}

class _TabItem extends StatelessWidget {
  const _TabItem({
    required this.label,
    required this.count,
    required this.selected,
    required this.color,
    required this.onTap,
  });

  final String label;
  final int count;
  final bool selected;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final fg = selected ? Colors.white : Theme.of(context).colorScheme.onSurface;
    return Material(
      color: selected ? color : Colors.transparent,
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: onTap,
        child: Padding(
          padding:
              const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              Icon(
                selected ? Icons.check_circle : Icons.radio_button_unchecked,
                size: 16,
                color: selected
                    ? Colors.white
                    : color.withValues(alpha: .7),
              ),
              const SizedBox(width: 8),
              Text(
                label,
                style:
                    Theme.of(context).textTheme.labelLarge?.copyWith(
                          color: fg,
                          fontWeight: FontWeight.w800,
                        ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 8,
                  vertical: 2,
                ),
                decoration: BoxDecoration(
                  color: selected
                      ? Colors.white.withValues(alpha: .25)
                      : color.withValues(alpha: .15),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  '$count',
                  style:
                      Theme.of(context).textTheme.labelSmall?.copyWith(
                            color: selected ? Colors.white : color,
                            fontWeight: FontWeight.w800,
                          ),
                ),
              ),
            ],
          ),
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
                      : Builder(builder: (context) {
                          // ── Totals ──
                          double totalIn = 0;
                          double totalOut = 0;
                          for (final r in rows) {
                            totalIn += _parseQty(r['qty_in'] ?? r['in']);
                            totalOut += _parseQty(r['qty_out'] ?? r['out']);
                          }

                          final dataRows = <DataRow>[
                            ...List<DataRow>.generate(rows.length, (i) {
                              final row = rows[i];
                              final partyName = row['party_name'] ??
                                  (row['party'] is Map
                                      ? row['party']['name']
                                      : null);
                              final refLabel =
                                  (row['invoice_number'] ??
                                          row['document_number'] ??
                                          row['reference'] ??
                                          row['voucher_number'] ??
                                          '-')
                                      .toString();
                              final invoiceId = _tryInt(row['invoice_id']);
                              final movementType =
                                  (row['type'] ?? row['movement_type'] ?? '')
                                      .toString();
                              final isClickableRef =
                                  invoiceId != null &&
                                      (movementType == 'sales' ||
                                          movementType == 'purchase') &&
                                      refLabel.trim().isNotEmpty &&
                                      refLabel.trim() != '-';

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
                                  DataCell(
                                    Center(
                                      child: isClickableRef
                                          ? InkWell(
                                              onTap: () => _openInvoice(
                                                context,
                                                movementType,
                                                invoiceId,
                                                refLabel,
                                              ),
                                              child: Padding(
                                                padding:
                                                    const EdgeInsets.symmetric(
                                                  horizontal: 4,
                                                  vertical: 2,
                                                ),
                                                child: Text(
                                                  refLabel,
                                                  textAlign: TextAlign.center,
                                                  style: TextStyle(
                                                    fontSize: 13,
                                                    fontWeight: FontWeight.w700,
                                                    color: Theme.of(context)
                                                        .colorScheme
                                                        .primary,
                                                    decoration: TextDecoration
                                                        .underline,
                                                    decorationColor:
                                                        Theme.of(context)
                                                            .colorScheme
                                                            .primary,
                                                  ),
                                                ),
                                              ),
                                            )
                                          : Text(
                                              refLabel,
                                              textAlign: TextAlign.center,
                                              style: TextStyle(
                                                fontSize: 13,
                                                fontWeight: FontWeight.w600,
                                              ),
                                            ),
                                    ),
                                  ),
                                  masterTextCell((partyName ?? '-').toString()),
                                  masterTextCell(
                                    _formatQty(row['qty_in'] ?? row['in']),
                                  ),
                                  masterTextCell(
                                    _formatQty(row['qty_out'] ?? row['out']),
                                  ),
                                  masterTextCell(
                                    _formatQty(
                                      row['running_qty'] ?? row['balance'],
                                    ),
                                  ),
                                ],
                              );
                            }),
                            // ── Total row ──
                            DataRow(
                              color: reportTotalRowColor(context),
                              cells: <DataCell>[
                                const DataCell(SizedBox.shrink()),
                                const DataCell(SizedBox.shrink()),
                                const DataCell(SizedBox.shrink()),
                                const DataCell(SizedBox.shrink()),
                                DataCell(
                                  Align(
                                    alignment: Alignment.centerRight,
                                    child: Text(
                                      'Total',
                                      style: reportTotalRowTextStyle(context)
                                          ?.copyWith(fontSize: 13),
                                    ),
                                  ),
                                ),
                                const DataCell(SizedBox.shrink()),
                                DataCell(
                                  Center(
                                    child: Text(
                                      _formatQty(totalIn),
                                      style: reportTotalRowTextStyle(context)
                                          ?.copyWith(fontSize: 13),
                                    ),
                                  ),
                                ),
                                DataCell(
                                  Center(
                                    child: Text(
                                      _formatQty(totalOut),
                                      style: reportTotalRowTextStyle(context)
                                          ?.copyWith(fontSize: 13),
                                    ),
                                  ),
                                ),
                                const DataCell(SizedBox.shrink()),
                              ],
                            ),
                          ];

                          return SizedBox(
                            height: (42.0 + (dataRows.length * 52.0))
                                .clamp(140.0, 540.0),
                            child: MastersTableShell(
                              isLoading: false,
                              emptyText: 'No movements',
                              minWidth: 1020,
                              columns: <DataColumn2>[
                                masterColumn(context, '#', fixedWidth: 48),
                                masterColumn(context, 'Date',
                                    size: ColumnSize.S),
                                masterColumn(context, 'Item',
                                    size: ColumnSize.M),
                                masterColumn(context, 'Movement',
                                    size: ColumnSize.S),
                                masterColumn(context, 'Reference',
                                    size: ColumnSize.M),
                                masterColumn(context, 'Party',
                                    size: ColumnSize.M),
                                masterColumn(context, 'In', size: ColumnSize.S),
                                masterColumn(context, 'Out',
                                    size: ColumnSize.S),
                                masterColumn(context, 'Balance',
                                    size: ColumnSize.S),
                              ],
                              rows: dataRows,
                            ),
                          );
                        }),
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

  Future<void> _openInvoice(
    BuildContext context,
    String movementType,
    int invoiceId,
    String title,
  ) async {
    if (movementType != 'sales' && movementType != 'purchase') {
      return;
    }
    final record = TransactionRecord(
      kind: movementType == 'sales'
          ? TransactionRecordKind.salesInvoice
          : TransactionRecordKind.purchaseInvoice,
      id: invoiceId,
      number: title,
      type: movementType,
      typeLabel: movementType == 'sales' ? 'Sales Invoice' : 'Purchase',
      rawPayload: <String, dynamic>{'id': invoiceId},
    );
    final detailRecord = await resolveTransactionDetailRecord(record);
    if (!context.mounted) return;
    await Get.to<void>(
      () => TransactionDetailScreen(record: detailRecord),
    );
  }
}

double _parseQty(dynamic value) {
  if (value == null) return 0;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString()) ?? 0;
}

String _formatQty(dynamic value) {
  if (value == null) return '0';
  if (value is num) {
    final v = value.toDouble();
    if (v == v.truncateToDouble()) return v.toStringAsFixed(0);
    return v
        .toStringAsFixed(3)
        .replaceFirst(RegExp(r'0+$'), '')
        .replaceFirst(RegExp(r'\.$'), '');
  }
  final raw = value.toString();
  final parsed = double.tryParse(raw);
  if (parsed == null) return raw;
  if (parsed == parsed.truncateToDouble()) return parsed.toStringAsFixed(0);
  return parsed
      .toStringAsFixed(3)
      .replaceFirst(RegExp(r'0+$'), '')
      .replaceFirst(RegExp(r'\.$'), '');
}

int? _tryInt(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  return int.tryParse(value.toString());
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
