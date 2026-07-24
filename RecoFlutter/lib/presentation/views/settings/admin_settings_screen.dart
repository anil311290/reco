import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../data/models/masters/master_entities.dart';
import '../../controllers/settings/admin_settings_controller.dart';
import '../../widgets/common/common_button.dart';
import '../../widgets/common/custom_text_field.dart';

class AdminSettingsScreen extends GetView<AdminSettingsController> {
  const AdminSettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Admin Settings',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
        bottom: TabBar(
          controller: controller.tabController,
          isScrollable: true,
          labelColor: theme.colorScheme.primary,
          unselectedLabelColor: theme.colorScheme.onSurface,
          labelStyle: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w800,
          ),
          unselectedLabelStyle: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w800,
          ),
          indicatorSize: TabBarIndicatorSize.label,
          indicatorWeight: 3,
          indicatorColor: theme.colorScheme.primary,
          dividerColor: theme.dividerColor.withValues(alpha: .5),
          tabs: const <Tab>[
            Tab(text: 'Company'),
            Tab(text: 'Theme'),
            Tab(text: 'Accounting'),
            Tab(text: 'Financial Year'),
          ],
        ),
      ),
      body: Obx(
        () => controller.isLoading.value
            ? const Center(child: CircularProgressIndicator())
            : TabBarView(
                controller: controller.tabController,
                children: <Widget>[
                  _CompanyTab(controller: controller),
                  _ThemeTab(controller: controller),
                  _AccountingTab(controller: controller),
                  _FinancialYearTab(controller: controller),
                ],
              ),
      ),
    );
  }
}

class _CompanyTab extends StatelessWidget {
  const _CompanyTab({required this.controller});
  final AdminSettingsController controller;
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    const currencies = <String>['INR', 'USD', 'EUR', 'GBP'];
    const timezones = <String>[
      'Asia/Kolkata',
      'America/New_York',
      'Europe/London',
    ];

    return ListView(
      padding: const EdgeInsets.all(16),
      children: <Widget>[
        Container(
          margin: const EdgeInsets.only(bottom: 16),
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            color: theme.cardColor,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(
              color: theme.dividerColor.withValues(alpha: .45),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Text(
                'Company Information',
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Update company profile, contact details and financial year setup.',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 16),
              CustomTextField(
                label: 'Company Name',
                controller: controller.companyNameController,
                requiredField: true,
              ),
              _TwoColumnRow(
                left: CustomTextField(
                  label: 'Email',
                  controller: controller.companyEmailController,
                  keyboardType: TextInputType.emailAddress,
                ),
                right: CustomTextField(
                  label: 'Phone',
                  controller: controller.companyPhoneController,
                  keyboardType: TextInputType.phone,
                ),
              ),
              _TwoColumnRow(
                left: CustomTextField(
                  label: 'GST Number',
                  controller: controller.companyGstController,
                ),
                right: CustomTextField(
                  label: 'PAN Number',
                  controller: controller.companyPanController,
                ),
              ),
              ValueListenableBuilder<TextEditingValue>(
                valueListenable: controller.companyCurrencyController,
                builder: (context, value, _) {
                  final selected = currencies.contains(value.text)
                      ? value.text
                      : null;
                  return _TwoColumnRow(
                    left: CustomDropdown<String>(
                      label: 'Currency',
                      value: selected,
                      items: currencies,
                      requiredField: true,
                      itemLabelBuilder: (item) => switch (item) {
                        'INR' => 'INR - Indian Rupee',
                        'USD' => 'USD - US Dollar',
                        'EUR' => 'EUR - Euro',
                        'GBP' => 'GBP - British Pound',
                        _ => item,
                      },
                      onChanged: (next) {
                        if (next != null) {
                          controller.companyCurrencyController.text = next;
                        }
                      },
                    ),
                    right: ValueListenableBuilder<TextEditingValue>(
                      valueListenable: controller.companyTimezoneController,
                      builder: (context, timezoneValue, _) {
                        final selectedTimezone =
                            timezones.contains(timezoneValue.text)
                            ? timezoneValue.text
                            : null;
                        return CustomDropdown<String>(
                          label: 'Timezone',
                          value: selectedTimezone,
                          items: timezones,
                          requiredField: true,
                          itemLabelBuilder: (item) => switch (item) {
                            'Asia/Kolkata' => 'Asia/Kolkata (IST)',
                            'America/New_York' =>
                              'America/New_York (EST)',
                            'Europe/London' => 'Europe/London (GMT)',
                            _ => item,
                          },
                          onChanged: (next) {
                            if (next != null) {
                              controller.companyTimezoneController.text = next;
                            }
                          },
                        );
                      },
                    ),
                  );
                },
              ),
              CustomTextField(
                label: 'Address',
                controller: controller.companyAddressController,
                requiredField: true,
                maxLines: 3,
              ),
              _ThreeColumnRow(
                first: CustomTextField(
                  label: 'City',
                  controller: controller.companyCityController,
                  requiredField: true,
                ),
                second: CustomTextField(
                  label: 'State',
                  controller: controller.companyStateController,
                  requiredField: true,
                ),
                third: CustomTextField(
                  label: 'Postal Code',
                  controller: controller.companyPostalCodeController,
                ),
              ),
              _TwoColumnRow(
                left: CustomTextField(
                  label: 'Country',
                  controller: controller.companyCountryController,
                  requiredField: true,
                ),
                right: const SizedBox.shrink(),
              ),
            ],
          ),
        ),
        Container(
          margin: const EdgeInsets.only(bottom: 16),
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            color: theme.cardColor,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(
              color: theme.dividerColor.withValues(alpha: .45),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Text(
                'Financial Year Defaults',
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Same as web settings. Use MM-DD format for year start and end.',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 16),
              _TwoColumnRow(
                left: CustomTextField(
                  label: 'FY Start',
                  controller: controller.financialYearStartController,
                  requiredField: true,
                  hintText: 'MM-DD',
                ),
                right: CustomTextField(
                  label: 'FY End',
                  controller: controller.financialYearEndController,
                  requiredField: true,
                  hintText: 'MM-DD',
                ),
              ),
              const SizedBox(height: 6),
              Obx(
                () => CommonButton(
                  text: 'Save Company Settings',
                  isLoading: controller.isSavingCompany.value,
                  onPressed: controller.saveCompany,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _TwoColumnRow extends StatelessWidget {
  const _TwoColumnRow({
    required this.left,
    required this.right,
  });

  final Widget left;
  final Widget right;

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    if (width < 760) {
      return Column(
        children: <Widget>[
          left,
          right,
        ],
      );
    }
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Expanded(child: left),
        const SizedBox(width: 12),
        Expanded(child: right),
      ],
    );
  }
}

class _ThreeColumnRow extends StatelessWidget {
  const _ThreeColumnRow({
    required this.first,
    required this.second,
    required this.third,
  });

  final Widget first;
  final Widget second;
  final Widget third;

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    if (width < 900) {
      return Column(
        children: <Widget>[
          first,
          second,
          third,
        ],
      );
    }
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Expanded(child: first),
        const SizedBox(width: 12),
        Expanded(child: second),
        const SizedBox(width: 12),
        Expanded(child: third),
      ],
    );
  }
}

class _ThemeTab extends StatelessWidget {
  const _ThemeTab({required this.controller});
  final AdminSettingsController controller;
  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: <Widget>[
        CustomTextField(label: 'Primary Color', controller: controller.primaryColorController),
        CustomTextField(label: 'Secondary Color', controller: controller.secondaryColorController),
        CustomTextField(label: 'Sidebar Color', controller: controller.sidebarColorController),
        CustomTextField(label: 'Header Color', controller: controller.headerColorController),
        Obx(
          () => SwitchListTile(
            value: controller.themeDarkMode.value,
            title: const Text('Dark Mode'),
            onChanged: (value) => controller.themeDarkMode.value = value,
          ),
        ),
        Obx(
          () => CommonButton(
            text: 'Save Theme Settings',
            isLoading: controller.isSavingTheme.value,
            onPressed: controller.saveTheme,
          ),
        ),
      ],
    );
  }
}

