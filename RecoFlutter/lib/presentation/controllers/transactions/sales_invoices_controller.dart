import '../../../core/config/api_endpoints.dart';
import '../../../data/models/transactions/transaction_entities.dart';
import 'base_transactions_tab_controller.dart';

class SalesInvoicesController extends BaseTransactionsTabController {
  SalesInvoicesController(
    super.repository,
    super.syncService,
    super.networkMonitorService,
  );

  @override
  String get module => 'sales_invoices';

  @override
  String get endpoint => ApiEndpoints.salesInvoices;

  @override
  TransactionRecordKind get kind => TransactionRecordKind.salesInvoice;

  @override
  String get searchHint => 'Search by invoice no or customer...';

  @override
  bool get supportsPartyFilter => false;

  @override
  bool get supportsDateFilter => false;

  @override
  bool get supportsWorkflowActions => false;

  @override
  List<String> get statusOptions => const <String>[
        'All',
        'draft',
        'sent',
        'partial',
        'paid',
        'overdue',
        'cancelled',
      ];

  @override
  Map<String, dynamic> get extraQueryParameters => const <String, dynamic>{};
}
