import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/theme/app_colors.dart';
import '../../bindings/masters_binding.dart';
import '../../bindings/reports_binding.dart';
import '../../bindings/transactions_binding.dart';
import '../../../data/models/transactions/transaction_entities.dart';
import '../../controllers/main/main_controller.dart';
import '../../controllers/masters/masters_shell_controller.dart';
import '../../controllers/dashboard/dashboard_controller.dart';
import '../../controllers/reports/report_lookup_controller.dart';
import '../../controllers/transactions/transactions_shell_controller.dart';
import '../reports/receipt_payment_report_screen.dart';
import '../reports/creditors_outstanding_report_screen.dart';
import '../reports/debtors_outstanding_report_screen.dart';
import '../reports/profit_loss_report_screen.dart';
import '../transactions/details/transaction_detail_screen.dart';

class DashboardScreen extends GetView<DashboardController> {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title:   Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16.0,vertical: 12),
          child: _DashboardHeader(controller: controller),
        ),
          titleSpacing: 0,
        // title: Text(
        //   'Dashboard',
        //   style: Theme.of(context).textTheme.titleMedium?.copyWith(
        //     fontWeight: FontWeight.w700,
        //   ),
        // ),
        // actions: <Widget>[
        //   IconButton(
        //     onPressed: Get.find<ThemeController>().toggleTheme,
        //     icon: const Icon(Icons.dark_mode_outlined),
        //   ),
        //   IconButton(
        //     onPressed: controller.logout,
        //     icon: const Icon(Icons.logout_rounded),
        //   ),
        // ],
      ),
      body: Obx(
            () => RefreshIndicator(
          onRefresh: () => controller.loadDashboard(showLoader: false),
          child: ListView(
            padding: const EdgeInsets.fromLTRB(12, 10, 12, 20),
            children: <Widget>[
             const SizedBox(height: 8),
              _StatusBanner(controller: controller),
              const SizedBox(height: 12),
              if (controller.isLoading.value && controller.dashboardData.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 80),
                  child: Center(child: CircularProgressIndicator()),
                )
              else ...<Widget>[
                _MetricsGrid(controller: controller),
                const SizedBox(height: 10),
                _QuickActionsCard(onAction: _openAction),
                const SizedBox(height: 10),
                _IncomeExpenseCard(controller: controller),
                const SizedBox(height: 10),
                _RecentTransactionsCard(controller: controller),
                const SizedBox(height: 10),
                _SingleTrendCard(
                  title: 'Receivables Trend',
                  icon: FontAwesomeIcons.chartLine,
                  color: Theme.of(context).colorScheme.tertiary,
                  values: controller.receivableSeries,
                  labels: controller.receivableLabels,
                  isRefreshing: controller.isRefreshing.value,
                  formatCurrency: controller.formatCurrency,
                ),
                const SizedBox(height: 10),
                _SingleTrendCard(
                  title: 'Payables Trend',
                  icon: FontAwesomeIcons.chartColumn,
                  color: Theme.of(context).colorScheme.error,
                  values: controller.payableSeries,
                  labels: controller.payableLabels,
                  isRefreshing: controller.isRefreshing.value,
                  formatCurrency: controller.formatCurrency,
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  void _openAction(_DashboardAction action) {
    switch (action) {
      case _DashboardAction.income:
        _openTransactionsTab(TransactionsTab.sales);
      case _DashboardAction.expense:
        _openTransactionsTab(TransactionsTab.adjustments);
      case _DashboardAction.receipt:
        _openTransactionsTab(TransactionsTab.receipts);
      case _DashboardAction.payment:
        _openTransactionsTab(TransactionsTab.payments);
      case _DashboardAction.party:
        _openMastersTab(MastersTab.parties);
      case _DashboardAction.reports:
        _openReportsHome();
    }
  }

  void _openReportsHome() {
    Get.find<MainController>().changeTab(3);
  }

  void _openMastersTab(MastersTab tab) {
    if (!Get.isRegistered<MastersShellController>()) {
      MastersBinding().dependencies();
    }
    Get.find<MainController>().changeTab(0);
    Get.find<MastersShellController>().changeTab(tab);
  }

  void _openTransactionsTab(TransactionsTab tab) {
    if (!Get.isRegistered<TransactionsShellController>()) {
      TransactionsBinding().dependencies();
    }
    Get.find<MainController>().changeTab(1);
    Get.find<TransactionsShellController>().changeTab(tab);
  }

}

class _DashboardHeader extends StatelessWidget {
  const _DashboardHeader({required this.controller});

  final DashboardController controller;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: <Widget>[
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Text(
                'Dashboard',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w800,
                  letterSpacing: -0.2,
                  fontSize: 20,
                ),
              ),
              const SizedBox(height: 3),
              Text(
                'Welcome back, ${controller.userName.value}. Here is your financial overview.',
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                  fontSize: 11.5,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(width: 10),
        PopupMenuButton<String>(
          initialValue: controller.selectedRange.value,
          onSelected: controller.changeRange,
          itemBuilder: (_) => DashboardController.rangeOptions
              .map(
                (item) => PopupMenuItem<String>(
              value: item,
              child: Text(_rangeLabel(item)),
            ),
          )
              .toList(),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 8),
            decoration: BoxDecoration(
              color: Theme.of(context).cardColor,
              borderRadius: BorderRadius.circular(9),
              border: Border.all(color: Theme.of(context).colorScheme.outline),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                Text(
                  _rangeLabel(controller.selectedRange.value),
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    fontWeight: FontWeight.w700,
                    fontSize: 11.5,
                  ),
                ),
                const SizedBox(width: 6),
                const Icon(Icons.keyboard_arrow_down_rounded, size: 18),
              ],
            ),
          ),
        ),
      ],
    );
  }

  String _rangeLabel(String value) {
    switch (value) {
      case 'this_month':
        return 'This Month';
      case 'last_month':
        return 'Last Month';
      case 'this_quarter':
        return 'This Quarter';
      default:
        return 'This Year';
    }
  }
}

