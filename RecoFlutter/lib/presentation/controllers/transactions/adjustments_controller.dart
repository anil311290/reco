import '../../../data/models/transactions/transaction_entities.dart';
import 'base_transactions_tab_controller.dart';

class AdjustmentsController extends BaseTransactionsTabController {
  AdjustmentsController(
    super.repository,
    super.syncService,
    super.networkMonitorService,
  );

  @override
  String get module => 'adjustments';

  @override
  String get endpoint => '/adjustments';

  @override
  TransactionRecordKind get kind => TransactionRecordKind.voucher;

  @override
  String get searchHint => 'Search by voucher no or narration...';

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
