import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/utils/app_date_formatter.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/repositories/reports/reports_repository.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import '../masters/widgets/masters_ui_components.dart';
import 'widgets/report_ui_components.dart';

class StockValueRegisterScreen extends StatefulWidget {
  const StockValueRegisterScreen({
    required this.financialYearId,
    this.fromDate,
    this.toDate,
    super.key,
  });

  final int financialYearId;
  final String? fromDate;
  final String? toDate;

  @override
  State<StockValueRegisterScreen> createState() =>
      _StockValueRegisterScreenState();
}

class _StockValueRegisterScreenState extends State<StockValueRegisterScreen> {
  static const Color _accentColor = Color(0xFF2563EB);

  final _repository = Get.find<ReportsRepository>();
  final _fromDateController = TextEditingController();
  final _toDateController = TextEditingController();

  final isLoading = false.obs;
  final entries = <Map<String, dynamic>>[].obs;

  late int _financialYearId;

  @override
  void initState() {
    super.initState();
    _financialYearId = widget.financialYearId;
    if (widget.fromDate != null && widget.fromDate!.isNotEmpty) {
      _fromDateController.text = AppDateFormatter.formatDisplay(widget.fromDate);
    }
    if (widget.toDate != null && widget.toDate!.isNotEmpty) {
      _toDateController.text = AppDateFormatter.formatDisplay(widget.toDate);
    }
    _loadEntries();
  }

  @override
  void dispose() {
    _fromDateController.dispose();
    _toDateController.dispose();
    super.dispose();
  }

  String? get _activeFromDate {
    final value = _fromDateController.text.trim();
    if (value.isEmpty) {
      return null;
    }
    return AppDateFormatter.toApiDate(value);
  }

  String? get _activeToDate {
    final value = _toDateController.text.trim();
    if (value.isEmpty) {
      return null;
    }
    return AppDateFormatter.toApiDate(value);
  }

