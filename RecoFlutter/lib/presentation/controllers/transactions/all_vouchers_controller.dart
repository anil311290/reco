import '../../../core/config/api_endpoints.dart';
import '../../../data/models/transactions/transaction_entities.dart';
import 'base_transactions_tab_controller.dart';

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
}
