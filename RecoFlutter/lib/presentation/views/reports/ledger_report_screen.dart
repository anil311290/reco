import 'package:data_table_2/data_table_2.dart';
import 'dart:async';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../data/models/transactions/transaction_entities.dart';
import '../../controllers/reports/ledger_report_controller.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import '../../widgets/common/custom_text_field.dart';
import '../masters/history/party_history_screen.dart';
import '../masters/widgets/masters_ui_components.dart';
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
        final entries = report is Map<String, dynamic> && report['entries'] is List
            ? List<Map<String, dynamic>>.from(
                (report['entries'] as List).whereType<Map>(),
              )
            : <Map<String, dynamic>>[];
        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            ReportFilterPanel(
              title: 'Filters',
              subtitle: 'Select ledger and period to inspect running balances.',
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
                  ReportDateRangeRow(
                    fromController: controller.fromDateController,
                    toController: controller.toDateController,
                    onFromTap: () => _pickDate(controller.fromDateController),
                    onToTap: () => _pickDate(controller.toDateController),
                  ),
                  const SizedBox(height: 12),
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
                    'Choose a ledger and date range to view voucher-wise movement and running balance.',
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                ),
              )
            else ...<Widget>[
              if (selectedAccount != null) ...<Widget>[
                Row(
                  children: <Widget>[
                    Expanded(
                      child: ReportStatCard(
                        label: 'Account',
                        value: (selectedAccount['account_code'] ?? '-').toString(),
                        note: (selectedAccount['account_name'] ?? 'Ledger Account').toString(),
                        color: const Color(0xFF0F766E),
                        icon: FontAwesomeIcons.bookBookmark,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: ReportStatCard(
                        label: 'Opening Balance',
                        value: controller.formatCurrency(
                          (report['opening_balance'] as Map?)?['balance'],
                        ),
                        note: ((report['opening_balance'] as Map?)?['type'] ?? '-')
                            .toString()
                            .toUpperCase(),
                        color: const Color(0xFF2563EB),
                        icon: FontAwesomeIcons.circlePlay,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
              ],
              Row(
                children: <Widget>[
                  if (selectedAccount == null)
                    Expanded(
                      child: ReportStatCard(
                        label: 'Opening Balance',
                        value: controller.formatCurrency(
                          (report['opening_balance'] as Map?)?['balance'],
                        ),
                        note: ((report['opening_balance'] as Map?)?['type'] ?? '-')
                            .toString()
                            .toUpperCase(),
                        color: const Color(0xFF2563EB),
                        icon: FontAwesomeIcons.circlePlay,
                      ),
                    ),
                  if (selectedAccount == null) const SizedBox(width: 10),
                  Expanded(
                    child: ReportStatCard(
                      label: 'Closing Balance',
                      value: controller.formatCurrency(
                        (report['closing_balance'] as Map?)?['balance'],
                      ),
                      note: ((report['closing_balance'] as Map?)?['type'] ?? '-')
                          .toString()
                          .toUpperCase(),
                      color: const Color(0xFF16A34A),
                      icon: FontAwesomeIcons.circleStop,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
            ],
            ReportSectionCard(
              title: 'Ledger Entries',
              icon: FontAwesomeIcons.tableList,
              iconColor: const Color(0xFF475569),
              trailing: report is Map<String, dynamic>
                  ? Text(
                      'Dr ${controller.formatCurrency(report['total_debit'])} | Cr ${controller.formatCurrency(report['total_credit'])}',
                      style: Theme.of(context).textTheme.labelLarge?.copyWith(
                        color: const Color(0xFF475569),
                        fontWeight: FontWeight.w700,
                      ),
                    )
                  : null,
              child: entries.isEmpty && report is! Map
                  ? const Padding(
                      padding: EdgeInsets.all(24),
                      child: Center(child: Text('No entries found')),
                    )
                  : _buildLedgerTable(context, report, entries),
            ),
          ],
        );
      }),
    );
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
                '${controller.formatCurrency(openingBalance['balance'])} ${(openingBalance['type'] ?? '').toString().toUpperCase()}',
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
                      : () => Get.to(
                            () => TransactionDetailScreen(
                              record: _buildVoucherRecord(entry, voucher, party),
                            ),
                          ),
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
            masterTextCell('${controller.formatCurrency(entry['running_balance'])} ${(entry['balance_type'] ?? '').toString().toUpperCase()}'),
            DataCell(Center(
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  if (voucher.isNotEmpty) ...<Widget>[
                    MasterActionButton(
                      icon: Icons.remove_red_eye_outlined,
                      tooltip: 'View Voucher',
                      color: Theme.of(context).colorScheme.primary,
                      onTap: () => Get.to(
                        () => TransactionDetailScreen(
                          record: _buildVoucherRecord(entry, voucher, party),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                  ],
                  MasterActionButton(
                    icon: FontAwesomeIcons.clockRotateLeft,
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
          DataCell(Center(child: Text('${controller.formatCurrency((reportData['closing_balance'] as Map?)?['balance'])} ${((reportData['closing_balance'] as Map?)?['type'] ?? '').toString().toUpperCase()}', style: reportTotalRowTextStyle(context)?.copyWith(fontSize: 13)))),
          const DataCell(SizedBox.shrink()),
        ],
      ),
    ];

    final calculatedHeight = 42.0 + (entries.length * 52.0);
    final tableHeight = calculatedHeight.clamp(160.0, 550.0);

    return SizedBox(
      height: tableHeight,
      child: MastersTableShell(
        isLoading: false,
        emptyText: 'No entries found',
        minWidth: 1120,
        columns: <DataColumn2>[
          masterColumn(context, 'Date'),
          masterColumn(context, 'Voucher #'),
          masterColumn(context, 'Particulars', size: ColumnSize.L),
          masterColumn(context, 'Party'),
          masterColumn(context, 'Debit'),
          masterColumn(context, 'Credit'),
          masterColumn(context, 'Balance'),
          masterColumn(context, 'Actions', fixedWidth: 100),
        ],
        rows: tableRows,
      ),
    );
  }

  Future<void> _pickDate(TextEditingController controller) async {
    final selected = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (selected != null) {
      controller.text = selected.toIso8601String().substring(0, 10);
    }
  }

  int? _asInt(dynamic value) => int.tryParse(value?.toString() ?? '');

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