class _StatusBanner extends StatelessWidget {
  const _StatusBanner({required this.controller});

  final DashboardController controller;

  @override
  Widget build(BuildContext context) {
    final online = controller.isOnline;
    final color = online
        ? AppColors.success
        : AppColors.warning;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .10),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: .16)),
      ),
      child: Row(
        children: <Widget>[
          Icon(
            online ? Icons.cloud_done_rounded : Icons.cloud_off_rounded,
            size: 18,
            color: color,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              online
                  ? 'Web admin dashboard data with local-first refresh active.'
                  : 'Offline mode active. Dashboard is reading local cached data.',
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: color,
                fontWeight: FontWeight.w700,
                fontSize: 11.5,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _MetricsGrid extends StatelessWidget {
  const _MetricsGrid({required this.controller});

  final DashboardController controller;

  @override
  Widget build(BuildContext context) {
    final statistics = controller.statistics;
    final metrics = <_DashboardMetric>[
      _DashboardMetric(
        title: 'TOTAL INCOME',
        value: controller.formatCurrency(statistics['income']),
        caption: 'year to date',
        icon: Icons.south_west_rounded,
        color: const Color(0xFF16A36A),
        target: _DashboardMetricTarget.profitLoss,
      ),
      _DashboardMetric(
        title: 'TOTAL EXPENSE',
        value: controller.formatCurrency(statistics['expense']),
        caption: 'year to date',
        icon: Icons.north_east_rounded,
        color: const Color(0xFFEF5B62),
        target: _DashboardMetricTarget.profitLoss,
      ),
      _DashboardMetric(
        title: 'NET PROFIT',
        value: controller.formatCurrency(statistics['profit']),
        caption: 'since opening',
        icon: Icons.trending_up_rounded,
        color: const Color(0xFF2E7BEF),
        target: _DashboardMetricTarget.profitLoss,
      ),
      _DashboardMetric(
        title: 'CASH BALANCE',
        value: controller.formatCurrency(statistics['cash_balance']),
        caption: 'available balance',
        icon: Icons.account_balance_wallet_outlined,
        color: const Color(0xFFF29B38),
        target: _DashboardMetricTarget.receiptPayment,
      ),
      _DashboardMetric(
        title: 'RECEIVABLES',
        value: controller.formatCurrency(statistics['receivables']),
        caption: 'outstanding amount',
        icon: Icons.groups_2_outlined,
        color: const Color(0xFF4B79E8),
        target: _DashboardMetricTarget.receivables,
      ),
      _DashboardMetric(
        title: 'PAYABLES',
        value: controller.formatCurrency(statistics['payables']),
        caption: 'outstanding amount',
        icon: Icons.group_outlined,
        color: const Color(0xFF9B62D4),
        target: _DashboardMetricTarget.payables,
      ),
    ];

    return LayoutBuilder(
      builder: (context, constraints) {
        final columns = constraints.maxWidth >= 720 ? 3 : 2;
        return GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: metrics.length,
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: columns,
            mainAxisSpacing: 8,
            crossAxisSpacing: 8,
            childAspectRatio: columns == 3 ? 1.5 : 1.6,
            mainAxisExtent: columns == 3 ? 108 : 102,
          ),
          itemBuilder: (context, index) => _MetricCard(metric: metrics[index]),
        );
      },
    );
  }
}

enum _DashboardMetricTarget { profitLoss, receiptPayment, receivables, payables }

void _openDashboardMetricTarget(_DashboardMetricTarget target) {
  if (!Get.isRegistered<ReportLookupController>()) {
    ReportsBinding().dependencies();
  }
  switch (target) {
    case _DashboardMetricTarget.profitLoss:
      Get.to(() => const ProfitLossReportScreen());
      break;
    case _DashboardMetricTarget.receiptPayment:
      Get.to(() => const ReceiptPaymentReportScreen());
      break;
    case _DashboardMetricTarget.receivables:
      Get.to(() => const DebtorsOutstandingReportScreen());
      break;
    case _DashboardMetricTarget.payables:
      Get.to(() => const CreditorsOutstandingReportScreen());
      break;
  }
}

void _openAllTransactions() {
  if (!Get.isRegistered<TransactionsShellController>()) {
    TransactionsBinding().dependencies();
  }
  Get.find<MainController>().changeTab(1);
}

class _DashboardMetric {
  const _DashboardMetric({
    required this.title,
    required this.value,
    required this.caption,
    required this.icon,
    required this.color,
    required this.target,
  });

  final String title;
  final String value;
  final String caption;
  final IconData icon;
  final Color color;
  final _DashboardMetricTarget target;
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({required this.metric});

  final _DashboardMetric metric;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () => _openDashboardMetricTarget(metric.target),
      borderRadius: BorderRadius.circular(12),
      child: Card(
        child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Row(
              children: <Widget>[
                Expanded(
                  child: Text(
                    metric.title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.labelSmall?.copyWith(
                      fontWeight: FontWeight.w700,
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                      fontSize: 10.5,
                      letterSpacing: .3,
                    ),
                  ),
                ),
                Container(
                  width: 28,
                  height: 28,
                  decoration: BoxDecoration(
                    color: metric.color.withValues(alpha: .10),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(metric.icon, size: 14, color: metric.color),
                ),
              ],
            ),
            const SizedBox(height: 6),
            FittedBox(
              fit: BoxFit.scaleDown,
              alignment: Alignment.centerLeft,
              child: Text(
                metric.value,
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w800,
                  color: metric.color,
                  fontSize: 18,
                  height: 1.05,
                ),
              ),
            ),
            const SizedBox(height: 2),
            Text(
              metric.caption,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
                fontSize: 10.5,
              ),
            ),
          ],
        ),
      ),
      ),
    );
  }
}

