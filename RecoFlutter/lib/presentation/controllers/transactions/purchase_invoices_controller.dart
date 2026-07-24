import '../../../core/config/api_endpoints.dart';
import '../../../data/models/transactions/transaction_entities.dart';
import 'base_transactions_tab_controller.dart';

class PurchaseInvoicesController extends BaseTransactionsTabController {
  PurchaseInvoicesController(
    super.repository,
    super.syncService,
    super.networkMonitorService,
  );

  @override
  String get module => 'purchase_invoices';

  @override
  String get endpoint => ApiEndpoints.purchaseInvoices;

  @override
  TransactionRecordKind get kind => TransactionRecordKind.purchaseInvoice;

  @override
  String get searchHint => 'Search by invoice no or supplier...';

  @override
  bool get supportsPartyFilter => true;

  @override
  bool get supportsWorkflowActions => false;

  @override
  List<String> get statusOptions => const <String>[
        'All',
        'draft',
        'verified',
        'partial',
        'paid',
        'overdue',
      ];
}
