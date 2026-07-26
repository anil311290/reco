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
  final isPartiesLoading = false.obs;
  final isItemsLoading = false.obs;
  final isTaxRatesLoading = false.obs;
  final isCashBankAccountsLoading = false.obs;
  final isPaymentParticularsLoading = false.obs;
  final isAdjustmentParticularsLoading = false.obs;
  final isServiceAccountsLoading = false.obs;
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
      ];
      if (includeItems && serviceAccountType == 'income') {
        futures.add(_loadSalesLineCatalog());
      } else {
        futures.add(_loadServiceAccounts(serviceAccountType));
        if (includeItems) {
          futures.add(_loadItems());
        }
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
    isPartiesLoading.value = true;
    try {
      final records = await _partiesRepository.getParties(
        queryParameters: <String, dynamic>{'type': type},
      );
      parties.assignAll(records.where((item) => item.isActive).toList());
    } finally {
      isPartiesLoading.value = false;
    }
  }

  Future<void> _loadItems() async {
    isItemsLoading.value = true;
    try {
      final records = await _itemsRepository.getDropdownItems();
      items.assignAll(records.where((item) => item.isActive).toList());
    } finally {
      isItemsLoading.value = false;
    }
  }

  Future<void> _loadSalesLineCatalog() async {
    isItemsLoading.value = true;
    isServiceAccountsLoading.value = true;
    try {
      final catalog = await _itemsRepository.getSalesLineCatalog();
      final itemRecords = catalog['items'] ?? <Map<String, dynamic>>[];
      final serviceRecords = catalog['services'] ?? <Map<String, dynamic>>[];

      final mappedItems = itemRecords
          .map(ItemEntity.fromRecord)
          .where((item) => item.isActive)
          .toList();
      if (mappedItems.isNotEmpty) {
        items.assignAll(mappedItems);
      } else {
        await _loadItems();
      }

      final mappedServices = _mapLookupOptions(serviceRecords);
      if (mappedServices.isNotEmpty) {
        serviceAccounts.assignAll(mappedServices);
      } else {
        await _loadServiceAccounts('income');
      }
    } finally {
      isItemsLoading.value = false;
      isServiceAccountsLoading.value = false;
    }
  }

  Future<void> _loadTaxRates() async {
    isTaxRatesLoading.value = true;
    try {
      final records = await _taxRatesRepository.getTaxRates();
      taxRates.assignAll(records.where((item) => item.isActive).toList());
    } finally {
      isTaxRatesLoading.value = false;
    }
  }

  Future<void> _loadCashBankAccounts(String? mode) async {
    isCashBankAccountsLoading.value = true;
    try {
      final records = await _accountsRepository.getCashBankAccounts(mode: mode);
      cashBankAccounts.assignAll(_mapLookupOptions(records));
    } finally {
      isCashBankAccountsLoading.value = false;
    }
  }

  Future<void> _loadPaymentParticulars(String type) async {
    isPaymentParticularsLoading.value = true;
    try {
      final records = await _accountsRepository.getPaymentParticulars(type);
      paymentParticulars.assignAll(_mapLookupOptions(records));
    } finally {
      isPaymentParticularsLoading.value = false;
    }
  }

  Future<void> _loadAdjustmentParticulars() async {
    isAdjustmentParticularsLoading.value = true;
    try {
      final records = await _accountsRepository.getAdjustmentParticulars();
      adjustmentParticulars.assignAll(_mapLookupOptions(records));
    } finally {
      isAdjustmentParticularsLoading.value = false;
    }
  }

  Future<void> _loadServiceAccounts(String type) async {
    isServiceAccountsLoading.value = true;
    try {
      final records = await _accountsRepository.getAccountsByType(type);
      serviceAccounts.assignAll(records.where((item) => item.id > 0).toList());
    } finally {
      isServiceAccountsLoading.value = false;
    }
  }

  List<LookupOption> _mapLookupOptions(List<Map<String, dynamic>> records) {
    return records
        .map(
          (record) {
            final rawId = record['id']?.toString() ?? '';
            return LookupOption(
              id: int.tryParse(rawId) ?? 0,
              label: (record['text'] ??
                    record['account_name'] ??
                    record['name'] ??
                    record['label'] ??
                    '')
                  .toString(),
              code: (record['code'] ?? record['account_code'] ?? '').toString(),
              rawId: rawId,
              group: record['group']?.toString(),
              kind: record['kind']?.toString(),
              transactionMode: record['transaction_mode']?.toString(),
              availableBalance: _parseLookupDouble(record['available_balance']),
            );
          },
        )
        .where((item) => item.valueKey.trim().isNotEmpty && item.label.trim().isNotEmpty)
        .toList();
  }

  double? _parseLookupDouble(dynamic value) {
    if (value == null) {
      return null;
    }
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value.toString());
  }
}