enum _DashboardAction { income, expense, receipt, payment, party, reports }

class _QuickActionsCard extends StatelessWidget {
  const _QuickActionsCard({required this.onAction});

  final ValueChanged<_DashboardAction> onAction;

  @override
  Widget build(BuildContext context) {
    final actions = <(String, IconData, Color, _DashboardAction)>[
      ('Payment', FontAwesomeIcons.arrowTrendUp, const Color(0xFFF59E0B), _DashboardAction.payment),
      ('Receipt', FontAwesomeIcons.arrowTrendDown, const Color(0xFF2563EB), _DashboardAction.receipt),
      ('Adjust', FontAwesomeIcons.bookBookmark, const Color(0xFF8B5CF6), _DashboardAction.expense),
      ('Invoice', FontAwesomeIcons.fileCirclePlus, const Color(0xFF16A36A), _DashboardAction.income),
      ('Party', FontAwesomeIcons.userPlus, const Color(0xFF475569), _DashboardAction.party),
      ('Reports', FontAwesomeIcons.chartSimple, const Color(0xFFEF5B62), _DashboardAction.reports),
    ];

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            const _CardTitle(
              title: 'Quick Actions',
              icon: FontAwesomeIcons.bolt,
              iconColor: Color(0xFFF59E0B),
            ),
            const SizedBox(height: 10),
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: actions.length,
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 3,
                mainAxisSpacing: 7,
                crossAxisSpacing: 7,
                mainAxisExtent: 78,
              ),
          itemBuilder: (context, index) {
            final action = actions[index];
            return Material(
                  color: action.$3.withValues(alpha: .05),
                  borderRadius: BorderRadius.circular(10),
                  child: InkWell(
                    onTap: () => onAction(action.$4),
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: action.$3.withValues(alpha: .18)),
                      ),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: <Widget>[
                          Icon(action.$2, color: action.$3, size: 16),
                          const SizedBox(height: 6),
                          Text(
                            action.$1,
                            textAlign: TextAlign.center,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: Theme.of(context).textTheme.bodySmall
                                ?.copyWith(
                              fontWeight: FontWeight.w700,
                              fontSize: 11,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _IncomeExpenseCard extends StatelessWidget {
  const _IncomeExpenseCard({required this.controller});

  final DashboardController controller;

  @override
  Widget build(BuildContext context) {
    final hasChartData =
        controller.incomeSeries.any((value) => value > 0) ||
            controller.expenseSeries.any((value) => value > 0);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Row(
              children: <Widget>[
                const Expanded(
                  child: _CardTitle(
                    title: 'Income vs Expense',
                    icon: FontAwesomeIcons.chartColumn,
                    iconColor: Color(0xFF2563EB),
                  ),
                ),
                const SizedBox(width: 8),
                Flexible(
                  child: FittedBox(
                    fit: BoxFit.scaleDown,
                    alignment: Alignment.centerRight,
                    child: _PeriodTabs(controller: controller),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            if (controller.isRefreshing.value) ...<Widget>[
              LinearProgressIndicator(
                minHeight: 2,
                color: Theme.of(context).colorScheme.primary,
                backgroundColor: Theme.of(
                  context,
                ).colorScheme.primary.withValues(alpha: .10),
              ),
              const SizedBox(height: 10),
            ],
            if (hasChartData) ...<Widget>[
              const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: <Widget>[
                  _ChartLegend(label: 'Income', color: Color(0xFF26B874)),
                  SizedBox(width: 18),
                  _ChartLegend(label: 'Expense', color: Color(0xFFEF5B62)),
                ],
              ),
              const SizedBox(height: 8),
              _TrendChart(
                primaryValues: controller.incomeSeries,
                secondaryValues: controller.expenseSeries,
                primaryColor: const Color(0xFF26B874),
                secondaryColor: const Color(0xFFEF5B62),
                labels: controller.incomeExpenseLabels,
              ),
              const SizedBox(height: 10),
              Row(
                children: <Widget>[
                  Expanded(
                    child: _MiniStatChip(
                      label: 'Income Total',
                      value: controller.formatCurrency(
                        controller.incomeSeries.fold<double>(
                          0,
                          (sum, item) => sum + item,
                        ),
                      ),
                      color: const Color(0xFF26B874),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: _MiniStatChip(
                      label: 'Expense Total',
                      value: controller.formatCurrency(
                        controller.expenseSeries.fold<double>(
                          0,
                          (sum, item) => sum + item,
                        ),
                      ),
                      color: const Color(0xFFEF5B62),
                    ),
                  ),
                ],
              ),
            ] else
              const _ChartEmptyState(
                title: 'No chart data available',
                subtitle: 'Income and expense graph will appear once posted data is available.',
              ),
          ],
        ),
      ),
    );
  }
}

class _PeriodTabs extends StatelessWidget {
  const _PeriodTabs({required this.controller});

  final DashboardController controller;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        border: Border.all(color: Theme.of(context).colorScheme.outline),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: List<Widget>.generate(
          DashboardController.groupOptions.length,
              (index) {
            final key = DashboardController.groupOptions[index];
            final selected = controller.selectedGroup.value == key;
            return InkWell(
              onTap: () => controller.changeGroup(key),
              borderRadius: BorderRadius.circular(8),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 180),
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: selected
                      ? Theme.of(context).colorScheme.primary
                      : Colors.transparent,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  _groupLabel(key),
                  style: Theme.of(context).textTheme.labelSmall?.copyWith(
                    color: selected
                        ? Theme.of(context).colorScheme.onPrimary
                        : Theme.of(context).colorScheme.onSurfaceVariant,
                    fontWeight: FontWeight.w700,
                    fontSize: 10.5,
                  ),
                ),
              ),
            );
          },
        ),
      ),
    );
  }

  String _groupLabel(String key) {
    switch (key) {
      case 'quarterly':
        return 'Quarterly';
      case 'yearly':
        return 'Yearly';
      default:
        return 'Monthly';
    }
  }
}

