import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../controllers/settings/subscription_controller.dart';

class SubscriptionScreen extends GetView<SubscriptionController> {
  const SubscriptionScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Subscription',
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
                  Card(
                    child: ListTile(
                      title: Text(
                        controller.currentSubscription['plan']?['name']
                                ?.toString() ??
                            'No active subscription',
                      ),
                      subtitle: Text(
                        controller.currentSubscription.isEmpty
                            ? 'Choose a plan from web/catalog'
                            : '${controller.currentSubscription['status']} • ${controller.currentSubscription['billing_cycle']}',
                      ),
                      trailing: controller.currentSubscription.isEmpty
                          ? null
                          : Obx(
                              () => TextButton(
                                onPressed: controller.isCancelling.value
                                    ? null
                                    : controller.cancelSubscription,
                                child: controller.isCancelling.value
                                    ? const SizedBox(
                                        width: 18,
                                        height: 18,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                        ),
                                      )
                                    : const Text('Cancel'),
                              ),
                            ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'Available Plans',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  ...controller.plans.map(
                    (plan) => Card(
                      child: ListTile(
                        title: Text((plan['name'] ?? 'Plan').toString()),
                        subtitle: Text((plan['description'] ?? '').toString()),
                        trailing: Text(
                          '₹${(plan['monthly_price'] ?? 0).toString()}',
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'Recent Invoices',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  ...controller.invoices.take(5).map(
                    (item) => Card(
                      child: ListTile(
                        title: Text((item['invoice_number'] ?? 'Invoice').toString()),
                        subtitle: Text((item['status'] ?? '').toString()),
                        trailing: Text('₹${(item['amount'] ?? 0).toString()}'),
                      ),
                    ),
                  ),
                ],
              ),
      ),
    );
  }
}

