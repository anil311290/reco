import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_snackbar.dart';
import '../../../data/models/masters/master_entities.dart';
import '../../../data/repositories/accounts/accounts_repository.dart';
import '../../../data/repositories/settings/settings_repository.dart';

class AdminSettingsController extends GetxController
    with GetSingleTickerProviderStateMixin {
  AdminSettingsController(this._settingsRepository, this._accountsRepository);

  final SettingsRepository _settingsRepository;
  final AccountsRepository _accountsRepository;

  late final TabController tabController;

  final isLoading = false.obs;
  final isSavingCompany = false.obs;
  final isSavingTheme = false.obs;
  final isSavingAccounting = false.obs;

  final companyNameController = TextEditingController();
  final companyEmailController = TextEditingController();
  final companyPhoneController = TextEditingController();
  final companyGstController = TextEditingController();
  final companyPanController = TextEditingController();
  final companyAddressController = TextEditingController();
  final companyCityController = TextEditingController();
  final companyStateController = TextEditingController();
  final companyPostalCodeController = TextEditingController();
  final companyCountryController = TextEditingController();
  final companyCurrencyController = TextEditingController(text: 'INR');
  final companyTimezoneController = TextEditingController(text: 'Asia/Kolkata');
  final financialYearStartController = TextEditingController(text: '04-01');
  final financialYearEndController = TextEditingController(text: '03-31');

  final primaryColorController = TextEditingController();
  final secondaryColorController = TextEditingController();
  final sidebarColorController = TextEditingController();
  final headerColorController = TextEditingController();
  final themeDarkMode = false.obs;

  final accounts = <LookupOption>[].obs;
  final selectedSalesTaxLedger = Rxn<LookupOption>();
  final selectedPurchaseTaxLedger = Rxn<LookupOption>();
  final selectedTdsLedger = Rxn<LookupOption>();
  final selectedTcsLedger = Rxn<LookupOption>();
  final selectedCessLedger = Rxn<LookupOption>();

  @override
  void onInit() {
    super.onInit();
    final rawInitialTab = (Get.arguments is int) ? Get.arguments as int : 0;
    final initialTab = rawInitialTab.clamp(0, 4);
    tabController = TabController(length: 5, vsync: this, initialIndex: initialTab);
    loadData();
  }

  @override
  void onClose() {
    tabController.dispose();
    for (final controller in <TextEditingController>[
      companyNameController,
      companyEmailController,
      companyPhoneController,
      companyGstController,
      companyPanController,
      companyAddressController,
      companyCityController,
      companyStateController,
      companyPostalCodeController,
      companyCountryController,
      companyCurrencyController,
      companyTimezoneController,
      financialYearStartController,
      financialYearEndController,
      primaryColorController,
      secondaryColorController,
      sidebarColorController,
      headerColorController,
    ]) {
      controller.dispose();
    }
    super.onClose();
  }

  Future<void> loadData() async {
    isLoading.value = true;
    try {
      await Future.wait(<Future<void>>[
        _loadSettings(),
        _loadAccounts(),
      ]);
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> _loadSettings() async {
    final data = await _settingsRepository.fetchSettings();
    final company = (data['company'] as Map?)?.cast<String, dynamic>() ??
        <String, dynamic>{};
    final theme = (data['theme'] as Map?)?.cast<String, dynamic>() ??
        <String, dynamic>{};
    final accounting =
        (data['accounting'] as Map?)?.cast<String, dynamic>() ??
            <String, dynamic>{};

    companyNameController.text = (company['name'] ?? '').toString();
    companyEmailController.text = (company['email'] ?? '').toString();
    companyPhoneController.text = (company['phone'] ?? '').toString();
    companyGstController.text = (company['gst_number'] ?? '').toString();
    companyPanController.text = (company['pan_number'] ?? '').toString();
    companyAddressController.text = (company['address'] ?? '').toString();
    companyCityController.text = (company['city'] ?? '').toString();
    companyStateController.text = (company['state'] ?? '').toString();
    companyPostalCodeController.text = (company['postal_code'] ?? '').toString();
    companyCountryController.text =
        (company['country'] ?? 'India').toString();
    companyCurrencyController.text =
        (company['currency'] ?? data['currency'] ?? 'INR').toString();
    companyTimezoneController.text =
        (company['timezone'] ?? data['timezone'] ?? 'Asia/Kolkata').toString();
    financialYearStartController.text =
        (company['financial_year_start'] ?? '04-01').toString();
    financialYearEndController.text =
        (company['financial_year_end'] ?? '03-31').toString();

    primaryColorController.text =
        (theme['primary_color'] ?? '#4f46e5').toString();
    secondaryColorController.text =
        (theme['secondary_color'] ?? '#6b7280').toString();
    sidebarColorController.text =
        (theme['sidebar_color'] ?? '#1e1b4b').toString();
    headerColorController.text =
        (theme['header_color'] ?? '#ffffff').toString();
    themeDarkMode.value = theme['dark_mode'] == true;

    _pendingAccountingIds = <String, dynamic>{
      'sales_tax_ledger_id': accounting['sales_tax_ledger_id'],
      'purchase_tax_ledger_id': accounting['purchase_tax_ledger_id'],
      'tds_ledger_id': accounting['tds_ledger_id'],
      'tcs_ledger_id': accounting['tcs_ledger_id'],
      'cess_ledger_id': accounting['cess_ledger_id'],
    };
  }

  Map<String, dynamic> _pendingAccountingIds = <String, dynamic>{};

  Future<void> _loadAccounts() async {
    final records = await _accountsRepository.getAccounts();
    accounts.assignAll(
      records
          .map(
            (item) => LookupOption(
              id: int.tryParse(item['id'].toString()) ?? 0,
              label: (item['account_name'] ?? item['name'] ?? '').toString(),
              code: (item['account_code'] ?? '').toString(),
            ),
          )
          .where((item) => item.id > 0 && item.label.trim().isNotEmpty)
          .toList(),
    );
    _bindAccountingSelections();
  }

  void _bindAccountingSelections() {
    selectedSalesTaxLedger.value =
        _findAccount(_pendingAccountingIds['sales_tax_ledger_id']);
    selectedPurchaseTaxLedger.value =
        _findAccount(_pendingAccountingIds['purchase_tax_ledger_id']);
    selectedTdsLedger.value = _findAccount(_pendingAccountingIds['tds_ledger_id']);
    selectedTcsLedger.value = _findAccount(_pendingAccountingIds['tcs_ledger_id']);
    selectedCessLedger.value =
        _findAccount(_pendingAccountingIds['cess_ledger_id']);
  }

  LookupOption? _findAccount(dynamic id) {
    final parsed = int.tryParse(id?.toString() ?? '');
    if (parsed == null) {
      return null;
    }
    for (final account in accounts) {
      if (account.id == parsed) {
        return account;
      }
    }
    return null;
  }

  Future<void> saveCompany() async {
    isSavingCompany.value = true;
    try {
      await _settingsRepository.updateCompanySettings(<String, dynamic>{
        'company_name': companyNameController.text.trim(),
        'company_email': companyEmailController.text.trim(),
        'company_phone': companyPhoneController.text.trim(),
        'company_gst_number': companyGstController.text.trim(),
        'company_pan_number': companyPanController.text.trim(),
        'company_address': companyAddressController.text.trim(),
        'company_city': companyCityController.text.trim(),
        'company_state': companyStateController.text.trim(),
        'company_postal_code': companyPostalCodeController.text.trim(),
        'company_country': companyCountryController.text.trim(),
        'company_currency': companyCurrencyController.text.trim(),
        'company_timezone': companyTimezoneController.text.trim(),
        'financial_year_start': financialYearStartController.text.trim(),
        'financial_year_end': financialYearEndController.text.trim(),
      });
      AppSnackbar.success('Company settings updated successfully.');
    } finally {
      isSavingCompany.value = false;
    }
  }

  Future<void> saveTheme() async {
    isSavingTheme.value = true;
    try {
      await _settingsRepository.updateThemeSettings(<String, dynamic>{
        'primary_color': primaryColorController.text.trim(),
        'secondary_color': secondaryColorController.text.trim(),
        'sidebar_color': sidebarColorController.text.trim(),
        'header_color': headerColorController.text.trim(),
        'dark_mode': themeDarkMode.value,
      });
      AppSnackbar.success('Theme settings updated successfully.');
    } finally {
      isSavingTheme.value = false;
    }
  }

  Future<void> saveAccounting() async {
    isSavingAccounting.value = true;
    try {
      await _settingsRepository.updateAccountingSettings(<String, dynamic>{
        'sales_tax_ledger_id': selectedSalesTaxLedger.value?.id,
        'purchase_tax_ledger_id': selectedPurchaseTaxLedger.value?.id,
        'tds_ledger_id': selectedTdsLedger.value?.id,
        'tcs_ledger_id': selectedTcsLedger.value?.id,
        'cess_ledger_id': selectedCessLedger.value?.id,
      });
      AppSnackbar.success('Accounting settings updated successfully.');
    } finally {
      isSavingAccounting.value = false;
    }
  }
}