class _RecentTransactionsCard extends StatelessWidget {
  const _RecentTransactionsCard({required this.controller});

  final DashboardController controller;

  @override
  Widget build(BuildContext context) {
    final activities = controller.recentTransactions;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          children: <Widget>[
            Row(
              children: <Widget>[
                const Expanded(
                  child: _CardTitle(
                    title: 'Recent Transactions',
                    icon: FontAwesomeIcons.clockRotateLeft,
                    iconColor: Color(0xFF64748B),
                  ),
                ),
                TextButton(
                  onPressed: _openAllTransactions,
                  child: const Text('View All'),
                ),
              ],
            ),
            if (activities.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 30),
                child: Column(
                  children: <Widget>[
                    Icon(
                      Icons.inbox_outlined,
                      size: 34,
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'No transactions yet',
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w700,
                        fontSize: 14,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Start by creating a voucher to see transactions here.',
                      textAlign: TextAlign.center,
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                        fontSize: 11.5,
                      ),
                    ),
                  ],
                ),
              )
            else
              ...activities.map((activity) {
                final party = activity['party'];
                final partyName = party is Map<String, dynamic>
                    ? (party['name'] ?? 'No party').toString()
                    : 'No party';
                final type = (activity['voucher_type'] ?? '').toString();
                return ListTile(
                  contentPadding: EdgeInsets.zero,
                  onTap: () => Get.to(
                    () => TransactionDetailScreen(
                      record: TransactionRecord.fromVoucher(activity),
                    ),
                  ),
                  leading: Container(
                    width: 38,
                    height: 38,
                    decoration: BoxDecoration(
                      color: _voucherColor(type).withValues(alpha: .1),
                      borderRadius: BorderRadius.circular(9),
                    ),
                    child: Icon(
                      _voucherIcon(type),
                      color: _voucherColor(type),
                      size: 16,
                    ),
                  ),
                  title: Text(
                    partyName,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                      fontSize: 12.5,
                    ),
                  ),
                  subtitle: Text(
                    '${activity['voucher_number'] ?? 'Voucher'} • ${controller.formatDate((activity['voucher_date'] ?? '').toString())}',
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                      fontSize: 10.5,
                    ),
                  ),
                  trailing: Text(
                    controller.formatCurrency(activity['total_debit']),
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: _voucherColor(type),
                      fontWeight: FontWeight.w800,
                      fontSize: 12,
                    ),
                  ),
                );
              }),
          ],
        ),
      ),
    );
  }

  IconData _voucherIcon(String type) {
    switch (type.toLowerCase()) {
      case 'income':
      case 'receipt':
        return Icons.south_west_rounded;
      case 'payment':
      case 'expense':
      case 'purchase':
        return Icons.north_east_rounded;
      default:
        return Icons.receipt_long_outlined;
    }
  }

  Color _voucherColor(String type) {
    switch (type.toLowerCase()) {
      case 'income':
      case 'receipt':
        return const Color(0xFF16A36A);
      case 'payment':
      case 'expense':
      case 'purchase':
        return const Color(0xFFEF5B62);
      default:
        return const Color(0xFF2E7BEF);
    }
  }
}

