import 'package:get/get.dart';

import '../../../core/utils/app_snackbar.dart';
import '../../../data/repositories/settings/subscriptions_repository.dart';

class SubscriptionController extends GetxController {
  SubscriptionController(this._repository);

  final SubscriptionsRepository _repository;

  final isLoading = false.obs;
  final isCancelling = false.obs;
  final currentSubscription = <String, dynamic>{}.obs;
  final plans = <Map<String, dynamic>>[].obs;
  final invoices = <Map<String, dynamic>>[].obs;
  final payments = <Map<String, dynamic>>[].obs;

  @override
  void onInit() {
    super.onInit();
    loadData();
  }

  Future<void> loadData() async {
    isLoading.value = true;
    try {
      final results = await Future.wait<dynamic>(<Future<dynamic>>[
        _repository.fetchCurrent(),
        _repository.fetchPlans(),
        _repository.fetchInvoices(),
        _repository.fetchPayments(),
      ]);
      currentSubscription.assignAll(
        (results[0] as Map<String, dynamic>?) ?? <String, dynamic>{},
      );
      plans.assignAll(results[1] as List<Map<String, dynamic>>);
      invoices.assignAll(results[2] as List<Map<String, dynamic>>);
      payments.assignAll(results[3] as List<Map<String, dynamic>>);
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> cancelSubscription() async {
    isCancelling.value = true;
    try {
      await _repository.cancelCurrent();
      AppSnackbar.success('Subscription cancelled successfully.');
      await loadData();
    } finally {
      isCancelling.value = false;
    }
  }
}

