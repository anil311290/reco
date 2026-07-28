import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_alert_dialog.dart';
import '../../../data/models/masters/master_entities.dart';
import '../../../data/repositories/settings/subscriptions_repository.dart';
import '../../controllers/settings/admin_settings_controller.dart';
import '../../controllers/settings/financial_years_controller.dart';
import '../../controllers/settings/subscription_controller.dart';
import '../../widgets/common/common_button.dart';
import '../../widgets/common/custom_text_field.dart';
import '../../../data/repositories/masters/financial_years_repository.dart';
import 'forms/financial_year_form_sheet.dart';
import 'subscription_screen.dart';

class AdminSettingsScreen extends GetView<AdminSettingsController> {
  const AdminSettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Settings',
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
            Tab(text: 'Subscription'),
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
                  _SubscriptionTab(),
                ],
              ),
      ),
      floatingActionButton: AnimatedBuilder(
        animation: controller.tabController,
        builder: (context, _) => controller.tabController.index == 3
            ? FloatingActionButton.extended(
                onPressed: () => Get.to(() => const FinancialYearFormSheet()),
                icon: const Icon(Icons.add_rounded),
                label: const Text('Add FY'),
              )
            : const SizedBox.shrink(),
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

class _ThemeTab extends StatelessWidget {
  const _ThemeTab({required this.controller});

  final AdminSettingsController controller;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Obx(
      () => ListView(
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
                  'Theme Customization',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Same flow as web settings. Update colors and save them permanently.',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
                const SizedBox(height: 16),
                _TwoColumnRow(
                  left: CustomTextField(
                    label: 'Primary Color',
                    controller: controller.primaryColorController,
                    hintText: '#1f6feb',
                  ),
                  right: CustomTextField(
                    label: 'Secondary Color',
                    controller: controller.secondaryColorController,
                    hintText: '#6b7280',
                  ),
                ),
                _TwoColumnRow(
                  left: CustomTextField(
                    label: 'Sidebar Color',
                    controller: controller.sidebarColorController,
                    hintText: '#ffffff',
                  ),
                  right: CustomTextField(
                    label: 'Header Color',
                    controller: controller.headerColorController,
                    hintText: '#ffffff',
                  ),
                ),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  value: controller.themeDarkMode.value,
                  onChanged: (value) => controller.themeDarkMode.value = value,
                  title: Text(
                    'Dark Mode',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  subtitle: const Text('Save theme preference for your company'),
                ),
                const SizedBox(height: 8),
                _ThemePreviewCard(controller: controller),
                const SizedBox(height: 16),
                CommonButton(
                  text: 'Save Theme Settings',
                  isLoading: controller.isSavingTheme.value,
                  onPressed: controller.saveTheme,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ThemePreviewCard extends StatelessWidget {
  const _ThemePreviewCard({required this.controller});

  final AdminSettingsController controller;

  Color _parseColor(String value, Color fallback) {
    final hex = value.trim().replaceFirst('#', '');
    if (hex.length != 6) {
      return fallback;
    }
    final parsed = int.tryParse(hex, radix: 16);
    if (parsed == null) {
      return fallback;
    }
    return Color(0xFF000000 | parsed);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final primary = _parseColor(
      controller.primaryColorController.text,
      theme.colorScheme.primary,
    );
    final secondary = _parseColor(
      controller.secondaryColorController.text,
      theme.colorScheme.secondary,
    );
    final sidebar = _parseColor(
      controller.sidebarColorController.text,
      theme.colorScheme.surfaceContainerLowest,
    );
    final header = _parseColor(
      controller.headerColorController.text,
      theme.colorScheme.surface,
    );

    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: theme.dividerColor.withValues(alpha: .45),
        ),
      ),
      child: Column(
        children: <Widget>[
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              color: header,
              borderRadius: const BorderRadius.vertical(
                top: Radius.circular(18),
              ),
            ),
            child: Text(
              'Live Preview',
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          SizedBox(
            height: 154,
            child: Row(
              children: <Widget>[
                Container(
                  width: 92,
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: sidebar,
                    borderRadius: const BorderRadius.only(
                      bottomLeft: Radius.circular(18),
                    ),
                  ),
                  child: Column(
                    children: <Widget>[
                      Container(
                        height: 10,
                        decoration: BoxDecoration(
                          color: primary,
                          borderRadius: BorderRadius.circular(999),
                        ),
                      ),
                      const SizedBox(height: 10),
                      Container(
                        height: 10,
                        decoration: BoxDecoration(
                          color: secondary.withValues(alpha: .65),
                          borderRadius: BorderRadius.circular(999),
                        ),
                      ),
                      const SizedBox(height: 10),
                      Container(
                        height: 10,
                        decoration: BoxDecoration(
                          color: secondary.withValues(alpha: .35),
                          borderRadius: BorderRadius.circular(999),
                        ),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Container(
                          height: 52,
                          decoration: BoxDecoration(
                            color: theme.colorScheme.surfaceContainerLowest,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(
                              color: theme.dividerColor.withValues(alpha: .35),
                            ),
                          ),
                        ),
                        const SizedBox(height: 10),
                        Container(
                          width: 110,
                          height: 34,
                          decoration: BoxDecoration(
                            color: primary,
                            borderRadius: BorderRadius.circular(10),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SubscriptionTab extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return ListView(
      padding: const EdgeInsets.all(16),
      children: <Widget>[
        Container(
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
                'Subscription',
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Review active plan, available plans, invoices and payments.',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 16),
              CommonButton(
                text: 'Open Subscription Module',
                onPressed: () {
                  Get.to(
                    () => const SubscriptionScreen(),
                    binding: BindingsBuilder(
                      () {
                        Get.put(
                          SubscriptionController(
                            Get.find<SubscriptionsRepository>(),
                          ),
                        );
                      },
                    ),
                  );
                },
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _FinancialYearTab extends StatefulWidget {
  const _FinancialYearTab({required this.controller});
  final AdminSettingsController controller;

  @override
  State<_FinancialYearTab> createState() => _FinancialYearTabState();
}

class _FinancialYearTabState extends State<_FinancialYearTab> {
  late final FinancialYearsController _fyController;

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<FinancialYearsController>()) {
      _fyController = Get.put(
        FinancialYearsController(
          Get.find<FinancialYearsRepository>(),
          Get.find(),
          Get.find(),
        ),
        permanent: false,
      );
    } else {
      _fyController = Get.find<FinancialYearsController>();
    }
  }

  Future<bool> _confirmAction(String title, String message) async {
    return AppAlertDialog.confirm(
      title: title,
      message: message,
      confirmText: 'Confirm',
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Obx(
      () => RefreshIndicator(
        onRefresh: _fyController.refreshData,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            // ── Current Financial Year Card ──
            if (_fyController.currentFinancialYear.value != null) ...[
              Container(
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: <Color>[
                      theme.colorScheme.primary.withValues(alpha: 0.12),
                      theme.colorScheme.primary.withValues(alpha: 0.04),
                    ],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                    color: theme.colorScheme.primary.withValues(alpha: 0.3),
                  ),
                ),
                child: Row(
                  children: <Widget>[
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.primary.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: Icon(
                        Icons.today_rounded,
                        color: theme.colorScheme.primary,
                        size: 28,
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: <Widget>[
                          Row(
                            children: <Widget>[
                              Text(
                                _fyController.currentFinancialYear.value!.name,
                                style: theme.textTheme.titleMedium?.copyWith(
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                              const SizedBox(width: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.green.withValues(alpha: 0.15),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  'CURRENT',
                                  style: TextStyle(
                                    fontSize: 10,
                                    fontWeight: FontWeight.w800,
                                    color: Colors.green.shade700,
                                    letterSpacing: 0.6,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Text(
                            '${_fyController.currentFinancialYear.value!.startDate}  →  ${_fyController.currentFinancialYear.value!.endDate}',
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
            ],

            // ── Section Header ──
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: <Widget>[
                Text(
                  'All Financial Years',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                Obx(
                  () => _fyController.isLoading.value
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const SizedBox.shrink(),
                ),
              ],
            ),
            const SizedBox(height: 8),

            // ── Financial Year Cards ──
            ..._fyController.financialYears.map((fy) {
              final isCurrent = fy.isCurrent;
              final isClosed = fy.isClosed;

              return Card(
                margin: const EdgeInsets.only(bottom: 10),
                elevation: isCurrent ? 2 : 0.5,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                  side: isCurrent
                      ? BorderSide(
                          color: theme.colorScheme.primary.withValues(alpha: 0.4),
                          width: 1.5,
                        )
                      : BorderSide(
                          color: theme.dividerColor.withValues(alpha: 0.4),
                        ),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Row(
                        children: <Widget>[
                          Expanded(
                            child: Row(
                              children: <Widget>[
                                Icon(
                                  isClosed
                                      ? Icons.lock_outline_rounded
                                      : isCurrent
                                          ? Icons.check_circle_rounded
                                          : Icons.calendar_month_outlined,
                                  size: 20,
                                  color: isClosed
                                      ? Colors.grey
                                      : isCurrent
                                          ? Colors.green
                                          : theme.colorScheme.primary,
                                ),
                                const SizedBox(width: 8),
                                Flexible(
                                  child: Text(
                                    fy.name,
                                    style: theme.textTheme.bodyLarge?.copyWith(
                                      fontWeight: FontWeight.w700,
                                    ),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          _FyStatusChip(
                            label: fy.statusLabel,
                            isCurrent: isCurrent,
                            isClosed: isClosed,
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: <Widget>[
                          _FyInfoChip(
                            icon: Icons.play_arrow_rounded,
                            label: fy.startDate,
                          ),
                          const SizedBox(width: 4),
                          const Icon(Icons.arrow_forward_rounded, size: 14),
                          const SizedBox(width: 4),
                          _FyInfoChip(
                            icon: Icons.stop_rounded,
                            label: fy.endDate,
                          ),
                        ],
                      ),
                      if (fy.closedAt != null && fy.closedAt!.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          'Closed on: ${fy.closedAt}',
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                        ),
                      ],
                      const SizedBox(height: 10),
                      // ── Action Buttons ──
                      Row(
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: <Widget>[
                          if (!isCurrent && !isClosed) ...[
                            _FyActionButton(
                              icon: Icons.check_rounded,
                              label: 'Set Current',
                              color: Colors.green,
                              onTap: () => _fyController.setAsCurrent(fy),
                            ),
                            const SizedBox(width: 6),
                          ],
                          if (!isClosed) ...[
                            if (isCurrent)
                              _FyActionButton(
                                icon: Icons.edit_outlined,
                                label: 'Edit',
                                color: theme.colorScheme.primary,
                                onTap: () => Get.to(
                                  () => FinancialYearFormSheet(entity: fy),
                                ),
                              )
                            else ...[
                              _FyActionButton(
                                icon: Icons.edit_outlined,
                                label: 'Edit',
                                color: theme.colorScheme.primary,
                                onTap: () => Get.to(
                                  () => FinancialYearFormSheet(entity: fy),
                                ),
                              ),
                              const SizedBox(width: 6),
                              _FyActionButton(
                                icon: Icons.lock_outline_rounded,
                                label: 'Close',
                                color: Colors.orange,
                                onTap: () async {
                                  final confirmed = await _confirmAction(
                                    'Close Financial Year',
                                    'Are you sure you want to close "${fy.name}"? '
                                    'This action cannot be undone.',
                                  );
                                  if (confirmed == true) {
                                    _fyController.closeFinancialYear(fy);
                                  }
                                },
                              ),
                            ],
                            if (!isCurrent) ...[
                              const SizedBox(width: 6),
                              _FyActionButton(
                                icon: Icons.delete_outline_rounded,
                                label: 'Delete',
                                color: Colors.red,
                                onTap: () async {
                                  final confirmed = await _confirmAction(
                                    'Delete Financial Year',
                                    'Are you sure you want to delete "${fy.name}"?',
                                  );
                                  if (confirmed == true) {
                                    _fyController.deleteFinancialYear(fy);
                                  }
                                },
                              ),
                            ],
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
              );
            }),

            if (_fyController.financialYears.isEmpty &&
                !_fyController.isLoading.value)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 40),
                child: Center(
                  child: Column(
                    children: <Widget>[
                      Icon(
                        Icons.calendar_month_outlined,
                        size: 48,
                        color: theme.colorScheme.onSurfaceVariant
                            .withValues(alpha: 0.4),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'No financial years found',
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                  ),
                ),
              ),

            const SizedBox(height: 80),
          ],
        ),
      ),
    );
  }
}

class _FyStatusChip extends StatelessWidget {
  const _FyStatusChip({
    required this.label,
    required this.isCurrent,
    required this.isClosed,
  });

  final String label;
  final bool isCurrent;
  final bool isClosed;

  @override
  Widget build(BuildContext context) {
    Color bg;
    Color fg;
    if (isClosed) {
      bg = Colors.grey.withValues(alpha: 0.15);
      fg = Colors.grey.shade700;
    } else if (isCurrent) {
      bg = Colors.green.withValues(alpha: 0.15);
      fg = Colors.green.shade700;
    } else {
      bg = Colors.blue.withValues(alpha: 0.1);
      fg = Colors.blue.shade700;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: fg,
          letterSpacing: 0.4,
        ),
      ),
    );
  }
}

class _FyInfoChip extends StatelessWidget {
  const _FyInfoChip({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest.withValues(alpha: 0.5),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          Icon(icon, size: 12, color: theme.colorScheme.onSurfaceVariant),
          const SizedBox(width: 3),
          Text(
            label,
            style: theme.textTheme.bodySmall?.copyWith(
              fontSize: 11,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

class _FyActionButton extends StatelessWidget {
  const _FyActionButton({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: color.withValues(alpha: 0.1),
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              Icon(icon, size: 14, color: color),
              const SizedBox(width: 4),
              Text(
                label,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: color,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
