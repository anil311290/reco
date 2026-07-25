import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../data/models/masters/master_entities.dart';
import '../../../data/repositories/masters/parties_repository.dart';

class PartyHistoryController extends GetxController {
  PartyHistoryController(
    this._repository, {
    required this.partyId,
    this.seedParty,
  });

  final PartiesRepository _repository;
  final int partyId;
  final PartyEntity? seedParty;

  final isLoading = false.obs;
  final party = Rxn<PartyEntity>();
  final transactions = <Map<String, dynamic>>[].obs;
  final totalDebit = 0.0.obs;
  final totalCredit = 0.0.obs;
  final closingBalance = 0.0.obs;
  final closingType = ''.obs;
  final fromDateController = TextEditingController();
  final toDateController = TextEditingController();

  @override
  void onInit() {
    super.onInit();
    party.value = seedParty;
    loadHistory();
  }

  Future<void> loadHistory({bool forceRefresh = false}) async {
    isLoading.value = true;
    try {
      final result = forceRefresh
          ? await _repository.refreshPartyHistory(
              partyId,
              queryParameters: queryParameters,
            )
          : await _repository.getPartyHistory(
              partyId,
              queryParameters: queryParameters,
            );
      final data = result['data'];
      if (data is Map<String, dynamic>) {
        final partyData = data['party'];
        if (partyData is Map<String, dynamic>) {
          party.value = PartyEntity.fromRecord(partyData);
        }
        totalDebit.value = _asDouble(data['total_debit']);
        totalCredit.value = _asDouble(data['total_credit']);
        closingBalance.value = _asDouble(data['closing_balance']);
        closingType.value = (data['closing_type'] ?? '').toString();
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

  String formatCurrency(num? value) => 'Rs ${_asDouble(value).toStringAsFixed(2)}';

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
