import 'package:data_table_2/data_table_2.dart';
import 'dart:async';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/utils/app_action_loader.dart';
import '../../../core/utils/app_date_formatter.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/repositories/settings/audit_logs_repository.dart';
import '../../../data/repositories/transactions/transactions_repository.dart';
import '../../../data/models/transactions/transaction_entities.dart';
import '../../controllers/reports/ledger_report_controller.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import '../../controllers/settings/audit_logs_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import '../masters/history/party_history_screen.dart';
import '../masters/widgets/masters_ui_components.dart';
import '../settings/audit_logs_screen.dart';
import '../transactions/details/transaction_detail_screen.dart';
import 'ledger_history_screen.dart';
import 'widgets/report_ui_components.dart';

class LedgerReportScreen extends StatefulWidget {
  const LedgerReportScreen({super.key, this.initialAccountId});

  final int? initialAccountId;

  @override
  State<LedgerReportScreen> createState() => _LedgerReportScreenState();
}

class _LedgerReportScreenState extends State<LedgerReportScreen> {
  late final LedgerReportController controller;

  @override
  void initState() {
    super.initState();
    controller = Get.find<LedgerReportController>();
    if (widget.initialAccountId != null) {
      unawaited(controller.openLinkedLedger(widget.initialAccountId!));
    }
  }