class _SingleTrendCard extends StatelessWidget {
  const _SingleTrendCard({
    required this.title,
    required this.icon,
    required this.color,
    required this.values,
    required this.labels,
    required this.isRefreshing,
    required this.formatCurrency,
  });

  final String title;
  final IconData icon;
  final Color color;
  final List<double> values;
  final List<String> labels;
  final bool isRefreshing;
  final String Function(dynamic) formatCurrency;

  @override
  Widget build(BuildContext context) {
    final hasChartData = values.any((value) => value > 0);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            _CardTitle(title: title, icon: icon, iconColor: color),
            const SizedBox(height: 10),
            if (isRefreshing) ...<Widget>[
              LinearProgressIndicator(
                minHeight: 2,
                color: color,
                backgroundColor: color.withValues(alpha: .10),
              ),
              const SizedBox(height: 10),
            ],
            if (hasChartData)
              Column(
                children: <Widget>[
                  _TrendChart(
                    primaryValues: values,
                    primaryColor: color,
                    labels: labels,
                    compact: true,
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: _MiniStatChip(
                          label: 'Latest',
                          value: values.isEmpty
                              ? 'Rs 0.00'
                              : formatCurrency(values.last),
                          color: color,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _MiniStatChip(
                          label: 'Peak',
                          value: values.isEmpty
                              ? 'Rs 0.00'
                              : formatCurrency(values.reduce(math.max)),
                          color: color,
                        ),
                      ),
                    ],
                  ),
                ],
              )
            else
              _ChartEmptyState(
                title: 'No trend data available',
                subtitle: '$title trend will appear after synced voucher activity.',
              ),
          ],
        ),
      ),
    );
  }
}