class _AccountingTab extends StatelessWidget {
  const _AccountingTab({required this.controller});
  final AdminSettingsController controller;
  @override
  Widget build(BuildContext context) {
    return Obx(
      () => ListView(
        padding: const EdgeInsets.all(16),
        children: <Widget>[
          _buildDropdown(
            'Sales Tax Ledger',
            controller.selectedSalesTaxLedger.value,
            controller.accounts,
            (value) => controller.selectedSalesTaxLedger.value = value,
          ),
          _buildDropdown(
            'Purchase Tax Ledger',
            controller.selectedPurchaseTaxLedger.value,
            controller.accounts,
            (value) => controller.selectedPurchaseTaxLedger.value = value,
          ),
          _buildDropdown(
            'TDS Ledger',
            controller.selectedTdsLedger.value,
            controller.accounts,
            (value) => controller.selectedTdsLedger.value = value,
          ),
          _buildDropdown(
            'TCS Ledger',
            controller.selectedTcsLedger.value,
            controller.accounts,
            (value) => controller.selectedTcsLedger.value = value,
          ),
          _buildDropdown(
            'Cess Ledger',
            controller.selectedCessLedger.value,
            controller.accounts,
            (value) => controller.selectedCessLedger.value = value,
          ),
          CommonButton(
            text: 'Save Accounting Settings',
            isLoading: controller.isSavingAccounting.value,
            onPressed: controller.saveAccounting,
          ),
        ],
      ),
    );
  }

  Widget _buildDropdown(
    String label,
    LookupOption? value,
    List<LookupOption> items,
    ValueChanged<LookupOption?> onChanged,
  ) {
    return CustomDropdown<LookupOption>(
      label: label,
      value: value,
      items: items,
      itemLabelBuilder: (item) => item.label,
      onChanged: onChanged,
    );
  }
}

class _FinancialYearTab extends StatelessWidget {
  const _FinancialYearTab({required this.controller});
  final AdminSettingsController controller;
  @override
  Widget build(BuildContext context) {
    return Obx(
      () => RefreshIndicator(
        onRefresh: controller.loadFinancialYears,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            if (controller.currentFinancialYear.isNotEmpty)
              Card(
                child: ListTile(
                  title: Text(
                    (controller.currentFinancialYear['name'] ??
                            controller.currentFinancialYear['financial_year'] ??
                            'Current Financial Year')
                        .toString(),
                  ),
                  subtitle: Text(
                    '${controller.currentFinancialYear['start_date'] ?? ''}  to  ${controller.currentFinancialYear['end_date'] ?? ''}',
                  ),
                ),
              ),
            const SizedBox(height: 12),
            for (final year in controller.financialYears)
              Card(
                child: ListTile(
                  title: Text(
                    (year['name'] ?? year['financial_year'] ?? 'Financial Year')
                        .toString(),
                  ),
                  subtitle: Text(
                    '${year['start_date'] ?? ''}  to  ${year['end_date'] ?? ''}',
                  ),
                  trailing: Text((year['status'] ?? '').toString()),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
