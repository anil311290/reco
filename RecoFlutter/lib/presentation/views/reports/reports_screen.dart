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
        actions: <Widget>[
          IconButton(
            onPressed: () {},
            icon: const Icon(Icons.notifications_none_rounded),
          ),
          const SizedBox(width: 4),
        ],
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
          return GridView.builder(
            padding: const EdgeInsets.all(14),
            gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: crossAxisCount,
              crossAxisSpacing: 10,
              mainAxisSpacing: 10,
              mainAxisExtent: mainAxisExtent,

            ),
            itemCount: items.length,
            itemBuilder: (context, index) => ReportFeatureCard(item: items[index]),
          );
        },
      ),
    );
  }
}
