import 'dart:async';

import 'package:get/get.dart';

import '../../../data/models/masters/master_entities.dart';
import '../../../data/repositories/accounts/accounts_repository.dart';
import '../../../data/repositories/masters/item_categories_repository.dart';
import '../../../data/repositories/masters/locations_repository.dart';
import '../../../data/repositories/masters/tax_rates_repository.dart';

class MastersLookupController extends GetxController {
  MastersLookupController(
    this._locationsRepository,
    this._accountsRepository,
    this._itemCategoriesRepository,
    this._taxRatesRepository,
  );

  final LocationsRepository _locationsRepository;
  final AccountsRepository _accountsRepository;
  final ItemCategoriesRepository _itemCategoriesRepository;
  final TaxRatesRepository _taxRatesRepository;

  final states = <LookupOption>[].obs;
  final cities = <LookupOption>[].obs;
  final incomeAccounts = <LookupOption>[].obs;
  final expenseAccounts = <LookupOption>[].obs;
  final categories = <LookupOption>[].obs;
  final taxes = <LookupOption>[].obs;

  @override
  void onInit() {
    super.onInit();
    unawaited(preload());
  }

  Future<void> preload() async {
    await Future.wait(<Future<void>>[
      loadStates(),
      loadAccountLookups(),
      loadItemLookups(),
    ]);
  }

  Future<void> loadStates() async {
    try {
      states.assignAll(await _locationsRepository.getStates());
    } catch (_) {}
  }

  Future<void> loadCitiesForState(int? stateId) async {
    if (stateId == null) {
      cities.clear();
      return;
    }
    cities.assignAll(await _locationsRepository.getCities(stateId));
  }

  Future<void> loadAccountLookups() async {
    try {
      incomeAccounts.assignAll(
        await _accountsRepository.getAccountsByType('income'),
      );
      expenseAccounts.assignAll(
        await _accountsRepository.getAccountsByType('expense'),
      );
    } catch (_) {}
  }

  Future<void> loadItemLookups() async {
    try {
      categories.assignAll(await _itemCategoriesRepository.getDropdownOptions());
      taxes.assignAll(await _taxRatesRepository.getDropdownOptions());
    } catch (_) {}
  }
}
