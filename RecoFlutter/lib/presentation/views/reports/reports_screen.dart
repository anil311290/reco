import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../bindings/reports_binding.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import 'balance_sheet_report_screen.dart';
import 'creditors_outstanding_report_screen.dart';
import 'day_book_report_screen.dart';
import 'debtors_outstanding_report_screen.dart';
import 'extended_reports_screens.dart';
import 'ledger_report_screen.dart';
import 'profit_loss_report_screen.dart';
import 'receipt_payment_report_screen.dart';
import 'trial_balance_report_screen.dart';
import 'widgets/report_ui_components.dart';

class ReportsScreen extends StatelessWidget {
  const ReportsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<ReportLookupController>()) {
      ReportsBinding().dependencies();
    }

    // Web sidebar → Accounting Reports
    final accountingItems = <ReportFeatureItem>[
      ReportFeatureItem(
        title: 'Day Book',
        subtitle:
            'All posted voucher lines for a selected date with debit / credit particulars.',
        icon: FontAwesomeIcons.calendarDay.data,
        color: const Color(0xFF0891B2),
        onTap: () => Get.to(() => const DayBookReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Ledger',
        subtitle:
            'Account-wise ledger with opening balance, running balance, and period filter.',
        icon: FontAwesomeIcons.bookOpen.data,
        color: const Color(0xFF475569),
        onTap: () => Get.to(() => const LedgerReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Trial Balance',
        subtitle:
            'Debit and credit closing balances for all ledgers — books must tally.',
        icon: FontAwesomeIcons.scaleBalanced.data,
        color: const Color(0xFFD97706),
        onTap: () => Get.to(() => const TrialBalanceReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Profit & Loss',
        subtitle:
            'Income and expense summary with net profit / loss for the financial year.',
        icon: FontAwesomeIcons.chartLine.data,
        color: const Color(0xFF16A34A),
        onTap: () => Get.to(() => const ProfitLossReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Receipt & Payment',
        subtitle:
            'Cash, bank, and OD movement head-wise with opening and closing balances.',
        icon: FontAwesomeIcons.moneyBillTransfer.data,
        color: const Color(0xFF059669),
        onTap: () => Get.to(() => const ReceiptPaymentReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Balance Sheet',
        subtitle:
            'Assets, liabilities, and equity — financial position as on date.',
        icon: FontAwesomeIcons.fileInvoiceDollar.data,
        color: const Color(0xFF2563EB),
        onTap: () => Get.to(() => const BalanceSheetReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Stock Register',
        subtitle: 'Item-wise stock movements for a selected period.',
        icon: FontAwesomeIcons.boxesStacked.data,
        color: const Color(0xFF0284C7),
        onTap: () => Get.to(() => const StockRegisterReportScreen()),
      ),
    ];

    // Web sidebar → AP / AR Reports
    final apArItems = <ReportFeatureItem>[
      ReportFeatureItem(
        title: 'Receivables',
        subtitle:
            'Invoice-wise and party-wise debtors outstanding with aging filters.',
        icon: FontAwesomeIcons.handHoldingDollar.data,
        color: const Color(0xFFDC2626),
        onTap: () => Get.to(() => const DebtorsOutstandingReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Payables',
        subtitle:
            'Invoice-wise and party-wise creditors outstanding with aging filters.',
        icon: FontAwesomeIcons.wallet.data,
        color: const Color(0xFF7C3AED),
        onTap: () => Get.to(() => const CreditorsOutstandingReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Unapplied Cash',
        subtitle: 'Receipts and payments not fully applied to invoices.',
        icon: FontAwesomeIcons.circleDollarToSlot.data,
        color: const Color(0xFF0D9488),
        onTap: () => Get.to(() => const UnappliedReceiptsReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Settlement Audit',
        subtitle: 'Payment–invoice mapping audit with status filters.',
        icon: FontAwesomeIcons.fileCircleCheck.data,
        color: const Color(0xFF4338CA),
        onTap: () => Get.to(() => const SettlementAuditReportScreen()),
      ),
    ];

    // Extra vs web sidebar — keep commented for easy restore.
    // ReportFeatureItem(
    //   title: 'Aging Summary',
    //   subtitle: 'Combined AR/AP overdue buckets and aging detail.',
    //   icon: FontAwesomeIcons.hourglassHalf.data,
    //   color: const Color(0xFFEA580C),
    //   onTap: () => Get.to(() => const AgingSummaryReportScreen()),
    // ),

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Reports',
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
            fontWeight: FontWeight.w700,
            fontSize: 15,
          ),
        ),
      ),
      body: LayoutBuilder(
        builder: (context, constraints) {
          final double width = constraints.maxWidth;
          final int crossAxisCount = width >= 1040
              ? 4
              : width >= 760
              ? 3
              : width >= 440
              ? 2
              : 1;
          final double mainAxisExtent = crossAxisCount == 1 ? 130 : 186;

          return ListView(
            padding: const EdgeInsets.fromLTRB(14, 10, 14, 20),
            children: <Widget>[
              _ReportsSectionHeader(
                title: 'Accounting Reports',
                subtitle: 'Books, ledgers and financial statements',
                count: accountingItems.length,
              ),
              const SizedBox(height: 10),
              _ReportsFeatureGrid(
                items: accountingItems,
                crossAxisCount: crossAxisCount,
                mainAxisExtent: mainAxisExtent,
              ),
              const SizedBox(height: 18),
              _ReportsSectionHeader(
                title: 'AP / AR Reports',
                subtitle: 'Receivables, payables and unapplied cash',
                count: apArItems.length,
              ),
              const SizedBox(height: 10),
              _ReportsFeatureGrid(
                items: apArItems,
                crossAxisCount: crossAxisCount,
                mainAxisExtent: mainAxisExtent,
              ),
            ],
          );
        },
      ),
    );
  }
}

class _ReportsSectionHeader extends StatelessWidget {
  const _ReportsSectionHeader({
    required this.title,
    required this.subtitle,
    required this.count,
  });

  final String title;
  final String subtitle;
  final int count;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    const primary = Color(0xFF2979FF);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: theme.dividerColor.withValues(alpha: .35)),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: Colors.black.withValues(alpha: .035),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Row(
        children: <Widget>[
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  title,
                  style: theme.textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.w800,
                    fontSize: 17,
                    letterSpacing: -.3,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                    fontSize: 11.5,
                  ),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: primary.withValues(alpha: .08),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              '$count',
              style: theme.textTheme.labelLarge?.copyWith(
                fontWeight: FontWeight.w800,
                color: primary,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ReportsFeatureGrid extends StatelessWidget {
  const _ReportsFeatureGrid({
    required this.items,
    required this.crossAxisCount,
    required this.mainAxisExtent,
  });

  final List<ReportFeatureItem> items;
  final int crossAxisCount;
  final double mainAxisExtent;

  @override
  Widget build(BuildContext context) {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: crossAxisCount,
        crossAxisSpacing: 10,
        mainAxisSpacing: 10,
        mainAxisExtent: mainAxisExtent,
      ),
      itemCount: items.length,
      itemBuilder: (context, index) => ReportFeatureCard(item: items[index]),
    );
  }
}
