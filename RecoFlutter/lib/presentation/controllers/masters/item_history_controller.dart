import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../data/models/masters/master_entities.dart';
import '../../../data/repositories/masters/items_repository.dart';

class ItemHistoryController extends GetxController {
  ItemHistoryController(
    this._repository, {
    required this.itemId,
    this.seedItem,
  });

  final ItemsRepository _repository;
  final int itemId;
  final ItemEntity? seedItem;

  final isLoading = false.obs;
  final item = Rxn<ItemEntity>();
  final transactions = <Map<String, dynamic>>[].obs;
  final totalIn = 0.0.obs;
  final totalOut = 0.0.obs;
  final totalSalesAmount = 0.0.obs;
  final totalPurchaseAmount = 0.0.obs;
  final closingQty = 0.0.obs;
  final fromDateController = TextEditingController();
  final toDateController = TextEditingController();

  @override
  void onInit() {
    super.onInit();
    item.value = seedItem;
    loadHistory();
  }

  Future<void> loadHistory({bool forceRefresh = false}) async {
    isLoading.value = true;
    try {
      final result = forceRefresh
          ? await _repository.refreshItemHistory(
              itemId,
              queryParameters: queryParameters,
            )
          : await _repository.getItemHistory(
              itemId,
              queryParameters: queryParameters,
            );
      final data = result['data'];
      if (data is Map<String, dynamic>) {
        final itemData = data['item'];
        if (itemData is Map<String, dynamic>) {
          item.value = ItemEntity.fromRecord(itemData);
        }
        totalIn.value = _asDouble(data['total_in']);
        totalOut.value = _asDouble(data['total_out']);
        totalSalesAmount.value = _asDouble(data['total_sales_amount']);
        totalPurchaseAmount.value = _asDouble(data['total_purchase_amount']);
        closingQty.value = _asDouble(data['closing_qty']);
        transactions.assignAll(
          (data['transactions'] is List)
              ? (data['transactions'] as List)
                  .whereType<Map>()
                  .map((item) => Map<String, dynamic>.from(item))
                  .toList()
              : <Map<String, dynamic>>[],
        );
      }
    } finally {
      isLoading.value = false;
    }
  }

  Map<String, dynamic> get queryParameters => <String, dynamic>{
        if (fromDateController.text.isNotEmpty)
          'date_from': fromDateController.text.trim(),
        if (toDateController.text.isNotEmpty)
          'date_to': toDateController.text.trim(),
      };

  String formatCurrency(num? value) => '₹ ${_asDouble(value).toStringAsFixed(2)}';
  String formatQuantity(num? value) => _asDouble(value).toStringAsFixed(3);
  String formatDate(String value) =>
      value.length >= 10 ? value.substring(0, 10) : value;

  @override
  void onClose() {
    fromDateController.dispose();
    toDateController.dispose();
    super.onClose();
  }

  double _asDouble(dynamic value) {
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value?.toString() ?? '') ?? 0;
  }
}
