import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/network/api_error_message.dart';
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
                      label: 'Apply',
                      icon: FontAwesomeIcons.filter.data,
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
            asOfDate: controller.toDateController.text,
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
    required this.asOfDate,
    required this.onChanged,
  });

  final int index;
  final List<Map<String, dynamic>> receipts;
  final List<Map<String, dynamic>> payments;
  final String Function(dynamic) formatCurrency;
  final String Function(String) formatDate;
  final String asOfDate;
  final Future<void> Function() onChanged;

  @override
  State<_UnappliedSection> createState() => _UnappliedSectionState();
}

class _UnappliedSectionState extends State<_UnappliedSection> {
  static const double _rowHeight = 68;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final rows = widget.index == 0 ? widget.receipts : widget.payments;
    final tabColor =
        widget.index == 0 ? const Color(0xFF16A34A) : const Color(0xFFDC2626);
    final tabTitle = widget.index == 0 ? 'receipts' : 'payments';
    final voucherType = widget.index == 0 ? 'receipt' : 'payment';

    if (rows.isEmpty) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 32),
        child: Center(
          child: Text(
            'No unapplied $tabTitle found for this date range.',
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
        ),
      );
    }

    final tableRows = <DataRow>[
      ...List<DataRow>.generate(rows.length, (index) {
        final row = rows[index];
        final party = _unappliedParty(row);
        final reference = (row['reference_number'] ?? '').toString();
        final isOpeningBalance = reference == 'Opening Balance';
        final typeLabel = isOpeningBalance
            ? 'Opening Balance'
            : _titleCase(voucherType);

        return DataRow(
          cells: <DataCell>[
            DataCell(
              Center(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: <Widget>[
                    ReportLinkText(
                      (row['voucher_number'] ?? '-').toString(),
                      onTap: row['voucher_id'] == null
                          ? null
                          : () => showPaymentSettlementDetails(
                                voucherId: row['voucher_id'],
                                title: (row['voucher_number'] ?? '').toString(),
                              ),
                    ),
                    if (reference.isNotEmpty && !isOpeningBalance)
                      Text(
                        reference,
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                      ),
                  ],
                ),
              ),
            ),
            masterTextCell(
              widget.formatDate((row['voucher_date'] ?? '').toString()),
            ),
            DataCell(

              Center(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: <Widget>[
                    Text(
                      (party['name'] ?? '-').toString(),
                      style: const TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                      ),
                    ),
                    Text(
                      (party['party_code'] ?? party['code'] ?? '-').toString(),
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            DataCell(
              Center(
                child: _unappliedTypeBadge(
                  context,
                  typeLabel,
                  tabColor,
                  isOpeningBalance,
                ),
              ),
            ),
            masterTextCell(widget.formatCurrency(row['voucher_amount'])),
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
                  asOfDate: widget.asOfDate,
                  onApplied: widget.onChanged,
                ),
              ),
            ),
          ],
        );
      }),
    ];

    final tableHeight =
        (42.0 + (rows.length * _rowHeight)).clamp(180.0, 620.0);

    return SizedBox(
      height: tableHeight,
      child: MastersTableShell(
        isLoading: false,
        emptyText: 'No rows',
        minWidth: 1180,
        dataRowHeight: _rowHeight,
        columns: <DataColumn2>[
          masterColumn(context, 'Voucher', size: ColumnSize.L),
          masterColumn(context, 'Date', size: ColumnSize.M),
          masterColumn(context, 'Party', size: ColumnSize.L),
          masterColumn(context, 'Type', size: ColumnSize.M),
          masterColumn(context, 'Voucher Amount', size: ColumnSize.M),
          masterColumn(context, 'Unapplied', size: ColumnSize.M),
          masterColumn(
            context,
            'Apply To Bill',
            size: ColumnSize.L,
            fixedWidth: 450,
          ),
        ],
        rows: tableRows,
      ),
    );
  }

  Map<String, dynamic> _unappliedParty(Map<String, dynamic> row) {
    final party = row['party'];
    if (party is Map) {
      return Map<String, dynamic>.from(party);
    }
    return <String, dynamic>{};
  }

  Widget _unappliedTypeBadge(
    BuildContext context,
    String label,
    Color color,
    bool isOpeningBalance,
  ) {
    final badgeColor =
        isOpeningBalance ? const Color(0xFF0EA5E9) : color;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: badgeColor.withValues(alpha: .12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: Theme.of(context).textTheme.labelSmall?.copyWith(
          color: badgeColor,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }

  String _titleCase(String value) {
    if (value.isEmpty) return value;
    return '${value[0].toUpperCase()}${value.substring(1)}';
  }
}

class _ApplyAllocationCell extends StatefulWidget {
  const _ApplyAllocationCell({
    required this.row,
    required this.voucherType,
    required this.asOfDate,
    required this.onApplied,
  });

  final Map<String, dynamic> row;
  final String voucherType;
  final String asOfDate;
  final Future<void> Function() onApplied;

  @override
  State<_ApplyAllocationCell> createState() => _ApplyAllocationCellState();
}

class _ApplyAllocationCellState extends State<_ApplyAllocationCell> {
  List<Map<String, dynamic>> _invoices = <Map<String, dynamic>>[];
  Map<String, dynamic>? _selectedInvoice;
  late final TextEditingController _amountController;
  bool _loadingInvoices = false;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _amountController = TextEditingController();
    _invoices = _filterInvoicesByAsOf(
      _parseInvoices(widget.row),
      widget.asOfDate,
    );
    if (_invoices.isNotEmpty) {
      _selectedInvoice = _invoices.first;
      _amountController.text = _initialAmount();
    } else {
      _loadInvoices();
    }
  }

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }

  Future<void> _loadInvoices() async {
    final partyId = _partyId(widget.row);
    if (partyId == null || _asInt(widget.row['voucher_id']) == null) {
      return;
    }
    setState(() => _loadingInvoices = true);
    try {
      final invoiceType = (widget.row['invoice_type'] ??
              (widget.voucherType == 'receipt' ? 'sales' : 'purchase'))
          .toString();
      final response = await Get.find<ApiClient>().get<Map<String, dynamic>>(
        ApiEndpoints.partyOutstandingInvoices(partyId),
        queryParameters: <String, dynamic>{'invoice_type': invoiceType},
      );
      final data = response.data?['data'];
      final loaded = <Map<String, dynamic>>[];
      if (data is List) {
        for (final item in data) {
          if (item is Map) {
            loaded.add(Map<String, dynamic>.from(item));
          }
        }
      }
      _invoices = _filterInvoicesByAsOf(loaded, widget.asOfDate);
      if (_invoices.isNotEmpty) {
        _selectedInvoice = _invoices.first;
        _amountController.text = _initialAmount();
      }
    } catch (_) {
      _invoices = <Map<String, dynamic>>[];
    } finally {
      if (mounted) {
        setState(() => _loadingInvoices = false);
      }
    }
  }

  List<Map<String, dynamic>> _parseInvoices(Map<String, dynamic> row) {
    final raw = row['invoices'];
    if (raw is! List) {
      return <Map<String, dynamic>>[];
    }
    return raw
        .map((item) {
          if (item is Map<String, dynamic>) {
            return item;
          }
          if (item is Map) {
            return Map<String, dynamic>.from(item);
          }
          return <String, dynamic>{};
        })
        .where((item) => _asInt(item['id']) != null)
        .toList();
  }

  List<Map<String, dynamic>> _filterInvoicesByAsOf(
    List<Map<String, dynamic>> invoices,
    String asOfDate,
  ) {
    final asOf = AppDateFormatter.parse(asOfDate);
    if (asOf == null) {
      return invoices;
    }
    return invoices.where((invoice) {
      final invoiceDate = AppDateFormatter.parse(invoice['invoice_date']);
      if (invoiceDate == null) {
        return true;
      }
      return !invoiceDate.isAfter(asOf);
    }).toList();
  }

  int? _partyId(Map<String, dynamic> row) {
    final direct = _asInt(row['party_id']);
    if (direct != null) {
      return direct;
    }
    final party = row['party'];
    if (party is Map) {
      return _asInt(party['id']);
    }
    return null;
  }

  double get _unapplied =>
      double.tryParse(widget.row['unapplied_amount']?.toString() ?? '') ?? 0;

  String _initialAmount() {
    final balance =
        double.tryParse(_selectedInvoice?['balance_due']?.toString() ?? '') ??
            0;
    final value = _twoDecimals(
      balance > 0
          ? (balance < _unapplied ? balance : _unapplied)
          : _unapplied,
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
            ? (balance < _unapplied ? balance : _unapplied)
            : _unapplied;
        _amountController.text = _twoDecimals(next).toStringAsFixed(2);
      }
    });
  }

  Future<void> _submit() async {
    final partyId = _partyId(widget.row);
    final voucherId = _asInt(widget.row['voucher_id']);
    final invoice = _selectedInvoice;
    if (partyId == null || voucherId == null || invoice == null) {
      AppSnackbar.error('Select a bill before applying.');
      return;
    }
    final amount = double.tryParse(_amountController.text.trim()) ?? 0;
    if (amount <= 0) {
      AppSnackbar.error('Enter a valid amount greater than zero.');
      return;
    }
    setState(() => _submitting = true);
    try {
      final source = (widget.row['allocation_source'] ?? 'voucher').toString();
      final response = await Get.find<ApiClient>().post<Map<String, dynamic>>(
        ApiEndpoints.partyApplyUnapplied(partyId),
        data: <String, dynamic>{
          'invoice_id': _asInt(invoice['id']),
          'amount': amount,
          'source': source,
          'voucher_id': voucherId,
        },
      );
      final data = response.data;
      final body = data is Map<String, dynamic>
          ? data
          : (data is Map ? Map<String, dynamic>.from(data!) : <String, dynamic>{});
      if (body['success'] != false) {
        AppSnackbar.success(
          body['message'] is String
              ? body['message'] as String
              : 'Amount applied successfully.',
        );
        await widget.onApplied();
      } else {
        AppSnackbar.errorDialog(
          body['message']?.toString() ?? 'Unable to apply amount.',
        );
      }
    } catch (error) {
      AppSnackbar.errorDialog(extractApiErrorMessage(error));
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_asInt(widget.row['voucher_id']) == null) {
      return const Center(child: Text('-', style: TextStyle(fontSize: 13)));
    }
    if (_loadingInvoices) {
      return const Center(
        child: SizedBox(
          width: 18,
          height: 18,
          child: CircularProgressIndicator(strokeWidth: 2),
        ),
      );
    }
    if (_invoices.isEmpty) {
      return Center(
        child: Text(
          'No open bills for this party',
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 12,
            color: Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ),
      );
    }

    return Align(
      alignment: Alignment.centerLeft,
      child: SizedBox(
        width: 500,
        height: 48,
        child: Row(
          children: <Widget>[
            SizedBox(
              width: 260,
              child: DropdownButtonFormField<Map<String, dynamic>>(
                key: ValueKey(_selectedInvoice?['id']),
                initialValue: _selectedInvoice,
                isExpanded: true,
                decoration: InputDecoration(
                  isDense: true,
                  hintText: 'Select bill',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 8,
                  ),
                ),
                items: _invoices
                    .map(
                      (invoice) => DropdownMenuItem<Map<String, dynamic>>(
                        value: invoice,
                        child: Text(
                          '${invoice['invoice_number']} - Balance: ${_currency(invoice['balance_due'])}',
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    )
                    .toList(),
                onChanged: _onInvoiceChanged,
              ),
            ),
            const SizedBox(width: 8),
            SizedBox(
              width: 84,
              child: TextField(
                controller: _amountController,
                keyboardType:
                    const TextInputType.numberWithOptions(decimal: true),
                decoration: InputDecoration(
                  isDense: true,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 8,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 8),
            FilledButton.icon(
              onPressed: _submitting ? null : _submit,
              style: FilledButton.styleFrom(
                backgroundColor: const Color(0xFF16A34A),
                foregroundColor: Colors.white,
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                visualDensity: VisualDensity.compact,
              ),
              icon: _submitting
                  ? const SizedBox(
                      width: 14,
                      height: 14,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.check_circle_outline, size: 14),
              label: Text(
                _submitting ? '...' : 'Apply',
                style:
                    const TextStyle(fontWeight: FontWeight.w800, fontSize: 12),
              ),
            ),
          ],
        ),
      ),
    );
  }

  int? _asInt(dynamic value) => int.tryParse(value?.toString() ?? '');

  String _currency(dynamic value) {
    final amount = double.tryParse(value?.toString() ?? '') ?? 0;
    return '₹${amount.toStringAsFixed(2)}';
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
  static const Color _qtyInColor = Color(0xFF15803D);
  static const Color _qtyOutColor = Color(0xFFB91C1C);
  static const Color _qtyBalanceColor = Color(0xFF1D4ED8);

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
        final report = data is Map<String, dynamic>
            ? data
            : <String, dynamic>{};
        final totalMovements =
            report['total_movements'] ?? rows.length;

        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle:
                  'Item-wise stock movement with opening quantity, inward quantity, outward quantity, and running balance.',
              icon: FontAwesomeIcons.sliders.data,
              iconColor: _primaryColor,
              child: Column(
                children: <Widget>[
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: Text(
                          'Filters',
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w700,
                            fontSize: 15,
                          ),
                        ),
                      ),
                      TextButton.icon(
                        onPressed: controller.resetFilters,
                        icon: const Icon(Icons.refresh_rounded, size: 16),
                        label: const Text('Reset'),
                      ),
                    ],
                  ),
                  CustomDropdown<int>(
                    label: 'Stock Item',
                    value: controller.itemId.value,
                    items: controller.itemOptions
                        .map(
                          (item) =>
                              int.tryParse(item['id']?.toString() ?? ''),
                        )
                        .whereType<int>()
                        .toList(),
                    itemLabelBuilder: (value) {
                      final item = controller.itemOptions.firstWhere(
                        (row) =>
                            int.tryParse(row['id']?.toString() ?? '') ==
                            value,
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
                  Align(
                    alignment: Alignment.centerRight,
                    child: ReportPrimaryButton(
                      label: 'Apply',
                      icon: FontAwesomeIcons.filter.data,
                      onTap: controller.loadReport,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Row(
                        children: <Widget>[
                          Icon(
                            FontAwesomeIcons.listCheck.data,
                            size: 16,
                            color: _primaryColor,
                          ),
                          const SizedBox(width: 8),
                          Text(
                            'Stock Movements',
                            style: Theme.of(context)
                                .textTheme
                                .titleMedium
                                ?.copyWith(
                                  fontWeight: FontWeight.w600,
                                  fontSize: 15,
                                ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Stock items are shown with their Stock ID in brackets.',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: Theme.of(context)
                              .colorScheme
                              .onSurfaceVariant,
                          fontSize: 10
                        ),
                      ),
                    ],
                  ),
                ),
                _movementPill(
                  context,
                  '$totalMovements Movements',
                  _primaryColor,
                ),
              ],
            ),
            const SizedBox(height: 12),
            if (controller.itemId.value == null)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 32),
                child: Center(
                  child: Text('Select a stock item to load movements.'),
                ),
              )
            else if (rows.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 32),
                child: Center(
                  child: Text(
                    'No stock movements found for the selected filters.',
                  ),
                ),
              )
            else
              _buildMovementsTable(context, rows, report),
          ],
        );
      }),
    );
  }

  Widget _buildMovementsTable(
    BuildContext context,
    List<Map<String, dynamic>> rows,
    Map<String, dynamic> report,
  ) {
    final totalIn = _parseQty(report['total_in']);
    final totalOut = _parseQty(report['total_out']);
    final closingQty = _parseQty(report['closing_quantity']);

    return LayoutBuilder(
      builder: (context, constraints) {
        final tableWidth = constraints.maxWidth < 1060 ? 1060.0 : constraints.maxWidth;
        return SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: SizedBox(
            width: tableWidth,
            child: Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(10),
                border: Border.all(
                  color: Theme.of(context).dividerColor.withValues(alpha: .55),
                ),
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(10),
                child: Column(
                  children: <Widget>[
                    _tableHeaderRow(context),
                    ...rows.map((row) => _tableDataRow(context, row)),
                    _tableTotalRow(
                      context,
                      totalIn: totalIn,
                      totalOut: totalOut,
                      closingQty: closingQty,
                    ),
                  ],
                ),
              ),
            ),
          ),
        );
      },
    );
  }

  Widget _tableHeaderRow(BuildContext context) {
    final style = Theme.of(context).textTheme.labelMedium?.copyWith(
      fontWeight: FontWeight.w800,
    );
    return Container(
      color: Theme.of(context)
          .colorScheme
          .surfaceContainerHighest
          .withValues(alpha: .45),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      child: Row(
        children: <Widget>[
          _headerCell('Date', style, width: 120),
          _headerCell('Stock Item', style, flex: 2),
          _headerCell('Movement', style, width: 150),
          _headerCell('Reference', style, flex: 2),
          _headerCell('Party', style, flex: 2),
          _headerCell('UoM', style, width: 72),
          _headerCell('Quantity In', style, width: 108, alignEnd: true),
          _headerCell('Quantity Out', style, width: 108, alignEnd: true),
          _headerCell('Balance Quantity', style, width: 120, alignEnd: true),
        ],
      ),
    );
  }

  Widget _headerCell(
    String label,
    TextStyle? style, {
    int flex = 0,
    double? width,
    bool alignEnd = false,
  }) {
    final child = Text(
      label,
      textAlign: alignEnd ? TextAlign.right : TextAlign.left,
      style: style,
    );
    if (width != null) {
      return SizedBox(width: width, child: child);
    }
    return Expanded(flex: flex, child: child);
  }

  Widget _tableDataRow(BuildContext context, Map<String, dynamic> row) {
    final movementType = (row['type'] ?? row['movement_type'] ?? '')
        .toString()
        .toLowerCase();
    final refLabel = (row['invoice_number'] ??
            row['document_number'] ??
            row['reference'] ??
            row['voucher_number'] ??
            '')
        .toString();
    final invoiceId = _tryInt(row['invoice_id']);
    final hasInvoice = invoiceId != null &&
        (movementType == 'sale' ||
            movementType == 'sales' ||
            movementType == 'purchase') &&
        refLabel.trim().isNotEmpty &&
        refLabel.trim() != '-';

    return Container(
      decoration: BoxDecoration(
        border: Border(
          top: BorderSide(
            color: Theme.of(context).dividerColor.withValues(alpha: .35),
          ),
        ),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          SizedBox(
            width: 96,
            child: Text(
              row['date'] == null
                  ? '-'
                  : controller.formatDate((row['date'] ?? '').toString()),
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
            ),
          ),
          Expanded(
            flex: 2,
            child: Text(
              (row['stock_reference'] ?? row['item_name'] ?? '-').toString(),
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
            ),
          ),
          SizedBox(
            width: 156,
            child: _movementPill(
              context,
              (row['type_label'] ?? row['type'] ?? '-').toString(),
              _movementColor(movementType),
            ),
          ),
          Expanded(
            flex: 2,
            child: hasInvoice
                ? ReportLinkText(
                    refLabel,
                    onTap: () => _openInvoice(
                      context,
                      movementType,
                      invoiceId,
                      refLabel,
                    ),
                  )
                : Text(
                    movementType == 'opening' ? 'Opening Stock' : '-',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                  ),
          ),
          Expanded(
            flex: 2,
            child: Text(
              (row['party_name'] ??
                      (row['party'] is Map
                          ? row['party']['name']
                          : null) ??
                      '-')
                  .toString(),
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
            ),
          ),
          SizedBox(
            width: 72,
            child: Text(
              (row['uom'] ?? '-').toString(),
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
            ),
          ),
          SizedBox(
            width: 108,
            child: Text(
              _formatQtyCell(row['qty_in'] ?? row['in']),
              textAlign: TextAlign.right,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: _qtyInColor,
              ),
            ),
          ),
          SizedBox(
            width: 108,
            child: Text(
              _formatQtyCell(row['qty_out'] ?? row['out'], isOut: true),
              textAlign: TextAlign.right,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: _qtyOutColor,
              ),
            ),
          ),
          SizedBox(
            width: 120,
            child: Text(
              _formatStockQty(row['running_qty'] ?? row['balance']),
              textAlign: TextAlign.right,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w800,
                color: _qtyBalanceColor,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _tableTotalRow(
    BuildContext context, {
    required double totalIn,
    required double totalOut,
    required double closingQty,
  }) {
    final totalStyle = reportTotalRowTextStyle(context);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
      color: const Color(0xFF23263A),
      child: Row(
        children: <Widget>[
          SizedBox(
            width: 96,
            child: Text('Total', style: totalStyle),
          ),
          const Expanded(flex: 2, child: SizedBox.shrink()),
          const SizedBox(width: 156, child: SizedBox.shrink()),
          const Expanded(flex: 2, child: SizedBox.shrink()),
          const Expanded(flex: 2, child: SizedBox.shrink()),
          const SizedBox(width: 72, child: SizedBox.shrink()),
          SizedBox(
            width: 108,
            child: Text(
              _formatStockQty(totalIn),
              textAlign: TextAlign.right,
              style: totalStyle?.copyWith(color: _qtyInColor),
            ),
          ),
          SizedBox(
            width: 108,
            child: Text(
              _formatStockQty(totalOut),
              textAlign: TextAlign.right,
              style: totalStyle?.copyWith(color: _qtyOutColor),
            ),
          ),
          SizedBox(
            width: 120,
            child: Text(
              _formatStockQty(closingQty),
              textAlign: TextAlign.right,
              style: totalStyle?.copyWith(color: _qtyBalanceColor),
            ),
          ),
        ],
      ),
    );
  }

  Color _movementColor(String movementType) {
    switch (movementType) {
      case 'purchase':
        return const Color(0xFF16A34A);
      case 'sale':
      case 'sales':
        return const Color(0xFFEF4444);
      default:
        return _primaryColor;
    }
  }

  Widget _movementPill(BuildContext context, String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .12),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withValues(alpha: .18)),
      ),
      child: Text(
        label,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: Theme.of(context).textTheme.labelSmall?.copyWith(
          color: color,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }

  String _formatQtyCell(dynamic value, {bool isOut = false}) {
    final qty = _parseQty(value);
    if (qty <= 0) {
      return '-';
    }
    return _formatStockQty(qty);
  }

  String _formatStockQty(dynamic value) {
    final qty = _parseQty(value);
    return qty.toStringAsFixed(3);
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
    final normalizedType =
        movementType == 'sales' ? 'sale' : movementType;
    if (normalizedType != 'sale' && normalizedType != 'purchase') {
      return;
    }
    final record = TransactionRecord(
      kind: normalizedType == 'sale'
          ? TransactionRecordKind.salesInvoice
          : TransactionRecordKind.purchaseInvoice,
      id: invoiceId,
      number: title,
      type: normalizedType,
      typeLabel: normalizedType == 'sale' ? 'Sales Invoice' : 'Purchase',
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
            // Row(
            //   children: <Widget>[
            //     Expanded(
            //       child: ReportStatCard(
            //         label: 'Mappings',
            //         value: '${summary['total_mappings'] ?? mappings.length}',
            //         note: 'Total settlement links',
            //         color: _primaryColor,
            //         icon: FontAwesomeIcons.link.data,
            //       ),
            //     ),
            //     const SizedBox(width: 8),
            //     Expanded(
            //       child: ReportStatCard(
            //         label: 'Allocated',
            //         value: controller.formatCurrency(summary['total_allocated']),
            //         note: 'Mapped amount',
            //         color: const Color(0xFF2563EB),
            //         icon: FontAwesomeIcons.sackDollar.data,
            //       ),
            //     ),
            //   ],
            // ),
            // const SizedBox(height: 10),
            // Row(
            //   children: <Widget>[
            //     Expanded(
            //       child: ReportStatCard(
            //         label: 'Settled',
            //         value: controller.formatCurrency(summary['total_settled']),
            //         note: 'Fully applied amount',
            //         color: const Color(0xFF16A34A),
            //         icon: FontAwesomeIcons.circleCheck.data,
            //       ),
            //     ),
            //     const SizedBox(width: 8),
            //     Expanded(
            //       child: ReportStatCard(
            //         label: 'Outstanding',
            //         value:
            //             controller.formatCurrency(summary['total_outstanding']),
            //         note: 'Still open after mapping',
            //         color: const Color(0xFFDC2626),
            //         icon: FontAwesomeIcons.hourglassHalf.data,
            //       ),
            //     ),
            //   ],
            // ),
            // const SizedBox(height: 12),
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
