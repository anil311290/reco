import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../bindings/reports_binding.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import 'balance_sheet_report_screen.dart';
import 'bank_book_report_screen.dart';
import 'cash_book_report_screen.dart';
import 'creditors_outstanding_report_screen.dart';
import 'day_book_report_screen.dart';
import 'debtors_outstanding_report_screen.dart';
import 'ledger_report_screen.dart';
import 'profit_loss_report_screen.dart';
import 'trial_balance_report_screen.dart';
import 'widgets/report_ui_components.dart';

class ReportsScreen extends StatelessWidget {
  const ReportsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<ReportLookupController>()) {
      ReportsBinding().dependencies();
    }

    final items = <ReportFeatureItem>[
      ReportFeatureItem(
        title: 'Day Book',
        subtitle: 'All posted voucher lines for a selected date.',
        icon: FontAwesomeIcons.calendarDay,
        color: const Color(0xFF0891B2),
        onTap: () => Get.to(() => const DayBookReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Cash Book',
        subtitle: 'Cash ledger movement with opening and closing.',
        icon: FontAwesomeIcons.moneyBillWave,
        color: const Color(0xFF059669),
        onTap: () => Get.to(() => const CashBookReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Bank Book',
        subtitle: 'Bank and OD account statements.',
        icon: FontAwesomeIcons.buildingColumns,
        color: const Color(0xFF2563EB),
        onTap: () => Get.to(() => const BankBookReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Ledger',
        subtitle: 'Account-wise ledger with running balance.',
        icon: FontAwesomeIcons.bookOpen,
        color: const Color(0xFF475569),
        onTap: () => Get.to(() => const LedgerReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Trial Balance',
        subtitle: 'Debit and credit closing balances for all ledgers.',
        icon: FontAwesomeIcons.scaleBalanced,
        color: const Color(0xFFD97706),
        onTap: () => Get.to(() => const TrialBalanceReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Profit & Loss',
        subtitle: 'Income, expense, and final profitability snapshot.',
        icon: FontAwesomeIcons.chartLine,
        color: const Color(0xFF16A34A),
        onTap: () => Get.to(() => const ProfitLossReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Balance Sheet',
        subtitle: 'Assets, liabilities, and equity statement.',
        icon: FontAwesomeIcons.fileInvoiceDollar,
        color: const Color(0xFF2563EB),
        onTap: () => Get.to(() => const BalanceSheetReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Receivables',
        subtitle: 'Debtors outstanding from party balances.',
        icon: FontAwesomeIcons.handHoldingDollar,
        color: const Color(0xFFDC2626),
        onTap: () => Get.to(() => const DebtorsOutstandingReportScreen()),
      ),
      ReportFeatureItem(
        title: 'Payables',
        subtitle: 'Creditors outstanding from party balances.',
        icon: FontAwesomeIcons.wallet,
        color: const Color(0xFF7C3AED),
        onTap: () => Get.to(() => const CreditorsOutstandingReportScreen()),
      ),
    ];

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
            padding: const EdgeInsets.all(14),
            children: <Widget>[
              _ReportsHeroCard(reportCount: items.length),
              const SizedBox(height: 14),
              GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: crossAxisCount,
                  crossAxisSpacing: 10,
                  mainAxisSpacing: 10,
                  mainAxisExtent: mainAxisExtent,
                ),
                itemCount: items.length,
                itemBuilder: (context, index) =>
                    ReportFeatureCard(item: items[index]),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _ReportsHeroCard extends StatelessWidget {
  const _ReportsHeroCard({required this.reportCount});

  final int reportCount;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final primary = theme.colorScheme.primary;

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: <Color>[
            primary.withValues(alpha: .12),
            primary.withValues(alpha: .05),
            theme.cardColor,
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(
          color: primary.withValues(alpha: .16),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: primary.withValues(alpha: .10),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                Icon(
                  FontAwesomeIcons.chartSimple,
                  size: 13,
                  color: primary,
                ),
                const SizedBox(width: 8),
                Text(
                  'Books & Statements',
                  style: theme.textTheme.labelLarge?.copyWith(
                    color: primary,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
          Text(
            'Financial Reports',
            style: theme.textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Tally-style sequence: books first, then trial balance and statutory statements, then receivables and payables.',
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              height: 1.4,
            ),
          ),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
            decoration: BoxDecoration(
              color: theme.cardColor,
              borderRadius: BorderRadius.circular(999),
              border: Border.all(
                color: theme.dividerColor.withValues(alpha: .35),
              ),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                Icon(
                  FontAwesomeIcons.tableCellsLarge,
                  size: 13,
                  color: const Color(0xFF2563EB),
                ),
                const SizedBox(width: 8),
                Text(
                  '$reportCount report views',
                  style: theme.textTheme.labelLarge?.copyWith(
                    fontWeight: FontWeight.w700,
                    color: const Color(0xFF2563EB),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
