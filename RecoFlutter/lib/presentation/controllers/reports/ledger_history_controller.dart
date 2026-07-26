import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/network/api_client.dart';

class LedgerHistoryController extends GetxController {
  LedgerHistoryController(this._apiClient, this.ledgerEntryId);

  final ApiClient _apiClient;
  final int ledgerEntryId;

  final isLoading = false.obs;
  final history = <Map<String, dynamic>>[].obs;

  @override
  void onInit() {
    super.onInit();
    loadHistory();
  }

  Future<void> loadHistory() async {
    isLoading.value = true;
    try {
      final response = await _apiClient.get<Map<String, dynamic>>(
        ApiEndpoints.ledgerHistory(ledgerEntryId),
      );
      final data = response.data?['data'];
      if (data is List) {
        history.assignAll(
          data
              .whereType<Map>()
              .map((item) => Map<String, dynamic>.from(item))
              .toList(),
        );
      } else {
        history.clear();
      }
    } finally {
      isLoading.value = false;
    }
  }
}