class _MiniStatChip extends StatelessWidget {
  const _MiniStatChip({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .08),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withValues(alpha: .14)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            label,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              fontSize: 10,
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.labelLarge?.copyWith(
              fontWeight: FontWeight.w700,
              color: color,
              fontSize: 11.5,
            ),
          ),
        ],
      ),
    );
  }
}

class _CardTitle extends StatelessWidget {
  const _CardTitle({
    required this.title,
    this.icon,
    this.iconColor,
  });

  final String title;
  final IconData? icon;
  final Color? iconColor;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: <Widget>[
        if (icon != null) ...<Widget>[
          Icon(
            icon,
            size: 14,
            color: iconColor ?? Theme.of(context).colorScheme.primary,
          ),
          const SizedBox(width: 8),
        ],
        Flexible(
          child: Text(
            title,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
              fontSize: 14,
            ),
          ),
        ),
      ],
    );
  }
}

class _ChartLegend extends StatelessWidget {
  const _ChartLegend({required this.label, required this.color});

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: <Widget>[
        Container(
          width: 9,
          height: 9,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 5),
        Text(
          label,
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
            color: Theme.of(context).colorScheme.onSurfaceVariant,
            fontSize: 10.5,
          ),
        ),
      ],
    );
  }
}

class _TrendChart extends StatelessWidget {
  const _TrendChart({
    required this.primaryValues,
    required this.primaryColor,
    required this.labels,
    this.secondaryValues,
    this.secondaryColor,
    this.compact = false,
  });

  final List<double> primaryValues;
  final List<double>? secondaryValues;
  final Color primaryColor;
  final Color? secondaryColor;
  final List<String> labels;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final safeLabels = _visibleLabels(
      labels: labels,
      targetCount: compact ? 4 : 6,
    );

    return Column(
      children: <Widget>[
        SizedBox(
          height: compact ? 128 : 170,
          width: double.infinity,
          child: CustomPaint(
            painter: _TrendPainter(
              primaryValues: primaryValues,
              secondaryValues: secondaryValues,
              primaryColor: primaryColor,
              secondaryColor: secondaryColor,
              gridColor: Theme.of(context).dividerColor.withValues(alpha: .55),
            ),
          ),
        ),
        const SizedBox(height: 7),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: safeLabels
              .map(
                (label) => Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: Theme.of(context).textTheme.labelSmall?.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
                fontSize: 10,
              ),
            ),
          )
              .toList(),
        ),
      ],
    );
  }

  List<String> _visibleLabels({
    required List<String> labels,
    required int targetCount,
  }) {
    if (labels.length <= targetCount) {
      return labels;
    }

    final step = math.max(1, (labels.length - 1) ~/ (targetCount - 1));
    final visible = <String>[];

    for (var index = 0; index < labels.length; index += step) {
      visible.add(labels[index]);
      if (visible.length == targetCount - 1) {
        break;
      }
    }

    final lastLabel = labels.last;
    if (visible.isEmpty || visible.last != lastLabel) {
      visible.add(lastLabel);
    }

    return visible;
  }
}

