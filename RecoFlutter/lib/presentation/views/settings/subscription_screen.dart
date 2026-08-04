import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_date_formatter.dart';
import '../../../core/utils/amount_formatter.dart';
import '../../controllers/settings/subscription_controller.dart';

class SubscriptionScreen extends GetView<SubscriptionController> {
  const SubscriptionScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Current Subscription',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      body: Obx(
        () => controller.isLoading.value
            ? const Center(child: CircularProgressIndicator())
            : ListView(
                padding: const EdgeInsets.all(16),
                children: <Widget>[
                  _SubscriptionShortcutRow(controller: controller),
                  const SizedBox(height: 14),
                  _SubscriptionHeroCard(controller: controller),
                  const SizedBox(height: 18),
                  if (controller.currentSubscription.isNotEmpty) ...<Widget>[
                    _PlanLimitsCard(controller: controller),
                    const SizedBox(height: 18),
                  ],
                  Text(
                    'Available Plans',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 10),
                  ...controller.plans.map(
                    (plan) => _PlanCard(plan: plan),
                  ),
                  const SizedBox(height: 18),
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: Text(
                          'Recent Invoices',
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                      if (controller.invoices.isNotEmpty)
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 6,
                          ),
                          decoration: BoxDecoration(
                            color: Theme.of(context)
                                .colorScheme
                                .primary
                                .withValues(alpha: .08),
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            '${controller.invoices.length} of ${controller.invoicesTotal.value}',
                            style: Theme.of(context).textTheme.labelMedium?.copyWith(
                              color: Theme.of(context).colorScheme.primary,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  if (controller.invoices.isEmpty)
                    _EmptySubscriptionState(
                      icon: FontAwesomeIcons.fileInvoiceDollar,
                      title: 'No invoices yet',
                      subtitle: 'Subscription invoices from the web plan will appear here.',
                    )
                  else
                    ...<Widget>[
                      ...controller.invoices.map((item) => _InvoiceCard(item: item)),
                      if (controller.hasMoreInvoices || controller.isLoadingMoreInvoices.value)
                        Padding(
                          padding: const EdgeInsets.only(top: 10),
                          child: Center(
                            child: controller.isLoadingMoreInvoices.value
                                ? const SizedBox(
                                    width: 22,
                                    height: 22,
                                    child: CircularProgressIndicator(strokeWidth: 2),
                                  )
                                : TextButton(
                                    onPressed: controller.loadMoreInvoices,
                                    child: const Text('Load more invoices'),
                                  ),
                          ),
                        ),
                    ],
                  const SizedBox(height: 18),
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: Text(
                          'Recent Payments',
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                      if (controller.payments.isNotEmpty)
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 6,
                          ),
                          decoration: BoxDecoration(
                            color: Theme.of(context)
                                .colorScheme
                                .primary
                                .withValues(alpha: .08),
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            '${controller.payments.length} of ${controller.paymentsTotal.value}',
                            style: Theme.of(context).textTheme.labelMedium?.copyWith(
                              color: Theme.of(context).colorScheme.primary,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  if (controller.payments.isEmpty)
                    _EmptySubscriptionState(
                      icon: FontAwesomeIcons.creditCard,
                      title: 'No payments yet',
                      subtitle: 'Subscription payments from the web plan will appear here.',
                    )
                  else
                    ...<Widget>[
                      ...controller.payments.map((item) => _PaymentCard(item: item)),
                      if (controller.hasMorePayments || controller.isLoadingMorePayments.value)
                        Padding(
                          padding: const EdgeInsets.only(top: 10),
                          child: Center(
                            child: controller.isLoadingMorePayments.value
                                ? const SizedBox(
                                    width: 22,
                                    height: 22,
                                    child: CircularProgressIndicator(strokeWidth: 2),
                                  )
                                : TextButton(
                                    onPressed: controller.loadMorePayments,
                                    child: const Text('Load more payments'),
                                  ),
                          ),
                        ),
                    ],
                ],
              ),
      ),
    );
  }
}

class _SubscriptionShortcutRow extends StatelessWidget {
  const _SubscriptionShortcutRow({required this.controller});

