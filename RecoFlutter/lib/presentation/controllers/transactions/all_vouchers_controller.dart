import '../../../core/config/api_endpoints.dart';
import '../../../data/models/transactions/transaction_entities.dart';
import 'base_transactions_tab_controller.dart';
import 'package:get/get.dart';

class AllVouchersController extends BaseTransactionsTabController {
  AllVouchersController(
    super.repository,
    super.syncService,
    super.networkMonitorService,
  );

  @override
  String get module => 'vouchers';

  @override
  String get endpoint => ApiEndpoints.vouchers;

  @override
  TransactionRecordKind get kind => TransactionRecordKind.voucher;

  @override
  String get searchHint => 'Search by voucher no, party, or narration...';

  final selectedType = 'All'.obs;

  @override
  bool get supportsPartyFilter => false;

  @override
  bool get supportsWorkflowActions => true;

  @override
  List<String> get statusOptions => const <String>[
        'All',
        'draft',
        'posted',
        'cancelled',
      ];

  List<String> get typeOptions => const <String>[
        'All',
        'payment',
        'receipt',
        'journal',
        'income',
        'expense',
      ];

  @override
  List<TransactionRecord> get filteredItems {
    final base = super.filteredItems;
    if (selectedType.value == 'All') {
      return base;
    }
    return base
        .where(
          (item) => item.type.toLowerCase() == selectedType.value.toLowerCase(),
        )
        .toList();
  }

  @override
  Map<String, dynamic> get extraQueryParameters => <String, dynamic>{
        if (selectedType.value != 'All')
          'voucher_type': selectedType.value.toLowerCase(),
      };

  Future<void> applyAllVoucherFilters({
    required String type,
    required String status,
    required String fromDate,
    required String toDate,
  }) async {
    selectedType.value = type;
    await applyFilters(
      status: status,
      partyId: 0,
      fromDate: fromDate,
      toDate: toDate,
    );
  }

  @override
  Future<void> clearFilters() async {
    selectedType.value = 'All';
    await super.clearFilters();
  }
}
