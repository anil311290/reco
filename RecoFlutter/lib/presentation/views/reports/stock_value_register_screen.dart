import 'package:data_table_2/data_table_2.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/network/api_error_message.dart';
import '../../../core/utils/app_date_formatter.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/repositories/reports/reports_repository.dart';
import '../../widgets/common/common_button.dart';
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
  final _repository = Get.find<ReportsRepository>();

  final isLoading = false.obs;
  final entries = <Map<String, dynamic>>[].obs;

  @override
  void initState() {
    super.initState();
    _loadEntries();
  }

  Future<void> _loadEntries() async {
    isLoading.value = true;
    try {
      entries.assignAll(
        await _repository.getStockValueEntries(
          financialYearId: widget.financialYearId,
          fromDate: widget.fromDate,
          toDate: widget.toDate,
        ),
      );
    } catch (error) {
      AppSnackbar.errorDialog(extractApiErrorMessage(error));
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> _openEntryForm({Map<String, dynamic>? entry}) async {
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) => _StockValueEntrySheet(
        financialYearId: widget.financialYearId,
        entry: entry,
      ),
    );
    if (saved == true) {
      await _loadEntries();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Stock Value Register'),
        actions: <Widget>[
          IconButton(
            tooltip: 'Add Stock Value',
            onPressed: () => _openEntryForm(),
            icon: const Icon(Icons.add_rounded),
          ),
        ],
      ),
      body: Obx(() {
        if (isLoading.value && entries.isEmpty) {
          return const Center(child: CircularProgressIndicator());
        }
        return RefreshIndicator(
          onRefresh: _loadEntries,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: <Widget>[
              ReportSectionCard(
                title: 'Stock Value Entries',
                icon: FontAwesomeIcons.boxesStacked.data,
                iconColor: const Color(0xFF2563EB),
                trailing: TextButton.icon(
                  onPressed: () => _openEntryForm(),
                  icon: const Icon(Icons.add, size: 18),
                  label: const Text('Add'),
                ),
                child: entries.isEmpty
                    ? const Padding(
                        padding: EdgeInsets.all(24),
                        child: Center(
                          child: Text('No stock value entries found.'),
                        ),
                      )
                    : SizedBox(
                        height: (42.0 + (entries.length * 52.0)).clamp(
                          180.0,
                          520.0,
                        ),
                        child: MastersTableShell(
                          isLoading: false,
                          emptyText: 'No stock value entries found.',
                          minWidth: 720,
                          columns: <DataColumn2>[
                            masterColumn(context, 'Date', size: ColumnSize.M),
                            masterColumn(context, 'Stock Value (₹)', size: ColumnSize.M),
                            masterColumn(context, 'Remarks', size: ColumnSize.L),
                            masterColumn(context, 'Actions', fixedWidth: 72),
                          ],
                          rows: entries.map((entry) {
                            return DataRow(
                              cells: <DataCell>[
                                masterTextCell(
                                  AppDateFormatter.formatDisplay(
                                    (entry['valuation_date'] ?? '').toString(),
                                  ),
                                ),
                                masterTextCell(
                                  _formatCurrency(entry['stock_value']),
                                  fontWeight: FontWeight.w700,
                                ),
                                masterTextCell(
                                  (entry['remarks'] ?? '-').toString(),
                                  fontWeight: FontWeight.w500,
                                ),
                                DataCell(
                                  Center(
                                    child: MasterActionButton(
                                      icon: Icons.edit_outlined,
                                      tooltip: 'Edit Stock Value',
                                      color: Theme.of(context).colorScheme.primary,
                                      onTap: () => _openEntryForm(entry: entry),
                                    ),
                                  ),
                                ),
                              ],
                            );
                          }).toList(),
                        ),
                      ),
              ),
            ],
          ),
        );
      }),
    );
  }

  String _formatCurrency(dynamic value) {
    final amount = double.tryParse(value?.toString() ?? '0') ?? 0;
    return '₹${amount.toStringAsFixed(2)}';
  }
}

class _StockValueEntrySheet extends StatefulWidget {
  const _StockValueEntrySheet({
    required this.financialYearId,
    this.entry,
  });

  final int financialYearId;
  final Map<String, dynamic>? entry;

  @override
  State<_StockValueEntrySheet> createState() => _StockValueEntrySheetState();
}

class _StockValueEntrySheetState extends State<_StockValueEntrySheet> {
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
    } else {
      _dateController.text = AppDateFormatter.formatDisplay(DateTime.now());
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
      final payload = <String, dynamic>{
        'financialYearId': widget.financialYearId,
        'valuationDate': AppDateFormatter.toApiDate(_dateController.text),
        'stockValue': double.tryParse(_valueController.text.trim()) ?? 0,
        'remarks': _remarksController.text.trim(),
      };
      if (_isEdit) {
        await _repository.updateStockValueEntry(
          entryId: int.parse(widget.entry!['id'].toString()),
          financialYearId: payload['financialYearId'] as int,
          valuationDate: payload['valuationDate'] as String,
          stockValue: payload['stockValue'] as double,
          remarks: payload['remarks'] as String,
        );
      } else {
        await _repository.saveStockValueEntry(
          financialYearId: payload['financialYearId'] as int,
          valuationDate: payload['valuationDate'] as String,
          stockValue: payload['stockValue'] as double,
          remarks: payload['remarks'] as String,
        );
      }
      if (mounted) {
        Navigator.of(context).pop(true);
      }
      AppSnackbar.success(
        _isEdit ? 'Stock value updated successfully.' : 'Stock value saved successfully.',
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
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + bottomInset),
      child: Form(
        key: _formKey,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: <Widget>[
            Text(
              _isEdit ? 'Edit Stock Value' : 'Add Stock Value',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 16),
            CustomTextField(
              controller: _dateController,
              label: 'Valuation Date',
              readOnly: true,
              requiredField: true,
              onTap: _pickDate,
              validator: (value) =>
                  (value ?? '').trim().isEmpty ? 'Required field' : null,
            ),
            const SizedBox(height: 12),
            CustomTextField(
              controller: _valueController,
              label: 'Stock Value (₹)',
              requiredField: true,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
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
            const SizedBox(height: 16),
            CommonButton(
              text: _isEdit ? 'Update Stock Value' : 'Save Stock Value',
              isLoading: isSaving,
              onPressed: _submit,
            ),
          ],
        ),
      ),
    );
  }
}