class _ChartEmptyState extends StatelessWidget {
  const _ChartEmptyState({required this.title, required this.subtitle});

  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 18),
      child: Column(
        children: <Widget>[
          Icon(
            Icons.insights_outlined,
            size: 28,
            color: Theme.of(context).colorScheme.onSurfaceVariant,
          ),
          const SizedBox(height: 8),
          Text(
            title,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
              fontSize: 13.5,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            subtitle,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
              fontSize: 11,
            ),
          ),
        ],
      ),
    );
  }
}

class _TrendPainter extends CustomPainter {
  const _TrendPainter({
    required this.primaryValues,
    required this.primaryColor,
    required this.gridColor,
    this.secondaryValues,
    this.secondaryColor,
  });

  final List<double> primaryValues;
  final List<double>? secondaryValues;
  final Color primaryColor;
  final Color? secondaryColor;
  final Color gridColor;

  @override
  void paint(Canvas canvas, Size size) {
    const padding = 10.0;
    final chartHeight = size.height - padding;
    final chartWidth = size.width;

    final maxPrimary = primaryValues.isEmpty ? 1.0 : primaryValues.reduce(math.max);
    final maxSecondary = secondaryValues == null || secondaryValues!.isEmpty
        ? 0.0
        : secondaryValues!.reduce(math.max);
    final maxValue = math.max(maxPrimary, maxSecondary);

    final gridPaint = Paint()
      ..color = gridColor
      ..strokeWidth = 1;

    for (var i = 0; i < 4; i++) {
      final y = (chartHeight / 3) * i;
      canvas.drawLine(Offset(0, y), Offset(chartWidth, y), gridPaint);
    }

    void drawSeries(List<double> values, Color color) {
      if (values.isEmpty) {
        return;
      }

      final pointSpacing = values.length == 1
          ? 0.0
          : chartWidth / (values.length - 1);
      final path = Path();
      final fillPath = Path();

      for (var i = 0; i < values.length; i++) {
        final x = pointSpacing * i;
        final y =
            chartHeight - ((values[i] / (maxValue == 0 ? 1 : maxValue)) * chartHeight);

        if (i == 0) {
          path.moveTo(x, y);
          fillPath.moveTo(x, chartHeight);
          fillPath.lineTo(x, y);
        } else {
          path.lineTo(x, y);
          fillPath.lineTo(x, y);
        }
      }

      fillPath
        ..lineTo(chartWidth, chartHeight)
        ..close();

      final fillPaint = Paint()
        ..shader = LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: <Color>[color.withValues(alpha: .22), color.withValues(alpha: .02)],
        ).createShader(Rect.fromLTWH(0, 0, chartWidth, chartHeight));

      final linePaint = Paint()
        ..color = color
        ..style = PaintingStyle.stroke
        ..strokeWidth = 2.5;

      canvas.drawPath(fillPath, fillPaint);
      canvas.drawPath(path, linePaint);

      final dotPaint = Paint()..color = color;
      for (var i = 0; i < values.length; i++) {
        final x = pointSpacing * i;
        final y =
            chartHeight - ((values[i] / (maxValue == 0 ? 1 : maxValue)) * chartHeight);
        canvas.drawCircle(Offset(x, y), 3.2, dotPaint);
      }
    }

    if (secondaryValues != null && secondaryColor != null) {
      drawSeries(secondaryValues!, secondaryColor!);
    }
    drawSeries(primaryValues, primaryColor);
  }

  @override
  bool shouldRepaint(covariant _TrendPainter oldDelegate) {
    return oldDelegate.primaryValues != primaryValues ||
        oldDelegate.secondaryValues != secondaryValues ||
        oldDelegate.primaryColor != primaryColor ||
        oldDelegate.secondaryColor != secondaryColor;
  }
}
