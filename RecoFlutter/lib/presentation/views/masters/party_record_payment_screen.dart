import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/network/api_client.dart';
import '../../../core/utils/amount_formatter.dart';
import '../../../core/utils/app_action_loader.dart';
import '../../../core/utils/app_date_formatter.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/models/masters/master_entities.dart';
import '../../../data/repositories/accounts/accounts_repository.dart';
import '../../widgets/common/custom_text_field.dart';

class PartyRecordPaymentScreen extends StatefulWidget {
  const PartyRecordPaymentScreen({required this.party, super.key});

  final PartyEntity party;

  @override
  State<PartyRecordPaymentScreen> createState() =>
      _PartyRecordPaymentScreenState();
}

class _PartyRecordPaymentScreenState extends State<PartyRecordPaymentScreen> {
  final paymentDateController = TextEditingController();
  final allocations = <int, TextEditingController>{};
  final invoices = <Map<String, dynamic>>[].obs;
  final isLoading = true.obs;
  int? cashBankAccountId;
  List<_CashBankOption> cashBankOptions = <_CashBankOption>[];

  bool get isDebtor => widget.party.type == 'debtor';

  @override
  void initState() {
    super.initState();
    paymentDateController.text = AppDateFormatter.formatDisplay(DateTime.now());
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    try {
      final accounts = await Get.find<AccountsRepository>().getCashBankAccounts();
      cashBankOptions = accounts
          .map((record) {
            final id = int.tryParse(record['id']?.toString() ?? '') ?? 0;
            final label = (record['text'] ??
                    record['account_name'] ??
                    record['name'] ??
                    '')
                .toString();
            if (id <= 0 || label.isEmpty) return null;
            return _CashBankOption(id: id, label: label);
          })
          .whereType<_CashBankOption>()
          .toList();
      if (cashBankOptions.isNotEmpty) {
        cashBankAccountId = cashBankOptions.first.id;
      }

      final response = await Get.find<ApiClient>().get<Map<String, dynamic>>(
        ApiEndpoints.partyOutstandingInvoices(widget.party.id!),
      );
      final data = response.data?['data'];
      final list = <Map<String, dynamic>>[];
      if (data is List) {
        for (final item in data.whereType<Map>()) {
          list.add(Map<String, dynamic>.from(item));
        }
      }
      for (final invoice in list) {
        final id = int.tryParse(invoice['id']?.toString() ?? '') ?? 0;
        if (id > 0) {
          allocations[id] = TextEditingController();
        }
      }
      invoices.assignAll(list);
    } catch (_) {
      AppSnackbar.error('Unable to load outstanding invoices.');
    } finally {
      isLoading.value = false;
    }
  }

  @override
  void dispose() {
    paymentDateController.dispose();
    for (final controller in allocations.values) {
      controller.dispose();
    }
    super.dispose();
  }

  double get totalAllocation {
    var sum = 0.0;
    for (final entry in allocations.entries) {
      sum += double.tryParse(entry.value.text.trim()) ?? 0;
    }
    return sum;
  }

