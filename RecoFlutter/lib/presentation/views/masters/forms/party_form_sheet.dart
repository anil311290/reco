import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../core/utils/app_date_formatter.dart';
import '../../../../data/models/masters/master_entities.dart';
import '../../../../data/repositories/masters/financial_years_repository.dart';
import '../../../controllers/masters/masters_lookup_controller.dart';
import '../../../controllers/masters/parties_controller.dart';
import '../../../widgets/common/app_help_dialog.dart';
import '../../../widgets/common/common_button.dart';
import '../../../widgets/common/custom_text_field.dart';

class PartyFormSheet extends StatefulWidget {
  const PartyFormSheet({super.key, this.entity, this.initialType});

  final PartyEntity? entity;
  final String? initialType;

  @override
  State<PartyFormSheet> createState() => _PartyFormSheetState();
}

class _PartyFormSheetState extends State<PartyFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _codeController = TextEditingController();
  final _nameController = TextEditingController();
  final _mobileController = TextEditingController();
  final _emailController = TextEditingController();
  final _addressController = TextEditingController();
  final _postalController = TextEditingController();
  final _gstController = TextEditingController();
  final _panController = TextEditingController();
  final _amountController = TextEditingController(text: '0');
  final _remarksController = TextEditingController();

  late final PartiesController controller;
  late final MastersLookupController lookupController;
  late final FinancialYearsRepository _financialYearsRepository;
  bool isSaving = false;
  String partyType = 'debtor';
  String openingBalanceType = 'debit';
  int? selectedStateId;
  int? selectedCityId;
  bool _isActive = true;
  String _openingDateApi = AppDateFormatter.toApiDate(DateTime.now());
  String _openingDateDisplay = AppDateFormatter.formatDisplay(DateTime.now());

  bool get _isEditMode => widget.entity != null;
  bool get _isTypeLocked => widget.entity?.typeLocked ?? false;

  @override
  void initState() {
    super.initState();
    controller = Get.find<PartiesController>();
    lookupController = Get.find<MastersLookupController>();
    _financialYearsRepository = Get.find<FinancialYearsRepository>();
    final entity = widget.entity;
    _codeController.text = entity?.partyCode ?? '';
    _nameController.text = entity?.name ?? '';
    _mobileController.text = entity?.mobile ?? '';
    _emailController.text = entity?.email ?? '';
    _addressController.text = entity?.address ?? '';
    _postalController.text = entity?.postalCode ?? '';
    _gstController.text = entity?.gstin ?? '';
    _panController.text = entity?.panNumber ?? '';
    _amountController.text = '${entity?.openingBalance ?? 0}';
    _remarksController.text = entity?.remarks ?? '';
    partyType = entity?.type ?? widget.initialType ?? 'debtor';
    openingBalanceType = entity?.openingBalanceType ?? 'debit';
    selectedStateId = entity?.stateId;
    selectedCityId = entity?.cityId;
    _isActive = entity?.isActive ?? true;
    unawaited(_prefillLocationSelections(entity));
    _loadOpeningDateDefault();
  }

  Future<void> _prefillLocationSelections(PartyEntity? entity) async {
    if (entity == null) {
      return;
    }

    if (lookupController.states.isEmpty) {
      await lookupController.loadStates();
    }

    var stateId = entity.stateId;
    var cityId = entity.cityId;
    final stateName = entity.state.trim();
    final cityName = entity.city.trim();

    if (stateId == null && stateName.isNotEmpty) {
      stateId = lookupController.states
          .firstWhereOrNull(
            (item) => item.label.toLowerCase() == stateName.toLowerCase(),
          )
          ?.id;
    }

    if (stateId != null) {
      await lookupController.loadCitiesForState(stateId);
      if (cityId == null && cityName.isNotEmpty) {
        cityId = lookupController.cities
            .firstWhereOrNull(
              (item) => item.label.toLowerCase() == cityName.toLowerCase(),
            )
            ?.id;
      }
    }

    if (!mounted) {
      return;
    }

    setState(() {
      selectedStateId = stateId;
      selectedCityId = cityId;
    });
  }

  @override
  void dispose() {
    _codeController.dispose();
    _nameController.dispose();
    _mobileController.dispose();
    _emailController.dispose();
    _addressController.dispose();
    _postalController.dispose();
    _gstController.dispose();
    _panController.dispose();
    _amountController.dispose();
    _remarksController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.entity == null ? 'Create Party' : 'Edit Party',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w700,
          ),
        ),
      ),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.symmetric(horizontal: 14,vertical: 5),
            children: <Widget>[
              _buildHero(theme),
              if (_isEditMode) ...<Widget>[
                const SizedBox(height: 12),
                _buildCodeCard(theme),
              ],
              const SizedBox(height: 12),
              _buildMainCard(theme),
              if (_isEditMode) ...<Widget>[
                const SizedBox(height: 12),
                _buildSideSetupCard(theme),
              ],
              const SizedBox(height: 12),
              _buildActionCard(theme),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHero(ThemeData theme) {
    final scheme = theme.colorScheme;
    return Container(
      margin: const EdgeInsets.only(bottom: 4),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: LinearGradient(
          colors: <Color>[
            scheme.primary.withValues(alpha: .12),
            scheme.secondary.withValues(alpha: .08),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        border: Border.all(color: scheme.primary.withValues(alpha: .14)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
            decoration: BoxDecoration(
              color: scheme.surface.withValues(alpha: .92),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              'Party Master',
              style: theme.textTheme.labelMedium?.copyWith(
                color: scheme.primary,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          const SizedBox(height: 10),
          Text(
            _isEditMode
                ? 'Edit party details'
                : 'Create a new party',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            _isEditMode
                ? 'Update contact, tax, address, and status details for ${widget.entity?.name ?? 'this party'}.'
                : 'Create customer or supplier records with tax, address, and opening balance details in one flow.',
            style: theme.textTheme.bodySmall?.copyWith(
              color: scheme.onSurfaceVariant,
              height: 1.35,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCodeCard(ThemeData theme) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: theme.colorScheme.outlineVariant.withValues(alpha: .7),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            'Party Code',
            style: theme.textTheme.labelMedium?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            _codeController.text.trim().isEmpty
                ? 'Generated automatically'
                : _codeController.text.trim(),
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Party code is preserved from the existing record.',
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              height: 1.35,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMainCard(ThemeData theme) {
    return Container(
      // decoration: BoxDecoration(
      //   color: theme.cardColor,
      //   borderRadius: BorderRadius.circular(20),
      //   border: Border.all(
      //     color: theme.colorScheme.outlineVariant.withValues(alpha: .6),
      //   ),
      // ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Text(
                'Party Details',
                style: theme.textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w700,
                  fontSize: 16
                ),
              ),

              Text(
                _isEditMode
                    ? 'Update editable information below.'
                    : 'Fields marked with * are required.',
                style: theme.textTheme.labelSmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
            ],
          ),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 16.0),
            child: Divider(height: 1, color: theme.dividerColor.withValues(alpha: .45)),
          ),
          Column(
            children: <Widget>[
              _buildSectionHeader(
                theme,
                icon: Icons.badge_outlined,
                title: 'Identity & Contact',
                subtitle: 'Basic party and communication details',
              ),
              _twoColumn(
                left: CustomTextField(
                  controller: _nameController,
                  label: 'Party Name',
                  hintText: 'e.g., ABC Company',
                  requiredField: true,
                  validator: _required,
                  keyboardType: TextInputType.name,
                  textInputAction: TextInputAction.next,
                ),
                right: CustomDropdown<String>(
                  label: 'Party Type',
                  items: const <String>['debtor', 'creditor'],
                  value: partyType,
                  requiredField: true,
                  enabled: !_isTypeLocked,
                  itemLabelBuilder: (value) =>
                      value == 'debtor' ? 'Customer' : 'Supplier',
                  validator: _requiredDropdown,
                  onChanged: (value) =>
                      setState(() => partyType = value ?? partyType),
                ),
              ),
              _twoColumn(
                left: CustomTextField(
                  controller: _mobileController,
                  label: 'Mobile',
                  hintText: '+91 9876543210',
                  keyboardType: TextInputType.phone,
                  textInputAction: TextInputAction.next,
                ),
                right: CustomTextField(
                  controller: _emailController,
                  label: 'Email',
                  hintText: 'party@example.com',
                  keyboardType: TextInputType.emailAddress,
                  textInputAction: TextInputAction.next,
                ),
              ),
              if (_isTypeLocked) _buildInlineNote(
                theme,
                Icons.lock_outline_rounded,
                'Locked because transactions exist.',
              ),
              const SizedBox(height: 6),
              _buildSectionHeader(
                theme,
                icon: Icons.receipt_long_outlined,
                title: 'Tax Information',
                subtitle: 'Optional statutory registration details',
              ),
              _twoColumn(
                left: CustomTextField(
                  controller: _gstController,
                  label: 'GSTIN',
                  hintText: '22AAAAA0000A1Z5',
                  keyboardType: TextInputType.text,
                  textInputAction: TextInputAction.next,
                ),
                right: CustomTextField(
                  controller: _panController,
                  label: 'PAN Number',
                  hintText: 'AAAAA1111A',
                  keyboardType: TextInputType.text,
                  textInputAction: TextInputAction.next,
                ),
              ),
              const SizedBox(height: 6),
              _buildSectionHeader(
                theme,
                icon: Icons.location_on_outlined,
                title: 'Billing Address',
                subtitle: 'Used on invoices and party documents',
              ),
              CustomTextField(
                controller: _addressController,
                label: 'Address',
                hintText: 'Enter complete billing address',
                requiredField: true,
                maxLines: 3,
                validator: _required,
                keyboardType: TextInputType.streetAddress,
                textInputAction: TextInputAction.newline,
              ),
              _twoColumn(
                left: Obx(
                  () => CustomDropdown<int>(
                    label: 'State',
                    items: lookupController.states.map((item) => item.id).toList(),
                    value: selectedStateId,
                    requiredField: true,
                    isLoading: lookupController.states.isEmpty,
                    itemLabelBuilder: (id) => lookupController.states
                        .firstWhere((item) => item.id == id)
                        .label,
                    validator: _requiredDropdown,
                    onChanged: (value) async {
                      setState(() {
                        selectedStateId = value;
                        selectedCityId = null;
                      });
                      await lookupController.loadCitiesForState(value);
                    },
                  ),
                ),
                right: Obx(
                  () => CustomDropdown<int>(
                    label: 'City',
                    items: lookupController.cities.map((item) => item.id).toList(),
                    value: selectedCityId,
                    requiredField: true,
                    enabled: selectedStateId != null,
                    isLoading:
                        selectedStateId != null && lookupController.cities.isEmpty,
                    itemLabelBuilder: (id) => lookupController.cities
                        .firstWhere((item) => item.id == id)
                        .label,
                    validator: _requiredDropdown,
                    onChanged: (value) =>
                        setState(() => selectedCityId = value),
                  ),
                ),
              ),
              CustomTextField(
                controller: _postalController,
                label: 'Pincode',
                hintText: '400001',
                requiredField: true,
                validator: _required,
                keyboardType: TextInputType.number,
                textInputAction: TextInputAction.next,
              ),
              const SizedBox(height: 6),
              _buildSectionHeader(
                theme,
                icon: _isEditMode
                    ? Icons.shield_outlined
                    : Icons.account_balance_wallet_outlined,
                title: 'Opening Balance',
                subtitle: _isEditMode
                    ? 'Locked financial values'
                    : 'Set once when creating the party',
              ),
              _twoColumn(
                left: CustomTextField(
                  controller: _amountController,
                  label: 'Amount',
                  hintText: '0.00',
                  prefixText: '₹ ',
                  readOnly: _isEditMode,
                  keyboardType: const TextInputType.numberWithOptions(
                    decimal: true,
                  ),
                  textInputAction: TextInputAction.next,
                ),
                right: CustomDropdown<String>(
                  label: 'Balance Type',
                  items: const <String>['debit', 'credit'],
                  value: openingBalanceType,
                  enableSearch: false,
                  enabled: !_isEditMode,
                  itemLabelBuilder: (value) =>
                      value == 'debit' ? 'Debit (DR)' : 'Credit (CR)',
                  validator: _requiredDropdown,
                  onChanged: (value) => setState(
                    () => openingBalanceType = value ?? openingBalanceType,
                  ),
                ),
              ),
              _buildInlineNote(
                theme,
                Icons.lock_outline_rounded,
                _isEditMode
                    ? 'Opening balance cannot be edited after creation.'
                    : 'Opening balance cannot be changed after creation. Opening date is auto-set to current financial year start date: $_openingDateDisplay.',
              ),
              const SizedBox(height: 6),
              CustomTextField(
                controller: _remarksController,
                label: 'Notes',
                hintText: 'Add internal notes about this party',
                maxLines: 3,
                keyboardType: TextInputType.multiline,
                textInputAction: TextInputAction.newline,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSideSetupCard(ThemeData theme) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: theme.colorScheme.outlineVariant.withValues(alpha: .6),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            'Party Summary',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 10),
          _buildInfoStat(
            theme,
            label: 'Party Code',
            value: _codeController.text.trim().isEmpty
                ? 'Generated automatically'
                : _codeController.text.trim(),
          ),
          const SizedBox(height: 10),
          _buildInfoStat(
            theme,
            label: 'Current Type',
            value: partyType == 'debtor' ? 'Customer' : 'Supplier',
          ),
          const SizedBox(height: 10),
          _buildInfoStat(
            theme,
            label: 'Opening Balance',
            value:
                '₹${(double.tryParse(_amountController.text.trim()) ?? 0).toStringAsFixed(2)} ${openingBalanceType == 'debit' ? 'DR' : 'CR'}',
          ),
        ],
      ),
    );
  }

  Widget _buildActionCard(ThemeData theme) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: theme.colorScheme.outlineVariant.withValues(alpha: .6),
        ),
      ),
      child: Column(
        children: <Widget>[
          Row(
            children: <Widget>[
              Text(
                'Party Help',
                style: theme.textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
              const Spacer(),
              const AppHelpDialogButton(
                title: 'Party Setup',
                tooltip: 'Party setup help',
                sections: <AppHelpDialogSection>[
                  AppHelpDialogSection(
                    title: 'Customer',
                    message:
                        'Choose Customer when the party normally owes your business.',
                  ),
                  AppHelpDialogSection(
                    title: 'Supplier',
                    message:
                        'Choose Supplier when your business normally owes the party.',
                  ),
                  AppHelpDialogSection(
                    title: 'Party code',
                    message:
                        'Customer and supplier codes are assigned automatically when the record is saved.',
                  ),
                  AppHelpDialogSection(
                    title: 'Opening balance',
                    message:
                        'Opening balance can be set while creating the party. Opening date is auto-set to the current financial year start date and cannot be changed later.',
                  ),
                  AppHelpDialogSection(
                    title: 'Address details',
                    message:
                        'State, city, and pincode are used on invoices and report exports.',
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 10),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            title: Text(
              'Active Party',
              style: theme.textTheme.titleSmall?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
            subtitle: Text(
              'Inactive parties won\'t appear in dropdowns',
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
            value: _isActive,
            onChanged: (value) => setState(() => _isActive = value),
            dense: true,
          ),
          const SizedBox(height: 10),
          CommonButton(
            text: _isEditMode ? 'Update Party' : 'Create Party',
            isLoading: isSaving,
            onPressed: _submit,
          ),
        ],
      ),
    );
  }

  Widget _buildSectionHeader(
    ThemeData theme, {
    required IconData icon,
    required String title,
    required String subtitle,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: <Widget>[
          Padding(
            padding: const EdgeInsets.only(top: 1),
            child: Icon(
              icon,
              size: 18,
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  title,
                  style: theme.textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                      fontSize: 16
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _twoColumn({
    required Widget left,
    required Widget right,
  }) {
    return LayoutBuilder(
      builder: (context, constraints) {
        if (constraints.maxWidth < 720) {
          return Column(
            children: <Widget>[left, right],
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
      },
    );
  }

  Widget _buildInlineNote(ThemeData theme, IconData icon, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: <Widget>[
          Icon(icon, size: 14, color: theme.colorScheme.onSurfaceVariant),
          const SizedBox(width: 6),
          Text(
            'Opening balance rule',
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(width: 4),
          AppHelpDialogButton(
            title: 'Opening Balance Rule',
            tooltip: 'Opening balance help',
            sections: <AppHelpDialogSection>[
              AppHelpDialogSection(
                title: 'Rule',
                message: text,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildInfoStat(
    ThemeData theme, {
    required String label,
    required String value,
    String? note,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: theme.colorScheme.outlineVariant.withValues(alpha: .6),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            label,
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w800,
            ),
          ),
          if (note != null && note.trim().isNotEmpty) ...<Widget>[
            const SizedBox(height: 4),
            Text(
              note,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
                height: 1.3,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Future<void> _loadOpeningDateDefault() async {
    try {
      final currentFinancialYear =
          await _financialYearsRepository.getCurrentFinancialYear();
      final startDate = currentFinancialYear?.startDate.trim();
      if (startDate != null && startDate.isNotEmpty) {
        _openingDateApi = startDate;
        _openingDateDisplay = AppDateFormatter.formatDisplay(startDate);
      }
    } catch (_) {
      _openingDateApi = AppDateFormatter.toApiDate(DateTime.now());
      _openingDateDisplay = AppDateFormatter.formatDisplay(DateTime.now());
    }
    if (mounted) {
      setState(() {});
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => isSaving = true);
    try {
      await controller.save(
        PartyEntity(
          id: widget.entity?.id,
          localId: widget.entity?.localId,
          partyCode: _codeController.text.trim(),
          name: _nameController.text.trim(),
          type: partyType,
          mobile: _mobileController.text.trim(),
          email: _emailController.text.trim(),
          address: _addressController.text.trim(),
          stateId: selectedStateId,
          state:
              lookupController.states
                  .firstWhereOrNull((item) => item.id == selectedStateId)
                  ?.label ??
              '',
          cityId: selectedCityId,
          city:
              lookupController.cities
                  .firstWhereOrNull((item) => item.id == selectedCityId)
                  ?.label ??
              '',
          postalCode: _postalController.text.trim(),
          gstin: _gstController.text.trim(),
          panNumber: _panController.text.trim(),
          openingBalance: double.tryParse(_amountController.text.trim()) ?? 0,
          openingBalanceType: openingBalanceType,
          openingDate: widget.entity?.openingDate ?? _openingDateApi,
          remarks: _remarksController.text.trim(),
          isActive: _isActive,
        ),
      );
      if (mounted) Navigator.of(context).pop(true);
    } finally {
      if (mounted) setState(() => isSaving = false);
    }
  }

  String? _required(String? value) =>
      (value ?? '').trim().isEmpty ? 'Required field' : null;

  String? _requiredDropdown<T>(T? value) =>
      value == null ? 'This field is required' : null;
}