  Future<void> _loadEntries() async {
    isLoading.value = true;
    try {
      entries.assignAll(
        await _repository.getStockValueEntries(
          financialYearId: _financialYearId,
          fromDate: _activeFromDate,
          toDate: _activeToDate,
        ),
      );
    } catch (error) {
      AppSnackbar.errorDialog(extractApiErrorMessage(error));
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> _resetFilters() async {
    final lookup = Get.find<ReportLookupController>();
    setState(() {
      _financialYearId =
          lookup.currentFinancialYearId.value ?? widget.financialYearId;
      _fromDateController.clear();
      _toDateController.clear();
    });
    await _loadEntries();
  }

  Future<void> _openEntryForm({Map<String, dynamic>? entry}) async {
    final saved = await showDialog<bool>(

      context: context,
      builder: (context) => _StockValueEntryDialog(

        financialYearId: _financialYearId,
        entry: entry,
      ),
    );
    if (saved == true) {
      await _loadEntries();
    }
  }

  Future<void> _pickDate(TextEditingController target) async {
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

  @override
  Widget build(BuildContext context) {
    final lookup = Get.find<ReportLookupController>();

    return Scaffold(
      appBar: AppBar(
        title: ReportPageTitle(
          title: 'Stock Value Register',
          icon: FontAwesomeIcons.boxesStacked.data,
          color: _accentColor,
        ),
      ),
      body: Obx(() {
        if (isLoading.value && entries.isEmpty) {
          return const ReportLoadingView();
        }

        return RefreshIndicator(
          onRefresh: _loadEntries,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: <Widget>[
              _heroSection(context),
              const SizedBox(height: 12),
              _filterPanel(context, lookup),
              const SizedBox(height: 12),
              _entriesSection(context),
            ],
          ),
        );
      }),
    );
  }

  Widget _heroSection(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: theme.dividerColor.withValues(alpha: .25),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            'Profit & Loss',
            style: theme.textTheme.labelLarge?.copyWith(
              color: _accentColor,
              fontWeight: FontWeight.w800,
              letterSpacing: .4,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Enter the total stock value by date. The latest values are used in the Profit & Loss report.',
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              height: 1.35,
            ),
          ),
          const SizedBox(height: 12),
          Align(
            alignment: Alignment.centerRight,
            child: OutlinedButton.icon(
              onPressed: Get.back,
              icon: const Icon(Icons.arrow_back_rounded, size: 16),
              label: const Text('Back to Profit & Loss'),
            ),
          ),
        ],
      ),
    );
  }

  Widget _filterPanel(BuildContext context, ReportLookupController lookup) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        gradient: LinearGradient(
          colors: <Color>[
            _accentColor.withValues(alpha: .06),
            Theme.of(context).colorScheme.surface,
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        border: Border.all(color: _accentColor.withValues(alpha: .10)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              Icon(FontAwesomeIcons.filter.data, size: 16, color: _accentColor),
              const SizedBox(width: 8),
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
                onPressed: _resetFilters,
                icon: const Icon(Icons.refresh_rounded, size: 16),
                label: const Text('Reset'),
              ),
            ],
          ),
          const SizedBox(height: 12),
          CustomDropdown<int>(
            label: 'Financial Year',
            value: _financialYearId,
            items: lookup.financialYears
                .map((e) => int.tryParse(e['id']?.toString() ?? ''))
                .whereType<int>()
                .toList(),
            itemLabelBuilder: (value) {
              final item = lookup.financialYears.firstWhere(
                (fy) => int.tryParse(fy['id']?.toString() ?? '') == value,
                orElse: () => <String, dynamic>{},
              );
              return (item['name'] ?? 'FY').toString();
            },
            onChanged: (value) {
              if (value == null) {
                return;
              }
              setState(() => _financialYearId = value);
            },
          ),
          const SizedBox(height: 12),
          ReportDateRangeRow(
            fromController: _fromDateController,
            toController: _toDateController,
            onFromTap: () => _pickDate(_fromDateController),
            onToTap: () => _pickDate(_toDateController),
          ),
          const SizedBox(height: 12),
          Align(
            alignment: Alignment.centerRight,
            child: ReportPrimaryButton(
              label: 'Apply',
              icon: FontAwesomeIcons.filter.data,
              onTap: _loadEntries,
            ),
          ),
        ],
      ),
    );
  }

  Widget _entriesSection(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: theme.dividerColor.withValues(alpha: .25),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          LayoutBuilder(
            builder: (context, constraints) {
              final stacked = constraints.maxWidth < 520;
              final header = Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Row(
                    children: <Widget>[
                      Icon(
                        FontAwesomeIcons.calendarDays.data,
                        size: 16,
                        color: _accentColor,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Stock Value Entries',
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w600,
                            fontSize: 15,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Create or edit a dated total value. Entries cannot be deleted.',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                      height: 1.3,
                    ),
                  ),
                ],
              );

              if (stacked) {
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    header,
                    const SizedBox(height: 10),
                    ReportPrimaryButton(
                      label: 'Add Entry',
                      icon: FontAwesomeIcons.circlePlus.data,
                      onTap: () => _openEntryForm(),
                    ),
                  ],
                );
              }

              return Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Expanded(child: header),
                  ReportPrimaryButton(
                    label: 'Add Entry',
                    icon: FontAwesomeIcons.circlePlus.data,
                    onTap: () => _openEntryForm(),
                  ),
                ],
              );
            },
          ),
          const SizedBox(height: 12),
          if (entries.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 40),
              child: Center(
                child: Text('No stock value entries found.'),
              ),
            )
          else
            SizedBox(
              height: (42.0 + (entries.length * 52.0)).clamp(180.0, 520.0),
              child: MastersTableShell(
                isLoading: isLoading.value,
                emptyText: 'No stock value entries found.',
                minWidth: 860,
                columns: <DataColumn2>[
                  masterColumn(context, 'Date', size: ColumnSize.M),
                  masterColumn(context, 'Stock Value (₹)', size: ColumnSize.M),
                  masterColumn(context, 'Remarks', size: ColumnSize.L),
                  masterColumn(context, 'Updated', size: ColumnSize.M),
                  masterColumn(context, 'Action', fixedWidth: 96),
                ],
                rows: entries.map((entry) {
                  return DataRow(
                    cells: <DataCell>[
                      masterTextCell(
                        AppDateFormatter.formatDisplay(
                          (entry['valuation_date'] ?? '').toString(),
                        ),
                        fontWeight: FontWeight.w700,
                      ),
                      masterTextCell(
                        _formatCurrency(entry['stock_value']),
                        fontWeight: FontWeight.w800,
                      ),
                      masterTextCell(
                        _displayRemarks(entry['remarks']),
                        fontWeight: FontWeight.w500,
                      ),
                      masterTextCell(
                        _formatUpdatedAt(entry['updated_at']),
                        fontWeight: FontWeight.w500,
                      ),
                      DataCell(
                        Center(
                          child: OutlinedButton.icon(
                            onPressed: () => _openEntryForm(entry: entry),
                            style: OutlinedButton.styleFrom(
                              visualDensity: VisualDensity.compact,
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 6,
                              ),
                            ),
                            icon: const Icon(Icons.edit_outlined, size: 14),
                            label: const Text('Edit'),
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
    );
  }

  String _displayRemarks(dynamic value) {
    final text = (value ?? '').toString().trim();
    return text.isEmpty ? '-' : text;
  }

  String _formatCurrency(dynamic value) {
    final amount = double.tryParse(value?.toString() ?? '0') ?? 0;
    return '₹${amount.toStringAsFixed(2)}';
  }

  String _formatUpdatedAt(dynamic value) {
    final parsed = AppDateFormatter.parse(value);
    if (parsed == null) {
      return '-';
    }
    final day = parsed.day.toString().padLeft(2, '0');
    final month = parsed.month.toString().padLeft(2, '0');
    final year = parsed.year;
    final hour = parsed.hour.toString().padLeft(2, '0');
    final minute = parsed.minute.toString().padLeft(2, '0');
    return '$day/$month/$year $hour:$minute';
  }
}

class _StockValueEntryDialog extends StatefulWidget {
  const _StockValueEntryDialog({
    required this.financialYearId,
    this.entry,
  });

  final int financialYearId;
  final Map<String, dynamic>? entry;

  @override
  State<_StockValueEntryDialog> createState() => _StockValueEntryDialogState();
}

class _StockValueEntryDialogState extends State<_StockValueEntryDialog> {
  final _repository = Get.find<ReportsRepository>();
  final _formKey = GlobalKey<FormState>();
  final _valueController = TextEditingController();
  final _remarksController = TextEditingController();
  final _dateController = TextEditingController();

  bool isSaving = false;

  bool get _isEdit => widget.entry != null;

  @override
  void initState() {
    super.initState();
    final entry = widget.entry;
    if (entry != null) {
      _dateController.text = AppDateFormatter.formatDisplay(
        (entry['valuation_date'] ?? '').toString(),
      );
      _valueController.text = (entry['stock_value'] ?? '0').toString();
      _remarksController.text = (entry['remarks'] ?? '').toString();
    }
  }

  @override
  void dispose() {
    _valueController.dispose();
    _remarksController.dispose();
    _dateController.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final initial =
        AppDateFormatter.parse(_dateController.text) ?? DateTime.now();
    final selected = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      _dateController.text = AppDateFormatter.formatDisplay(selected);
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    setState(() => isSaving = true);
    try {
      final valuationDate = AppDateFormatter.toApiDate(_dateController.text);
      final stockValue = double.tryParse(_valueController.text.trim()) ?? 0;
      final remarks = _remarksController.text.trim();
      if (_isEdit) {
        await _repository.updateStockValueEntry(
          entryId: int.parse(widget.entry!['id'].toString()),
          financialYearId: widget.financialYearId,
          valuationDate: valuationDate,
          stockValue: stockValue,
          remarks: remarks,
        );
      } else {
        await _repository.saveStockValueEntry(
          financialYearId: widget.financialYearId,
          valuationDate: valuationDate,
          stockValue: stockValue,
          remarks: remarks,
        );
      }
      if (mounted) {
        Navigator.of(context).pop(true);
      }
      AppSnackbar.success(
        _isEdit
            ? 'Stock value updated successfully.'
            : 'Stock value saved successfully.',
      );
    } catch (error) {
      AppSnackbar.errorDialog(extractApiErrorMessage(error));
    } finally {
      if (mounted) {
        setState(() => isSaving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(

      backgroundColor: Colors.white,
      insetPadding: EdgeInsets.symmetric(horizontal: 10),
      title: Text(_isEdit ? 'Edit Stock Value' : 'Add Stock Value',style: context.theme.textTheme.titleMedium?.copyWith(
        color: context.theme.colorScheme.onBackground,
       fontSize: 14
      ),),
      content: Form(
        key: _formKey,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            CustomTextField(
              controller: _dateController,
              label: 'Date',
              readOnly: true,
              requiredField: true,
              suffixIcon: Icons.edit_calendar_rounded,
              onTap: _pickDate,
              validator: (value) =>
                  (value ?? '').trim().isEmpty ? 'Required field' : null,
            ),
            const SizedBox(height: 10),
            CustomTextField(
              controller: _valueController,
              label: 'Stock Value (₹)',
              requiredField: true,
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              validator: (value) {
                final amount = double.tryParse((value ?? '').trim());
                if (amount == null) {
                  return 'Enter a valid amount';
                }
                if (amount < 0) {
                  return 'Amount cannot be negative';
                }
                return null;
              },
            ),
            const SizedBox(height: 12),
            CustomTextField(
              controller: _remarksController,
              label: 'Remarks',
              maxLines: 3,
            ),
          ],
        ),
      ),
      actions: <Widget>[
        TextButton(
          onPressed: isSaving ? null : () => Navigator.of(context).pop(false),
          child: const Text('Cancel'),
        ),
        FilledButton.icon(
          onPressed: isSaving ? null : _submit,
          icon: isSaving
              ? const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.check_circle_outline, size: 18),
          label: const Text('Save'),
        ),
      ],
    );
  }
}
