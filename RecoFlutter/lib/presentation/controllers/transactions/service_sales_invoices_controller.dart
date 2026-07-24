import '../../../core/config/api_endpoints.dart';
import '../../../data/models/transactions/transaction_entities.dart';
import 'base_transactions_tab_controller.dart';

class ServiceSalesInvoicesController extends BaseTransactionsTabController {
  ServiceSalesInvoicesController(
    super.repository,
    super.syncService,
    super.networkMonitorService,
  );

  @override
  String get module => 'service_sales_invoices';

  @override
  String get endpoint => ApiEndpoints.serviceSalesInvoices;

  @override
  TransactionRecordKind get kind => TransactionRecordKind.salesInvoice;

  @override
  String get searchHint => 'Search by invoice no or customer...';

  @override
  bool get supportsPartyFilter => true;

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
  Map<String, dynamic> get extraQueryParameters => const <String, dynamic>{
        'invoice_type': 'service',
      };
}
