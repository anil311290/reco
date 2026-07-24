import 'dart:async';

import 'package:get/get.dart';

import '../../../../data/models/masters/master_entities.dart';
import '../../../../data/repositories/accounts/accounts_repository.dart';
import '../../../../data/repositories/masters/items_repository.dart';
import '../../../../data/repositories/masters/parties_repository.dart';
import '../../../../data/repositories/masters/tax_rates_repository.dart';

class TransactionFormLookupController extends GetxController {
  TransactionFormLookupController(
    this._partiesRepository,
    this._accountsRepository,
    this._itemsRepository,
    this._taxRatesRepository,
  );

  final PartiesRepository _partiesRepository;
  final AccountsRepository _accountsRepository;
  final ItemsRepository _itemsRepository;
  final TaxRatesRepository _taxRatesRepository;

  final isLoading = false.obs;
  final parties = <PartyEntity>[].obs;
  final items = <ItemEntity>[].obs;
  final taxRates = <TaxRateEntity>[].obs;
  final cashBankAccounts = <LookupOption>[].obs;
  final paymentParticulars = <LookupOption>[].obs;
  final adjustmentParticulars = <LookupOption>[].obs;
  final serviceAccounts = <LookupOption>[].obs;

  Future<void> loadVoucherLookups({
    required String voucherType,
    String? paymentMode,
  }) async {
    isLoading.value = true;
    try {
      if (voucherType == 'payment' || voucherType == 'receipt') {
        await Future.wait(<Future<void>>[
          _loadCashBankAccounts(paymentMode),
          _loadPaymentParticulars(voucherType),
        ]);
      } else {
        await _loadAdjustmentParticulars();
      }
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> loadInvoiceLookups({
    required String partyType,
    required String serviceAccountType,
    required bool includeItems,
  }) async {
    isLoading.value = true;
    try {
      final futures = <Future<void>>[
        _loadParties(type: partyType),
        _loadTaxRates(),
        _loadServiceAccounts(serviceAccountType),
      ];
      if (includeItems) {
        futures.add(_loadItems());
      }
      await Future.wait(futures);
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> refreshCashBankAccounts(String mode) {
    return _loadCashBankAccounts(mode);
  }

  Future<void> _loadParties({required String type}) async {
    final records = await _partiesRepository.getParties(
      queryParameters: <String, dynamic>{'type': type},
    );
    parties.assignAll(records.where((item) => item.isActive).toList());
  }

  Future<void> _loadItems() async {
    final records = await _itemsRepository.getDropdownItems();
    items.assignAll(records.where((item) => item.isActive).toList());
  }

  Future<void> _loadTaxRates() async {
    final records = await _taxRatesRepository.getTaxRates();
    taxRates.assignAll(records.where((item) => item.isActive).toList());
  }

  Future<void> _loadCashBankAccounts(String? mode) async {
    final records = await _accountsRepository.getCashBankAccounts(mode: mode);
    cashBankAccounts.assignAll(_mapLookupOptions(records));
  }

  Future<void> _loadPaymentParticulars(String type) async {
    final records = await _accountsRepository.getPaymentParticulars(type);
    paymentParticulars.assignAll(_mapLookupOptions(records));
  }

  Future<void> _loadAdjustmentParticulars() async {
    final records = await _accountsRepository.getAdjustmentParticulars();
    adjustmentParticulars.assignAll(_mapLookupOptions(records));
  }

  Future<void> _loadServiceAccounts(String type) async {
    final records = await _accountsRepository.getAccountsByType(type);
    serviceAccounts.assignAll(records.where((item) => item.id > 0).toList());
  }

  List<LookupOption> _mapLookupOptions(List<Map<String, dynamic>> records) {
    return records
        .map(
          (record) => LookupOption(
            id: int.tryParse(record['id'].toString()) ?? 0,
            label: (record['text'] ??
                    record['account_name'] ??
                    record['name'] ??
                    record['label'] ??
                    '')
                .toString(),
            code: (record['code'] ?? record['account_code'] ?? '').toString(),
          ),
        )
        .where((item) => item.id > 0 && item.label.trim().isNotEmpty)
        .toList();
  }
}