  final SubscriptionController controller;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final hasCurrent = controller.currentSubscription.isNotEmpty;
    return Wrap(
      spacing: 10,
      runSpacing: 10,
      children: <Widget>[
        _ShortcutChip(
          icon: FontAwesomeIcons.solidCircleCheck,
          label: hasCurrent ? 'Current Plan Active' : 'No Active Plan',
          color: hasCurrent ? const Color(0xFF16A34A) : const Color(0xFF64748B),
        ),
        _ShortcutChip(
          icon: FontAwesomeIcons.layerGroup,
          label: '${controller.plans.length} Plans',
          color: theme.colorScheme.primary,
        ),
        _ShortcutChip(
          icon: FontAwesomeIcons.fileInvoiceDollar,
          label: '${controller.invoices.length} Invoices',
          color: const Color(0xFF2563EB),
        ),
        _ShortcutChip(
          icon: FontAwesomeIcons.creditCard,
          label: '${controller.payments.length} Payments',
          color: const Color(0xFF9333EA),
        ),
      ],
    );
  }
}

class _SubscriptionHeroCard extends StatelessWidget {
  const _SubscriptionHeroCard({required this.controller});

  final SubscriptionController controller;

  @override
  Widget build(BuildContext context) {
    final hasSubscription = controller.currentSubscription.isNotEmpty;
    final plan = controller.currentSubscription['plan'] is Map
        ? Map<String, dynamic>.from(
            controller.currentSubscription['plan'] as Map,
          )
        : <String, dynamic>{};
    final accent = hasSubscription
        ? Theme.of(context).colorScheme.primary
        : const Color(0xFF475569);
    final periodStart = (controller.currentSubscription['current_period_start'] ?? '')
        .toString();
    final periodEnd = (controller.currentSubscription['current_period_end'] ?? '')
        .toString();
    final isTrial =
        (controller.currentSubscription['status'] ?? '').toString().toLowerCase() ==
            'trial';

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: LinearGradient(
          colors: <Color>[
            accent.withValues(alpha: .10),
            Theme.of(context).cardColor,
            Theme.of(context).cardColor,
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        border: Border.all(color: accent.withValues(alpha: .15)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  color: accent.withValues(alpha: .12),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(
                  hasSubscription
                      ? FontAwesomeIcons.crown
                      : FontAwesomeIcons.solidCreditCard,
                  size: 18,
                  color: accent,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      hasSubscription
                          ? (plan['name'] ?? 'Active Subscription').toString()
                          : 'No Active Subscription',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      hasSubscription
                          ? '${_titleCase((controller.currentSubscription['status'] ?? 'active').toString())} • ${_titleCase((controller.currentSubscription['billing_cycle'] ?? '').toString())}'
                          : 'Choose a plan to get started.',
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                        height: 1.35,
                      ),
                    ),
                  ],
                ),
              ),
              if (hasSubscription)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                  decoration: BoxDecoration(
                    color: accent.withValues(alpha: .10),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    (controller.currentSubscription['status'] ?? 'active')
                        .toString()
                        .toUpperCase(),
                    style: Theme.of(context).textTheme.labelSmall?.copyWith(
                      color: accent,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
            ],
          ),
          if (hasSubscription) ...<Widget>[
            const SizedBox(height: 16),
            Row(
              children: <Widget>[
                Expanded(
                  child: _MiniInfoCard(
                    label: 'Billing',
                    value: _titleCase(
                      (controller.currentSubscription['billing_cycle'] ?? '-')
                          .toString(),
                    ),
                    icon: FontAwesomeIcons.arrowsRotate,
                    color: const Color(0xFF2563EB),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _MiniInfoCard(
                    label: 'Amount',
                    value: _planAmountLabel(plan),
                    icon: FontAwesomeIcons.indianRupeeSign,
                    color: const Color(0xFF16A34A),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: <Widget>[
                Expanded(
                  child: _MiniInfoCard(
                    label: 'Current Period',
                    value: _rangeLabel(periodStart, periodEnd),
                    icon: FontAwesomeIcons.calendarDays,
                    color: const Color(0xFFF59E0B),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _MiniInfoCard(
                    label: isTrial ? 'Trial Ends' : 'Next Renewal',
                    value: isTrial
                        ? _shortDate(periodEnd)
                        : ((controller.currentSubscription['billing_cycle'] ?? '')
                                    .toString()
                                    .toLowerCase() ==
                                'lifetime')
                            ? 'One-time purchase'
                            : _shortDate(periodEnd),
                    icon: isTrial
                        ? FontAwesomeIcons.clock
                        : FontAwesomeIcons.rotateRight,
                    color: const Color(0xFF7C3AED),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            Obx(
              () => SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: controller.isCancelling.value
                      ? null
                      : controller.cancelSubscription,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFFDC2626),
                    side: BorderSide(
                      color: const Color(0xFFDC2626).withValues(alpha: .22),
                    ),
                    padding: const EdgeInsets.symmetric(vertical: 13),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                  icon: controller.isCancelling.value
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.cancel_outlined),
                  label: Text(
                    controller.isCancelling.value
                        ? 'Cancelling...'
                        : 'Cancel Subscription',
                  ),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  String _shortDate(String value) {
    return value.isEmpty
        ? '-'
        : AppDateFormatter.formatDisplay(value, fallback: value);
  }

  String _rangeLabel(String start, String end) {
    final normalizedStart = _shortDate(start);
    final normalizedEnd = _shortDate(end);
    if (normalizedStart == '-' && normalizedEnd == '-') {
      return '-';
    }
    return '$normalizedStart to $normalizedEnd';
  }

  String _titleCase(String value) {
    final normalized = value.trim();
    if (normalized.isEmpty || normalized == '-') {
      return '-';
    }
    return normalized
        .replaceAll('_', ' ')
        .split(' ')
        .where((part) => part.isNotEmpty)
        .map((part) => '${part[0].toUpperCase()}${part.substring(1)}')
        .join(' ');
  }

  String _planAmountLabel(Map<String, dynamic> plan) {
    final monthly = AmountFormatter.parse(plan['monthly_price']);
    final yearly = AmountFormatter.parse(plan['yearly_price']);
    final price = AmountFormatter.parse(plan['price']);
    final cycle = (controller.currentSubscription['billing_cycle'] ?? '')
        .toString()
        .toLowerCase();

    if (cycle == 'lifetime') {
      final amount = price > 0 ? price : monthly;
      return amount <= 0 ? '-' : '${AmountFormatter.currency(amount)} (Lifetime)';
    }
    if (cycle == 'yearly' && yearly > 0) {
      return '${AmountFormatter.currency(yearly)} /year';
    }
    final amount = monthly > 0 ? monthly : price;
    return amount <= 0 ? '-' : '${AmountFormatter.currency(amount)} /month';
  }
}

class _MiniInfoCard extends StatelessWidget {
  const _MiniInfoCard({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
  });

  final String label;
  final String value;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .07),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: color.withValues(alpha: .14)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              Icon(icon, size: 12, color: color),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  label,
                  style: Theme.of(context).textTheme.labelMedium?.copyWith(
                    color: color,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }
}

class _PlanCard extends StatelessWidget {
  const _PlanCard({required this.plan});

  final Map<String, dynamic> plan;

  @override
  Widget build(BuildContext context) {
    final name = (plan['name'] ?? 'Plan').toString();
    final description = (plan['description'] ?? '').toString();
    final price = AmountFormatter.currency(plan['monthly_price']);
    final isTrial = name.toLowerCase().contains('trial');
    final accent = isTrial ? const Color(0xFF7C3AED) : Theme.of(context).colorScheme.primary;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: accent.withValues(alpha: .14)),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: accent.withValues(alpha: .04),
            blurRadius: 14,
            offset: const Offset(0, 7),
          ),
        ],
      ),
      child: Row(
        children: <Widget>[
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: accent.withValues(alpha: .11),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(
              isTrial ? FontAwesomeIcons.rocket : FontAwesomeIcons.layerGroup,
              color: accent,
              size: 16,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  name,
                  style: Theme.of(context).textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  description,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                    height: 1.35,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
            decoration: BoxDecoration(
              color: accent.withValues(alpha: .10),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              price,
              style: Theme.of(context).textTheme.labelLarge?.copyWith(
                color: accent,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _InvoiceCard extends StatelessWidget {
  const _InvoiceCard({required this.item});

  final Map<String, dynamic> item;

  @override
  Widget build(BuildContext context) {
    final status = (item['status'] ?? '').toString();
    final amount = AmountFormatter.currency(item['amount']);
    final invoiceNumber = (item['invoice_number'] ?? 'Invoice').toString();
    final accent = status.toLowerCase() == 'paid'
        ? const Color(0xFF16A34A)
        : const Color(0xFF2563EB);

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: Theme.of(context).dividerColor.withValues(alpha: .25),
        ),
      ),
      child: Row(
        children: <Widget>[
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: accent.withValues(alpha: .10),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              FontAwesomeIcons.fileInvoice,
              size: 15,
              color: accent,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  invoiceNumber,
                  style: Theme.of(context).textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  status.isEmpty ? 'Pending status' : status,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Text(
            amount,
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w800,
              color: accent,
            ),
          ),
        ],
      ),
    );
  }
}

class _PaymentCard extends StatelessWidget {
  const _PaymentCard({required this.item});

  final Map<String, dynamic> item;

  @override
  Widget build(BuildContext context) {
    final status = (item['status'] ?? '').toString();
    final amount = AmountFormatter.currency(item['amount']);
    final paymentId = (item['razorpay_payment_id'] ?? item['payment_id'] ?? 'Payment')
        .toString();
    final accent = status.toLowerCase() == 'completed'
        ? const Color(0xFF16A34A)
        : const Color(0xFF9333EA);

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: Theme.of(context).dividerColor.withValues(alpha: .25),
        ),
      ),
      child: Row(
        children: <Widget>[
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: accent.withValues(alpha: .10),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              FontAwesomeIcons.creditCard,
              size: 15,
              color: accent,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  paymentId,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  status.isEmpty ? 'Pending status' : status,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Text(
            amount,
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w800,
              color: accent,
            ),
          ),
        ],
      ),
    );
  }
}

class _PlanLimitsCard extends StatelessWidget {
  const _PlanLimitsCard({required this.controller});

  final SubscriptionController controller;

  @override
  Widget build(BuildContext context) {
    final plan = controller.currentSubscription['plan'] is Map
        ? Map<String, dynamic>.from(controller.currentSubscription['plan'] as Map)
        : <String, dynamic>{};
    if (plan.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text(
          'Plan Limits',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 10),
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisSpacing: 10,
          mainAxisSpacing: 10,
          childAspectRatio: 1.6,
          children: <Widget>[
            _LimitCard(label: 'Users', value: _limitValue(plan['max_users'])),
            _LimitCard(
              label: 'Transactions',
              value: _limitValue(plan['max_transactions']),
            ),
            _LimitCard(label: 'Accounts', value: _limitValue(plan['max_accounts'])),
            _LimitCard(label: 'Parties', value: _limitValue(plan['max_parties'])),
          ],
        ),
      ],
    );
  }

  String _limitValue(dynamic value) {
    final parsed = int.tryParse(value?.toString() ?? '');
    if (parsed == null) {
      return '-';
    }
    return parsed == -1 ? 'Unlimited' : parsed.toString();
  }
}

class _LimitCard extends StatelessWidget {
  const _LimitCard({
    required this.label,
    required this.value,
  });

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: Theme.of(context).dividerColor.withValues(alpha: .25),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: <Widget>[
          Text(
            label,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            value,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }
}

class _ShortcutChip extends StatelessWidget {
  const _ShortcutChip({
    required this.icon,
    required this.label,
    required this.color,
  });

  final IconData icon;
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .08),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withValues(alpha: .16)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          Icon(icon, size: 14, color: color),
          const SizedBox(width: 8),
          Text(
            label,
            style: Theme.of(context).textTheme.labelLarge?.copyWith(
              color: color,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptySubscriptionState extends StatelessWidget {
  const _EmptySubscriptionState({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: Theme.of(context).dividerColor.withValues(alpha: .22),
        ),
      ),
      child: Column(
        children: <Widget>[
          Icon(
            icon,
            size: 20,
            color: Theme.of(context).colorScheme.primary,
          ),
          const SizedBox(height: 10),
          Text(
            title,
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            subtitle,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
              height: 1.35,
            ),
          ),
        ],
      ),
    );
  }
}
