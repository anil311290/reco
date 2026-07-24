import 'dart:async';

import 'package:get/get.dart';

import '../../../core/services/local_storage_service.dart';
import '../../../core/services/network_monitor_service.dart';
import '../../../data/repositories/dashboard/dashboard_repository.dart';
import '../../bindings/login_binding.dart';
import '../../views/auth/login_screen.dart';

class DashboardController extends GetxController {
  DashboardController(
    this._dashboardRepository,
    this._localStorageService,
    this._networkMonitorService,
  );

  final DashboardRepository _dashboardRepository;
  final LocalStorageService _localStorageService;
  final NetworkMonitorService _networkMonitorService;

  static const List<String> rangeOptions = <String>[
    'this_month',
    'last_month',
    'this_quarter',
    'this_year',
  ];

  static const List<String> groupOptions = <String>[
    'monthly',
    'quarterly',
    'yearly',
  ];

  final isLoading = false.obs;
  final isRefreshing = false.obs;
  final selectedRange = 'this_year'.obs;
  final selectedGroup = 'monthly'.obs;
  final dashboardData = <String, dynamic>{}.obs;
  final userName = ''.obs;

  bool get isOnline => _networkMonitorService.isOnline.value;

  Map<String, dynamic> get payload {
    final data = dashboardData['data'];
    if (data is Map<String, dynamic>) {
      return data;
    }
    return <String, dynamic>{};
  }

  Map<String, dynamic> get statistics {
    final value = payload['statistics'];
    if (value is Map<String, dynamic>) {
      return value;
    }
    return <String, dynamic>{};
  }

  Map<String, dynamic> get chartData {
    final value = payload['chart_data'];
    if (value is Map<String, dynamic>) {
      return value;
    }
    return <String, dynamic>{
      'labels': <String>[],
      'income': <double>[],
      'expense': <double>[],
    };
  }

  Map<String, dynamic> get receivablesTrend {
    final value = payload['receivables_trend'];
    if (value is Map<String, dynamic>) {
      return value;
    }
    return <String, dynamic>{'labels': <String>[], 'data': <double>[]};
  }

  Map<String, dynamic> get payablesTrend {
    final value = payload['payables_trend'];
    if (value is Map<String, dynamic>) {
      return value;
    }
    return <String, dynamic>{'labels': <String>[], 'data': <double>[]};
  }

  List<Map<String, dynamic>> get recentTransactions {
    final value = payload['recent_transactions'];
    if (value is List) {
      return value
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }
    return <Map<String, dynamic>>[];
  }

  List<String> get incomeExpenseLabels => _stringList(chartData['labels']);
  List<double> get incomeSeries => _numberList(chartData['income']);
  List<double> get expenseSeries => _numberList(chartData['expense']);
  List<String> get receivableLabels => _stringList(receivablesTrend['labels']);
  List<double> get receivableSeries => _numberList(receivablesTrend['data']);
  List<String> get payableLabels => _stringList(payablesTrend['labels']);
  List<double> get payableSeries => _numberList(payablesTrend['data']);

  @override
  void onInit() {
    super.onInit();
    userName.value =
        _localStorageService.user?['name']?.toString() ?? 'Reco User';
    unawaited(loadDashboard(showLoader: true));
  }

  Future<void> loadDashboard({bool showLoader = false}) async {
    if (showLoader) {
      isLoading.value = true;
    } else {
      isRefreshing.value = true;
    }

    try {
      final queryParameters = <String, dynamic>{
        'range': selectedRange.value,
        'group': selectedGroup.value,
        'limit': 10,
      };

      final cached = await _dashboardRepository.getDashboard(
        queryParameters: queryParameters,
      );
      if (cached.isNotEmpty) {
        dashboardData.value = cached;
      }

      if (await _networkMonitorService.hasInternetNow()) {
        final fresh = await _dashboardRepository.refreshDashboardIfConnected(
          queryParameters: queryParameters,
        );
        if (fresh.isNotEmpty) {
          dashboardData.value = fresh;
        }
      }
    } finally {
      isLoading.value = false;
      isRefreshing.value = false;
    }
  }

  Future<void> changeRange(String range) async {
    if (range == selectedRange.value) {
      return;
    }
    selectedRange.value = range;
    await loadDashboard(showLoader: true);
  }

  Future<void> changeGroup(String group) async {
    if (group == selectedGroup.value) {
      return;
    }
    selectedGroup.value = group;
    await loadDashboard(showLoader: true);
  }

  String formatCurrency(dynamic value) {
    final amount = _toDouble(value);
    final isNegative = amount < 0;
    final normalized = amount.abs().toStringAsFixed(2);
    final parts = normalized.split('.');
    final whole = parts.first;
    final decimal = parts.last;
    final buffer = StringBuffer();

    for (var index = 0; index < whole.length; index++) {
      final reverseIndex = whole.length - index;
      buffer.write(whole[index]);
      if (reverseIndex > 1 && reverseIndex % 3 == 1) {
        buffer.write(',');
      }
    }

    return '${isNegative ? '-' : ''}Rs ${buffer.toString()}.$decimal';
  }

  String formatDate(String value) {
    final parsed = DateTime.tryParse(value);
    if (parsed == null) {
      return value;
    }
    const months = <int, String>{
      1: 'Jan',
      2: 'Feb',
      3: 'Mar',
      4: 'Apr',
      5: 'May',
      6: 'Jun',
      7: 'Jul',
      8: 'Aug',
      9: 'Sep',
      10: 'Oct',
      11: 'Nov',
      12: 'Dec',
    };
    return '${parsed.day.toString().padLeft(2, '0')} ${months[parsed.month]} ${parsed.year}';
  }

  Future<void> logout() async {
    await _localStorageService.clearSession();
    Get.offAll(() => const LoginScreen(), binding: LoginBinding());
  }

  List<String> _stringList(dynamic value) {
    if (value is! List) {
      return <String>[];
    }
    return value.map((item) => item.toString()).toList();
  }

  List<double> _numberList(dynamic value) {
    if (value is! List) {
      return <double>[];
    }
    return value.map(_toDouble).toList();
  }

  double _toDouble(dynamic value) {
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value?.toString() ?? '') ?? 0;
  }
}
