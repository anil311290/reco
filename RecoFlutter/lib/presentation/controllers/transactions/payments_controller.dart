import '../../../data/models/transactions/transaction_entities.dart';
import 'base_transactions_tab_controller.dart';

class PaymentsController extends BaseTransactionsTabController {
  PaymentsController(super.repository, super.syncService, super.networkMonitorService);

  @override
  String get module => 'payments';

  @override
  String get endpoint => '/payments';

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
