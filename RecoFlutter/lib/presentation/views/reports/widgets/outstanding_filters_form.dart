import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../../core/utils/app_date_formatter.dart';
import '../../../controllers/reports/outstanding_report_filters.dart';
import '../../../controllers/reports/report_lookup_controller.dart';
import '../../../widgets/common/custom_text_field.dart';
import 'report_ui_components.dart';

/// Shared filter panel body for debtors / creditors / aging summary.
class OutstandingFiltersForm extends StatelessWidget {
  const OutstandingFiltersForm({
    required this.controller,
    required this.onFilter,
    this.exportExcel,
    this.exportPdf,
    this.primaryColor = const Color(0xFFDC2626),
    super.key,
  });

  final OutstandingReportFiltersMixin controller;
  final VoidCallback onFilter;
  final VoidCallback? exportExcel;
  final VoidCallback? exportPdf;
  final Color primaryColor;

  @override
  Widget build(BuildContext context) {
    final lookup = Get.find<ReportLookupController>();

    return Obx(() {
      return Column(
        children: <Widget>[
          CustomDropdown<int>(
            label: 'Financial Year',
            value: controller.financialYearId.value,
            items: lookup.financialYears
                .map((item) => int.tryParse(item['id']?.toString() ?? ''))
                .whereType<int>()
                .toList(),
            itemLabelBuilder: (value) {
              final item = lookup.financialYears.firstWhere(
                (row) => int.tryParse(row['id']?.toString() ?? '') == value,
                orElse: () => <String, dynamic>{},
              );
              return (item['name'] ?? 'FY').toString();
            },
            onChanged: (value) =>
                controller.applyFinancialYear(value, lookup),
          ),
          CustomTextField(
            label: 'As of Date',
            controller: controller.asOfDateController,
            readOnly: true,
            suffixIcon: Icons.edit_calendar_rounded,
            onTap: () => _pickDate(context, controller.asOfDateController),
            bottomPadding: 12,
          ),
          Row(
            children: <Widget>[
              Expanded(
                child: CustomDropdown<String>(
                  label: 'Overdue Status',
                  value: controller.overdueStatus.value,
                  items: OutstandingReportFiltersMixin.overdueStatusOptions
                      .map((e) => e.key)
                      .toList(),
                  itemLabelBuilder: (value) =>
                      OutstandingReportFiltersMixin.overdueStatusOptions
                          .firstWhere(
                            (e) => e.key == value,
                            orElse: () => MapEntry(value, value),
                          )
                          .value,
                  onChanged: (value) {
                    if (value != null) controller.overdueStatus.value = value;
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: CustomDropdown<String>(
                  label: 'Aging Basis',
                  value: controller.basis.value,
                  items: OutstandingReportFiltersMixin.basisOptions
                      .map((e) => e.key)
                      .toList(),
                  itemLabelBuilder: (value) =>
                      OutstandingReportFiltersMixin.basisOptions
                          .firstWhere(
                            (e) => e.key == value,
                            orElse: () => MapEntry(value, value),
                          )
                          .value,
                  onChanged: (value) {
                    if (value != null) controller.basis.value = value;
                  },
                ),
              ),
            ],
          ),
          CustomDropdown<String>(
            label: 'Age Bucket',
            value: controller.ageBucket.value,
            items: OutstandingReportFiltersMixin.ageBucketOptions
                .map((e) => e.key)
                .toList(),
            itemLabelBuilder: (value) =>
                OutstandingReportFiltersMixin.ageBucketOptions
                    .firstWhere(
                      (e) => e.key == value,
                      orElse: () => MapEntry(value, value),
                    )
                    .value,
            onChanged: (value) {
              if (value != null) controller.ageBucket.value = value;
            },
          ),
          if (controller.ageBucket.value == 'custom')
            Row(
              children: <Widget>[
                Expanded(
                  child: CustomTextField(
                    label: 'Age Min (days)',
                    controller: controller.ageMinController,
                    keyboardType: TextInputType.number,
                    bottomPadding: 12,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: CustomTextField(
                    label: 'Age Max (days)',
                    controller: controller.ageMaxController,
                    keyboardType: TextInputType.number,
                    bottomPadding: 12,
                  ),
                ),
              ],
            ),
          ReportActionBar(
            children: <Widget>[
              ReportPrimaryButton(
                label: 'Filter',
                icon: FontAwesomeIcons.sliders.data,
                onTap: onFilter,
              ),
              if (exportExcel != null)
                ReportSecondaryButton(
                  label: 'Excel',
                  icon: FontAwesomeIcons.fileExcel.data,
                  onTap: exportExcel!,
                ),
              if (exportPdf != null)
                ReportSecondaryButton(
                  label: 'PDF',
                  icon: FontAwesomeIcons.filePdf.data,
                  onTap: exportPdf!,
                ),
            ],
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

Color overdueStatusColor(String? status) {
  switch (status) {
    case 'due':
      return const Color(0xFFDC2626);
    case 'not_due':
      return const Color(0xFF16A34A);
    default:
      return const Color(0xFF64748B);
  }
}

Widget overdueBadge(BuildContext context, Map<String, dynamic> row) {
  final label = (row['overdue_label'] ?? row['overdue_status'] ?? '-')
      .toString();
  final color = overdueStatusColor(row['overdue_status']?.toString());
  return Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
    decoration: BoxDecoration(
      color: color.withValues(alpha: .12),
      borderRadius: BorderRadius.circular(999),
      border: Border.all(color: color.withValues(alpha: .25)),
    ),
    child: Text(
      label,
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
      style: Theme.of(context).textTheme.labelSmall?.copyWith(
            color: color,
            fontWeight: FontWeight.w800,
            fontSize: 11,
          ),
    ),
  );
}

/// Aggregate invoice-level outstanding rows into party-wise totals (web parity).
List<Map<String, dynamic>> summarizeOutstandingPartyWise(
  List<Map<String, dynamic>> rows,
) {
  final groups = <String, Map<String, dynamic>>{};

  for (final row in rows) {
    final partyRaw = row['party'];
    if (partyRaw is! Map) continue;
    final party = Map<String, dynamic>.from(partyRaw);
    final key = (party['id'] ?? party['name'] ?? '').toString();
    if (key.isEmpty) continue;

    final existing = groups.putIfAbsent(
      key,
      () => <String, dynamic>{
        'party': party,
        'invoice_count': 0,
        'invoice_total': 0.0,
        'amount_paid': 0.0,
        'balance': 0.0,
        'max_due_days': null,
      },
    );

    existing['invoice_count'] = (existing['invoice_count'] as int) + 1;
    existing['invoice_total'] =
        (existing['invoice_total'] as double) +
        (double.tryParse(row['invoice_total']?.toString() ?? '') ?? 0);
    existing['amount_paid'] =
        (existing['amount_paid'] as double) +
        (double.tryParse(row['amount_paid']?.toString() ?? '') ?? 0);
    existing['balance'] =
        (existing['balance'] as double) +
        (double.tryParse(row['balance']?.toString() ?? '') ?? 0);

    final dueDays = int.tryParse(
      (row['due_days'] ?? row['overdue_days'])?.toString() ?? '',
    );
    if (dueDays != null) {
      final currentMax = existing['max_due_days'] as int?;
      if (currentMax == null || dueDays > currentMax) {
        existing['max_due_days'] = dueDays;
      }
    }
  }

  final result = groups.values.toList()
    ..sort(
      (a, b) => ((b['balance'] as double).compareTo(a['balance'] as double)),
    );
  return result;
}

class OutstandingViewTabs extends StatelessWidget {
  const OutstandingViewTabs({
    required this.partyWise,
    required this.onChanged,
    required this.primaryColor,
    super.key,
  });

  final bool partyWise;
  final ValueChanged<bool> onChanged;
  final Color primaryColor;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: <Widget>[
        Expanded(
          child: _tabChip(
            context,
            label: 'Invoice Wise',
            selected: !partyWise,
            onTap: () => onChanged(false),
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _tabChip(
            context,
            label: 'Party Wise',
            selected: partyWise,
            onTap: () => onChanged(true),
          ),
        ),
      ],
    );
  }

  Widget _tabChip(
    BuildContext context, {
    required String label,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return Material(
      color: selected
          ? primaryColor.withValues(alpha: .12)
          : Theme.of(context).colorScheme.surfaceContainerHighest.withValues(
                alpha: .45,
              ),
      borderRadius: BorderRadius.circular(10),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10),
          alignment: Alignment.center,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(10),
            border: Border.all(
              color: selected
                  ? primaryColor.withValues(alpha: .45)
                  : Theme.of(context).dividerColor.withValues(alpha: .35),
            ),
          ),
          child: Text(
            label,
            style: Theme.of(context).textTheme.labelLarge?.copyWith(
                  fontWeight: FontWeight.w800,
                  color: selected
                      ? primaryColor
                      : Theme.of(context).colorScheme.onSurfaceVariant,
                ),
          ),
        ),
      ),
    );
  }
}