  Future<void> _submit() async {
    if (cashBankAccountId == null) {
      AppSnackbar.error('Select a cash / bank account.');
      return;
    }
    if (paymentDateController.text.isEmpty) {
      AppSnackbar.error('Payment date is required.');
      return;
    }

    final payloadAllocations = <Map<String, dynamic>>[];
    for (final invoice in invoices) {
      final id = int.tryParse(invoice['id']?.toString() ?? '') ?? 0;
      if (id <= 0) continue;
      final amount = double.tryParse(allocations[id]?.text.trim() ?? '') ?? 0;
      if (amount <= 0) continue;
      final balance =
          double.tryParse(invoice['balance_due']?.toString() ?? '0') ?? 0;
      if (amount > balance + 0.0001) {
        AppSnackbar.error(
          'Allocation for ${invoice['invoice_number']} exceeds balance due.',
        );
        return;
      }
      payloadAllocations.add(<String, dynamic>{
        'invoice_id': id,
        'amount': amount,
      });
    }

    if (payloadAllocations.isEmpty) {
      AppSnackbar.error('Enter at least one allocation amount.');
      return;
    }

    try {
      final response = await AppActionLoader.run(
        () => Get.find<ApiClient>().post<Map<String, dynamic>>(
          ApiEndpoints.partyRecordPayment(widget.party.id!),
          data: <String, dynamic>{
            'cash_bank_account_id': cashBankAccountId,
            'payment_date':
                AppDateFormatter.toApiDate(paymentDateController.text),
            'allocations': payloadAllocations,
          },
        ),
        message: 'Recording payment...',
      );
      final voucherNumber =
          response.data?['data']?['voucher_number']?.toString() ?? '';
      AppSnackbar.success(
        voucherNumber.isEmpty
            ? 'Payment recorded successfully.'
            : 'Payment recorded · $voucherNumber',
      );
      Get.back<bool>(result: true);
    } catch (_) {
      AppSnackbar.error('Unable to record payment.');
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final accent = isDebtor ? const Color(0xFF16A34A) : const Color(0xFFDC2626);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          isDebtor ? 'Record Receipt' : 'Record Payment',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w700,
            fontSize: 15,
          ),
        ),
      ),
      body: Obx(() {
        if (isLoading.value) {
          return const Center(child: CircularProgressIndicator());
        }

        return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: accent.withValues(alpha: .08),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: accent.withValues(alpha: .18)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    widget.party.name,
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${widget.party.partyCode} · ${isDebtor ? 'Debtor' : 'Creditor'}',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),
            CustomDropdown<int>(
              label: 'Cash / Bank Account',
              value: cashBankAccountId,
              items: cashBankOptions.map((e) => e.id).toList(),
              itemLabelBuilder: (id) => cashBankOptions
                  .firstWhere(
                    (e) => e.id == id,
                    orElse: () => _CashBankOption(id: id, label: '$id'),
                  )
                  .label,
              onChanged: (value) => setState(() => cashBankAccountId = value),
            ),
            CustomTextField(
              label: 'Payment Date',
              controller: paymentDateController,
              readOnly: true,
              suffixIcon: Icons.edit_calendar_rounded,
              onTap: () async {
                final initial =
                    AppDateFormatter.parse(paymentDateController.text) ??
                        DateTime.now();
                final selected = await showDatePicker(
                  context: context,
                  initialDate: initial,
                  firstDate: DateTime(2000),
                  lastDate: DateTime(2100),
                );
                if (selected != null) {
                  setState(() {
                    paymentDateController.text =
                        AppDateFormatter.formatDisplay(selected);
                  });
                }
              },
            ),
            const SizedBox(height: 4),
            Text(
              'Allocate to invoices',
              style: theme.textTheme.titleSmall?.copyWith(
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 8),
            if (invoices.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 28),
                child: Center(
                  child: Text(
                    'No outstanding invoices for this party.',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ),
              )
            else
              ...invoices.map((invoice) {
                final id = int.tryParse(invoice['id']?.toString() ?? '') ?? 0;
                final balance =
                    double.tryParse(invoice['balance_due']?.toString() ?? '0') ??
                        0;
                return Container(
                  margin: const EdgeInsets.only(bottom: 10),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: theme.cardColor,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                      color: theme.dividerColor.withValues(alpha: .45),
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Row(
                        children: <Widget>[
                          Expanded(
                            child: Text(
                              (invoice['invoice_number'] ?? '-').toString(),
                              style: theme.textTheme.bodyMedium?.copyWith(
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ),
                          TextButton(
                            onPressed: () {
                              allocations[id]?.text =
                                  balance.toStringAsFixed(2);
                              setState(() {});
                            },
                            child: const Text('Full'),
                          ),
                        ],
                      ),
                      Text(
                        'Date ${AppDateFormatter.formatDisplay((invoice['invoice_date'] ?? '').toString())}'
                        ' · Due ${AppDateFormatter.formatDisplay((invoice['due_date'] ?? '').toString())}',
                        style: theme.textTheme.labelMedium?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Balance due: ${AmountFormatter.currency(balance)}',
                        style: theme.textTheme.labelLarge?.copyWith(
                          color: accent,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 8),
                      CustomTextField(
                        label: 'Allocate amount',
                        controller: allocations[id],
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        onChanged: (_) => setState(() {}),
                        bottomPadding: 0,
                      ),
                    ],
                  ),
                );
              }),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFF2563EB).withValues(alpha: .08),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                children: <Widget>[
                  Text(
                    'Total allocation',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const Spacer(),
                  Text(
                    AmountFormatter.currency(totalAllocation),
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w800,
                      color: const Color(0xFF2563EB),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              height: 48,
              width: double.infinity,
              child: FilledButton(
                onPressed: invoices.isEmpty ? null : _submit,
                style: FilledButton.styleFrom(
                  backgroundColor: accent,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: Text(
                  isDebtor ? 'Record Receipt' : 'Record Payment',
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
              ),
            ),
          ],
        );
      }),
    );
  }
}

class _CashBankOption {
  const _CashBankOption({required this.id, required this.label});
  final int id;
  final String label;
}

/// Opens the multi-invoice allocation screen for a party.
Future<bool> openPartyRecordPayment(PartyEntity party) async {
  if (party.id == null) {
    AppSnackbar.error('Party is not synced yet.');
    return false;
  }
  if (party.type != 'debtor' && party.type != 'creditor') {
    AppSnackbar.error('Only debtors and creditors can receive payments.');
    return false;
  }
  final result = await Get.to<bool>(() => PartyRecordPaymentScreen(party: party));
  return result == true;
}