  @override
  Widget build(BuildContext context) {
    final lookup = Get.find<ReportLookupController>();
    return Scaffold(
      appBar: AppBar(
        title: const ReportPageTitle(
          title: 'Ledger',
          icon: FontAwesomeIcons.bookOpen,
          color: Color(0xFF475569),
        ),
      ),
      body: Obx(() {
        if (controller.shouldShowInitialLoader) {
          return const ReportLoadingView();
        }
        final report = controller.reportData['data'];
        final selectedAccount =
            report is Map<String, dynamic> && report['account'] is Map<String, dynamic>
                ? Map<String, dynamic>.from(report['account'] as Map<String, dynamic>)
                : null;
        final entries = controller.entries;
        final bool compactLayout = MediaQuery.sizeOf(context).width < 640;
        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle: 'Select one ledger to view opening balance, voucher-wise movement, and closing balance (Tally ledger style).',
              icon: FontAwesomeIcons.sliders,
              iconColor: const Color(0xFF475569),
              child: Column(
                children: <Widget>[
                  CustomDropdown<int>(
                    label: 'Ledger',
                    value: controller.accountId.value,
                    items: lookup.ledgerAccounts
                        .map((item) => _asInt(item['id']))
                        .whereType<int>()
                        .toList(),
                    itemLabelBuilder: (value) {
                      final item = lookup.ledgerAccounts.firstWhere(
                        (row) => _asInt(row['id']) == value,
                        orElse: () => <String, dynamic>{},
                      );
                      final code = (item['account_code'] ?? '').toString();
                      final name = (item['account_name'] ?? '').toString();
                      return code.isEmpty ? name : '$code - $name';
                    },
                    onChanged: (value) {
                      controller.accountId.value = value;
                      controller.loadReport();
                    },
                  ),
                  const SizedBox(height: 5),
                  CustomDropdown<int>(
                    label: 'Financial Year',
                    value: controller.financialYearId.value,
                    items: lookup.financialYears
                        .map((item) => _asInt(item['id']))
                        .whereType<int>()
                        .toList(),
                    itemLabelBuilder: (value) {
                      final item = lookup.financialYears.firstWhere(
                        (row) => _asInt(row['id']) == value,
                        orElse: () => <String, dynamic>{},
                      );
                      return (item['name'] ?? 'FY').toString();
                    },
                    onChanged: (value) => controller.applyFinancialYear(value, lookup),
                  ),

                  ReportDateRangeRow(
                    fromController: controller.fromDateController,
                    toController: controller.toDateController,
                    onFromTap: () => _pickDate(controller.fromDateController),
                    onToTap: () => _pickDate(controller.toDateController),
                  ),
                  const SizedBox(height: 10),
                  ReportActionBar(
                    children: <Widget>[
                      ReportPrimaryButton(
                        label: 'Filter',
                        icon: FontAwesomeIcons.sliders,
                        onTap: controller.loadReport,
                      ),
                      ReportSecondaryButton(
                        label: 'Excel',
                        icon: FontAwesomeIcons.fileExcel,
                        onTap: () => controller.exportExcel(
                          reportName: 'ledger',
                          exportEndpoint: ApiEndpoints.exportLedgerExcel,
                          queryParameters: controller.queryParameters,
                        ),
                      ),
                      ReportSecondaryButton(
                        label: 'PDF',
                        icon: FontAwesomeIcons.filePdf,
                        onTap: () => controller.exportPdf(
                          exportEndpoint: ApiEndpoints.exportLedgerPdf,
                          queryParameters: controller.queryParameters,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            if (report is! Map<String, dynamic>)
              ReportSectionCard(
                title: 'Select an account',
                icon: FontAwesomeIcons.bookOpenReader,
                iconColor: const Color(0xFF475569),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Theme.of(context).cardColor,
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(
                      color: Theme.of(context).dividerColor.withValues(alpha: .18),
                    ),
                  ),
                  child: Text(
                    'Choose an account and date window to view the ledger.',
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                ),
              )
            else ...<Widget>[
              if (selectedAccount != null) ...<Widget>[
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: <Widget>[
                    SizedBox(
                      width: compactLayout
                          ? double.infinity
                          : (MediaQuery.sizeOf(context).width - 42) / 2,
                      child: ReportStatCard(
                        label: 'Account',
                        value: (selectedAccount['account_code'] ?? '-').toString(),
                        note: (selectedAccount['account_name'] ?? 'Ledger Account').toString(),
                        color: const Color(0xFF0F766E),
                        icon: FontAwesomeIcons.bookBookmark,
                      ),
                    ),
                    SizedBox(
                      width: compactLayout
                          ? double.infinity
                          : (MediaQuery.sizeOf(context).width - 42) / 2,
                      child: ReportStatCard(
                        label: 'Opening Balance',
                        value: controller.formatCurrency(
                          (report['opening_balance'] as Map?)?['balance'],
                        ),
                        note: _drCr(
                          ((report['opening_balance'] as Map?)?['type'] ?? '')
                              .toString(),
                        ),
                        color: const Color(0xFF2563EB),
                        icon: FontAwesomeIcons.circlePlay,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
              ],
              Wrap(
                spacing: 10,
                runSpacing: 10,
                children: <Widget>[
                  if (selectedAccount == null)
                    SizedBox(
                      width: compactLayout
                          ? double.infinity
                          : (MediaQuery.sizeOf(context).width - 42) / 2,
                      child: ReportStatCard(
                        label: 'Opening Balance',
                        value: controller.formatCurrency(
                          (report['opening_balance'] as Map?)?['balance'],
                        ),
                        note: _drCr(
                          ((report['opening_balance'] as Map?)?['type'] ?? '')
                              .toString(),
                        ),
                        color: const Color(0xFF2563EB),
                        icon: FontAwesomeIcons.circlePlay,
                      ),
                    ),
                  SizedBox(
                    width: compactLayout
                        ? double.infinity
                        : (MediaQuery.sizeOf(context).width - 42) / 2,
                    child: ReportStatCard(
                      label: 'Closing Balance',
                      value: controller.formatCurrency(
                        (report['closing_balance'] as Map?)?['balance'],
                      ),
                      note: _drCr(
                        ((report['closing_balance'] as Map?)?['type'] ?? '')
                            .toString(),
                      ),
                      color: const Color(0xFF16A34A),
                      icon: FontAwesomeIcons.circleStop,
                    ),
                  ),
                ],
              ),

            ],
            const SizedBox(height: 12),
            ReportSectionCard(
              title: 'Ledger Entries',
              icon: FontAwesomeIcons.tableList,
              iconColor: const Color(0xFF475569),
              trailing: report is Map<String, dynamic>
                  ? Text(
                      '${controller.entriesTotal.value} lines | Dr ${controller.formatCurrency(report['total_debit'])} | Cr ${controller.formatCurrency(report['total_credit'])}',
                      style: Theme.of(context).textTheme.labelLarge?.copyWith(
                        color: const Color(0xFF475569),
                        fontWeight: FontWeight.w700,
                        fontSize: 12,
                      ),
                    )
                  : null,
              child: entries.isEmpty && report is! Map
                  ? const Padding(
                      padding: EdgeInsets.all(24),
                      child: Center(child: Text('No entries found')),
                    )
                  : _buildLedgerTable(context, report, entries.toList()),
            ),
          ],
        );
      }),
    );
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

  Widget _buildLedgerTable(
    BuildContext context,
    dynamic reportData,
    List<Map<String, dynamic>> entries,
  ) {
    if (reportData is! Map) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(24),
          child: Text('No entries found'),
        ),
      );
    }

    final openingBalance = reportData['opening_balance'] is Map
        ? Map<String, dynamic>.from(reportData['opening_balance'] as Map)
        : <String, dynamic>{};
    final bool isCompact = MediaQuery.sizeOf(context).width < 640;

    if (isCompact) {
      return _buildMobileLedgerEntries(
        context,
        Map<String, dynamic>.from(reportData),
        openingBalance,
        entries,
      );
    }

    final tableRows = <DataRow>[
      DataRow(
        color: WidgetStatePropertyAll(
          Theme.of(context).colorScheme.primary.withValues(alpha: .06),
        ),
        cells: <DataCell>[
          const DataCell(SizedBox.shrink()),
          const DataCell(SizedBox.shrink()),
          DataCell(
            Text(
              'Opening Balance',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          const DataCell(SizedBox.shrink()),
          const DataCell(Center(child: Text('-'))),
          const DataCell(Center(child: Text('-'))),
          DataCell(
            Center(
              child: Text(
                '${controller.formatCurrency(openingBalance['balance'])} ${_drCr((openingBalance['type'] ?? '').toString())}',
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
          ),
          const DataCell(SizedBox.shrink()),
        ],
      ),
      ...List<DataRow>.generate(entries.length, (index) {
        final entry = entries[index];
        final voucher = entry['voucher'] is Map
            ? Map<String, dynamic>.from(entry['voucher'] as Map)
            : <String, dynamic>{};
        final party = entry['party'] is Map
            ? Map<String, dynamic>.from(entry['party'] as Map)
            : <String, dynamic>{};
        final debit =
            double.tryParse(entry['debit']?.toString() ?? '0') ?? 0;
        final credit =
            double.tryParse(entry['credit']?.toString() ?? '0') ?? 0;

        return DataRow(
          cells: <DataCell>[
            masterTextCell(controller.formatDate((entry['transaction_date'] ?? '').toString())),
            DataCell(
              Center(
                child: ReportLinkText(
                  (voucher['voucher_number'] ?? '-').toString(),
                  onTap: voucher.isEmpty
                      ? null
                      : () => _openVoucherDetail(entry, voucher, party),
                ),
              ),
            ),
            masterTextCell((entry['narration'] ?? entry['description'] ?? '-').toString()),
            DataCell(
              Center(
                child: ReportLinkText(
                  (party['name'] ?? '-').toString(),
                  onTap: _asInt(entry['party_id']) == null
                      ? null
                      : () => Get.to(
                            () => PartyHistoryScreen(
                              partyId: _asInt(entry['party_id'])!,
                            ),
                          ),
                ),
              ),
            ),
            DataCell(Center(
              child: Text(
                debit > 0 ? controller.formatCurrency(debit) : '-',
                textAlign: TextAlign.center,
                style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: debit > 0 ? const Color(0xFF2563EB) : null),
              ),
            )),
            DataCell(Center(
              child: Text(
                credit > 0 ? controller.formatCurrency(credit) : '-',
                textAlign: TextAlign.center,
                style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: credit > 0 ? const Color(0xFFEF4444) : null),
              ),
            )),
            masterTextCell('${controller.formatCurrency(entry['running_balance'])} ${_drCr((entry['balance_type'] ?? '').toString())}'),
            DataCell(Center(
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  if (voucher.isNotEmpty) ...<Widget>[
                    MasterActionButton(
                      icon: Icons.remove_red_eye_outlined,
                      tooltip: 'View Voucher',
                      color: Theme.of(context).colorScheme.primary,
                      onTap: () => _openVoucherDetail(entry, voucher, party),
                    ),
                    const SizedBox(width: 6),
                  ],
                  MasterActionButton(
                    icon: Icons.view_list_outlined,
                    tooltip: 'Ledger History',
                    color: const Color(0xFF475569),
                    onTap: () {
                      final ledgerId = _asInt(entry['id']);
                      if (ledgerId == null) {
                        return;
                      }
                      Get.to(() => LedgerHistoryScreen(ledgerEntryId: ledgerId));
                    },
                  ),
                  const SizedBox(width: 6),
                  MasterActionButton(
                    icon: FontAwesomeIcons.clockRotateLeft,
                    tooltip: 'Ledger Audit',
                    color: const Color(0xFF38BDF8),
                    onTap: () {
                      final ledgerId = _asInt(entry['id']);
                      if (ledgerId == null) {
                        return;
                      }
                      Get.to(
                        () => const AuditLogsScreen(),
                        binding: BindingsBuilder(() {
                          Get.put(
                            AuditLogsController(
                              Get.find<AuditLogsRepository>(),
                              initialModule: 'ledger',
                              initialRecordId: ledgerId.toString(),
                            ),
                          );
                        }),
                      );
                    },
                  ),
                ],
              ),
            )),
          ],
        );
      }),
      DataRow(
        color: reportTotalRowColor(context),
        cells: <DataCell>[
          const DataCell(SizedBox.shrink()),
          const DataCell(SizedBox.shrink()),
          DataCell(
            Text(
              'Total',
              style: reportTotalRowTextStyle(context),
            ),
          ),
          const DataCell(SizedBox.shrink()),
          DataCell(Center(child: Text(controller.formatCurrency(reportData['total_debit']), style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13)))),
          DataCell(Center(child: Text(controller.formatCurrency(reportData['total_credit']), style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13)))),
          DataCell(Center(child: Text('${controller.formatCurrency((reportData['closing_balance'] as Map?)?['balance'])} ${_drCr((((reportData['closing_balance'] as Map?)?['type'] ?? '').toString()))}', style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13)))),
          const DataCell(SizedBox.shrink()),
        ],
      ),
    ];

    final calculatedHeight = 42.0 + (tableRows.length * 52.0);
    final tableHeight = calculatedHeight.clamp(160.0, 550.0);

    return PaginatedTablePane(
      hasMore: controller.hasMoreEntries,
      isLoadingMore: controller.isLoadingMoreEntries.value,
      loadedCount: controller.entries.length,
      totalCount: controller.entriesTotal.value,
      onLoadMore: () => controller.loadEntries(reset: false),
      child: SizedBox(
        height: tableHeight,
        child: MastersTableShell(
          isLoading: controller.isLoadingEntries.value && controller.entries.isEmpty,
          emptyText: 'No entries found',
          minWidth: 1120,
          columns: <DataColumn2>[
            masterColumn(context, 'Date'),
            masterColumn(context, 'Voucher #'),
            masterColumn(context, 'Particulars', size: ColumnSize.L),
            masterColumn(context, 'Party'),
            masterColumn(context, 'Dr'),
            masterColumn(context, 'Cr'),
            masterColumn(context, 'Balance'),
            masterColumn(context, 'Actions', fixedWidth: 140),
          ],
          rows: tableRows,
        ),
      ),
    );
  }

  Widget _buildMobileLedgerEntries(
    BuildContext context,
    Map<String, dynamic> reportData,
    Map<String, dynamic> openingBalance,
    List<Map<String, dynamic>> entries,
  ) {
    final theme = Theme.of(context);
    return Container(
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: theme.dividerColor.withValues(alpha: .18),
        ),
      ),
      child: Column(
        children: <Widget>[
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              color: theme.colorScheme.primary.withValues(alpha: .05),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  'Opening Balance',
                  style: theme.textTheme.labelLarge?.copyWith(
                    fontWeight: FontWeight.w700,
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  '${controller.formatCurrency(openingBalance['balance'])} ${_drCr((openingBalance['type'] ?? '').toString())}',
                  style: theme.textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ],
            ),
          ),
          if (entries.isEmpty)
            const Padding(
              padding: EdgeInsets.all(24),
              child: Center(child: Text('No entries found')),
            )
          else
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              padding: const EdgeInsets.all(12),
              itemCount: entries.length,
              separatorBuilder: (_, _) => const SizedBox(height: 10),
              itemBuilder: (BuildContext context, int index) {
                final entry = entries[index];
                final voucher = entry['voucher'] is Map
                    ? Map<String, dynamic>.from(entry['voucher'] as Map)
                    : <String, dynamic>{};
                final party = entry['party'] is Map
                    ? Map<String, dynamic>.from(entry['party'] as Map)
                    : <String, dynamic>{};
                final debit =
                    double.tryParse(entry['debit']?.toString() ?? '0') ?? 0;
                final credit =
                    double.tryParse(entry['credit']?.toString() ?? '0') ?? 0;
                return Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                      color: theme.dividerColor.withValues(alpha: .18),
                    ),
                    color: theme.scaffoldBackgroundColor,
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: <Widget>[
                          Expanded(
                            child: Text(
                              controller.formatDate(
                                (entry['transaction_date'] ?? '').toString(),
                              ),
                              style: theme.textTheme.labelLarge?.copyWith(
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                          if (voucher.isNotEmpty)
                            ReportLinkText(
                              (voucher['voucher_number'] ?? '-').toString(),
                              onTap: () => _openVoucherDetail(entry, voucher, party),
                              textAlign: TextAlign.right,
                            ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      _mobileInfoRow(
                        context,
                        'Particulars',
                        (entry['narration'] ?? entry['description'] ?? '-').toString(),
                      ),
                      const SizedBox(height: 6),
                      _mobileInfoRow(
                        context,
                        'Party',
                        (party['name'] ?? '-').toString(),
                        onTap: _asInt(entry['party_id']) == null
                            ? null
                            : () => Get.to(
                                  () => PartyHistoryScreen(
                                    partyId: _asInt(entry['party_id'])!,
                                  ),
                                ),
                      ),
                      const SizedBox(height: 10),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: <Widget>[
                          _amountChip(
                            context,
                            'Dr',
                            debit > 0 ? controller.formatCurrency(debit) : '-',
                            const Color(0xFF2563EB),
                          ),
                          _amountChip(
                            context,
                            'Cr',
                            credit > 0 ? controller.formatCurrency(credit) : '-',
                            const Color(0xFFEF4444),
                          ),
                          _amountChip(
                            context,
                            'Bal',
                            '${controller.formatCurrency(entry['running_balance'])} ${_drCr((entry['balance_type'] ?? '').toString())}',
                            const Color(0xFF475569),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: <Widget>[
                          if (voucher.isNotEmpty)
                            _mobileActionChip(
                              context,
                              icon: Icons.remove_red_eye_outlined,
                              label: 'View',
                              color: theme.colorScheme.primary,
                              onTap: () => _openVoucherDetail(entry, voucher, party),
                            ),
                          _mobileActionChip(
                            context,
                            icon: Icons.view_list_outlined,
                            label: 'History',
                            color: const Color(0xFF475569),
                            onTap: () {
                              final ledgerId = _asInt(entry['id']);
                              if (ledgerId == null) {
                                return;
                              }
                              Get.to(() => LedgerHistoryScreen(ledgerEntryId: ledgerId));
                            },
                          ),
                          _mobileActionChip(
                            context,
                            icon: FontAwesomeIcons.clockRotateLeft,
                            label: 'Audit',
                            color: const Color(0xFF38BDF8),
                            onTap: () {
                              final ledgerId = _asInt(entry['id']);
                              if (ledgerId == null) {
                                return;
                              }
                              Get.to(
                                () => const AuditLogsScreen(),
                                binding: BindingsBuilder(() {
                                  Get.put(
                                    AuditLogsController(
                                      Get.find<AuditLogsRepository>(),
                                      initialModule: 'ledger',
                                      initialRecordId: ledgerId.toString(),
                                    ),
                                  );
                                }),
                              );
                            },
                          ),
                        ],
                      ),
                    ],
                  ),
                );
              },
            ),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: const BoxDecoration(
              color: Color(0xFF23263A),
              borderRadius: BorderRadius.vertical(bottom: Radius.circular(18)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  'Total',
                  style: theme.textTheme.labelLarge?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  'Dr ${controller.formatCurrency(reportData['total_debit'])}  |  Cr ${controller.formatCurrency(reportData['total_credit'])}',
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  'Closing ${controller.formatCurrency((reportData['closing_balance'] as Map?)?['balance'])} ${_drCr((((reportData['closing_balance'] as Map?)?['type'] ?? '').toString()))}',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: Colors.white.withValues(alpha: .92),
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
          if (controller.isLoadingMoreEntries.value || controller.hasMoreEntries)
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 10, 12, 0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: <Widget>[
                  if (controller.isLoadingMoreEntries.value) ...<Widget>[
                    const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    ),
                    const SizedBox(width: 8),
                    Text(
                      'Loading more...',
                      style: theme.textTheme.bodySmall,
                    ),
                  ] else
                    TextButton(
                      onPressed: () => controller.loadEntries(reset: false),
                      child: Text(
                        'Load more (${controller.entries.length}/${controller.entriesTotal.value})',
                      ),
                    ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _mobileInfoRow(
    BuildContext context,
    String label,
    String value, {
    VoidCallback? onTap,
  }) {
    final theme = Theme.of(context);
    final valueWidget = onTap == null
        ? Text(
            value,
            style: theme.textTheme.bodyMedium?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          )
        : ReportLinkText(
            value,
            onTap: onTap,
            textAlign: TextAlign.left,
            maxLines: 2,
          );
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        SizedBox(
          width: 82,
          child: Text(
            label,
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
        const SizedBox(width: 8),
        Expanded(child: valueWidget),
      ],
    );
  }

  Widget _amountChip(
    BuildContext context,
    String label,
    String value,
    Color color,
  ) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .08),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withValues(alpha: .16)),
      ),
      child: RichText(
        text: TextSpan(
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
            color: color,
            fontWeight: FontWeight.w700,
          ),
          children: <InlineSpan>[
            TextSpan(text: '$label '),
            TextSpan(
              text: value,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: Theme.of(context).colorScheme.onSurface,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _mobileActionChip(
    BuildContext context, {
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
  }) {
    return OutlinedButton.icon(
      onPressed: onTap,
      style: OutlinedButton.styleFrom(
        foregroundColor: color,
        side: BorderSide(color: color.withValues(alpha: .22)),
        minimumSize: const Size(0, 36),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
        ),
      ),
      icon: Icon(icon, size: 14),
      label: Text(label),
    );
  }

  int? _asInt(dynamic value) => int.tryParse(value?.toString() ?? '');

  String _drCr(String type) {
    switch (type.toLowerCase()) {
      case 'debit':
      case 'dr':
        return 'Dr';
      case 'credit':
      case 'cr':
        return 'Cr';
      default:
        return '-';
    }
  }

  Future<void> _openVoucherDetail(
    Map<String, dynamic> entry,
    Map<String, dynamic> voucher,
    Map<String, dynamic> party,
  ) async {
    final summaryRecord = _buildVoucherRecord(entry, voucher, party);
    final voucherId = _asInt(voucher['id']);
    if (voucherId == null) {
      Get.to(() => TransactionDetailScreen(record: summaryRecord));
      return;
    }

    try {
      final response = await AppActionLoader.run(
        () => Get.find<TransactionsRepository>().fetchRecordDetail(
          endpoint: ApiEndpoints.voucherDetail(voucherId),
        ),
        message: 'Loading voucher details...',
      );
      final detail = response['data'];
      final record = detail is Map<String, dynamic>
          ? TransactionRecord.fromVoucher(detail)
          : summaryRecord;
      Get.to(() => TransactionDetailScreen(record: record));
    } catch (_) {
      AppSnackbar.warning(
        'Full voucher details could not be loaded. Showing available data.',
      );
      Get.to(() => TransactionDetailScreen(record: summaryRecord));
    }
  }

  TransactionRecord _buildVoucherRecord(
    Map<String, dynamic> entry,
    Map<String, dynamic> voucher,
    Map<String, dynamic> party,
  ) {
    final payload = <String, dynamic>{
      ...voucher,
      'party': party,
      'voucher_type': (voucher['voucher_type'] ?? entry['voucher_type'] ?? '').toString(),
      'voucher_number': (voucher['voucher_number'] ?? '').toString(),
      'voucher_date': (voucher['voucher_date'] ?? entry['transaction_date'] ?? '').toString(),
      'status': (voucher['status'] ?? 'posted').toString(),
      'narration': (entry['narration'] ?? entry['description'] ?? voucher['narration'] ?? '').toString(),
      'total_debit': _parseAmount(entry['debit']) + _parseAmount(entry['credit']),
      'party_id': party['id'],
    };

    return TransactionRecord.fromVoucher(payload);
  }

  double _parseAmount(dynamic value) {
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value?.toString() ?? '') ?? 0;
  }
}
