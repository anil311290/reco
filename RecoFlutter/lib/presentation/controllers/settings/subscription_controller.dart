import 'package:get/get.dart';

import '../../../core/utils/app_snackbar.dart';
import '../../../data/repositories/settings/subscriptions_repository.dart';

class SubscriptionController extends GetxController {
  SubscriptionController(this._repository);

  final SubscriptionsRepository _repository;

  final isLoading = false.obs;
  final isCancelling = false.obs;
  final isLoadingMoreInvoices = false.obs;
  final isLoadingMorePayments = false.obs;
  final currentSubscription = <String, dynamic>{}.obs;
  final plans = <Map<String, dynamic>>[].obs;
  final invoices = <Map<String, dynamic>>[].obs;
  final payments = <Map<String, dynamic>>[].obs;
  final invoicesCurrentPage = 1.obs;
  final invoicesLastPage = 1.obs;
  final invoicesTotal = 0.obs;
  final paymentsCurrentPage = 1.obs;
  final paymentsLastPage = 1.obs;
  final paymentsTotal = 0.obs;

  static const int pageSize = 10;

  bool get hasMoreInvoices => invoicesCurrentPage.value < invoicesLastPage.value;
  bool get hasMorePayments => paymentsCurrentPage.value < paymentsLastPage.value;

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
        _repository.fetchInvoicesPage(page: 1, perPage: pageSize),
        _repository.fetchPaymentsPage(page: 1, perPage: pageSize),
      ]);
      currentSubscription.assignAll(
        (results[0] as Map<String, dynamic>?) ?? <String, dynamic>{},
      );
      plans.assignAll(results[1] as List<Map<String, dynamic>>);
      _applyInvoicesPage(results[2], reset: true);
      _applyPaymentsPage(results[3], reset: true);
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> loadMoreInvoices() async {
    if (isLoadingMoreInvoices.value || !hasMoreInvoices) {
      return;
    }
    isLoadingMoreInvoices.value = true;
    try {
      final result = await _repository.fetchInvoicesPage(
        page: invoicesCurrentPage.value + 1,
        perPage: pageSize,
      );
      _applyInvoicesPage(result, reset: false);
    } finally {
      isLoadingMoreInvoices.value = false;
    }
  }

  Future<void> loadMorePayments() async {
    if (isLoadingMorePayments.value || !hasMorePayments) {
      return;
    }
    isLoadingMorePayments.value = true;
    try {
      final result = await _repository.fetchPaymentsPage(
        page: paymentsCurrentPage.value + 1,
        perPage: pageSize,
      );
      _applyPaymentsPage(result, reset: false);
    } finally {
      isLoadingMorePayments.value = false;
    }
  }

  void _applyInvoicesPage(dynamic result, {required bool reset}) {
    invoicesCurrentPage.value = result.currentPage;
    invoicesLastPage.value = result.lastPage;
    invoicesTotal.value = result.total;
    final incoming = result.items
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    if (reset) {
      invoices.assignAll(
        _mergeMaps(
          incoming,
          base: const <Map<String, dynamic>>[],
          keyBuilder: _invoiceKey,
        ),
      );
    } else {
      invoices.assignAll(
        _mergeMaps(
          incoming,
          base: invoices,
          keyBuilder: _invoiceKey,
        ),
      );
    }
  }

  void _applyPaymentsPage(dynamic result, {required bool reset}) {
    paymentsCurrentPage.value = result.currentPage;
    paymentsLastPage.value = result.lastPage;
    paymentsTotal.value = result.total;
    final incoming = result.items
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    if (reset) {
      payments.assignAll(
        _mergeMaps(
          incoming,
          base: const <Map<String, dynamic>>[],
          keyBuilder: _paymentKey,
        ),
      );
    } else {
      payments.assignAll(
        _mergeMaps(
          incoming,
          base: payments,
          keyBuilder: _paymentKey,
        ),
      );
    }
  }

  List<Map<String, dynamic>> _mergeMaps(
    List<Map<String, dynamic>> incoming, {
    required List<Map<String, dynamic>> base,
    required String Function(Map<String, dynamic>) keyBuilder,
  }) {
    final merged = <String, Map<String, dynamic>>{};
    for (final item in base) {
      merged[keyBuilder(item)] = item;
    }
    for (final item in incoming) {
      merged[keyBuilder(item)] = item;
    }
    return merged.values.toList();
  }

  String _invoiceKey(Map<String, dynamic> item) {
    final id = item['id']?.toString();
    if (id != null && id.isNotEmpty) {
      return 'id:$id';
    }
    final invoiceNumber = item['invoice_number']?.toString() ?? '';
    return 'invoice:$invoiceNumber';
  }

  String _paymentKey(Map<String, dynamic> item) {
    final id = item['id']?.toString();
    if (id != null && id.isNotEmpty) {
      return 'id:$id';
    }
    final paymentId =
        (item['razorpay_payment_id'] ?? item['payment_id'] ?? '').toString();
    return 'payment:$paymentId';
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
