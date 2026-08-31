class ApiEndpoints {
  ApiEndpoints._();

  static const String login = '/login';
  static const String register = '/register';
  static const String logout = '/logout';
  static const String me = '/me';
  static const String changePassword = '/change-password';
  static const String forgotPassword = '/forgot-password';
  static const String resetPassword = '/reset-password';

  static const String pinLogin = '/pin/login';
  static const String pinSet = '/pin/set';
  static const String pinVerify = '/pin/verify';
  static const String securitySettings = '/security/settings';
  static const String securityAppLock = '/security/app-lock';
  static const String settings = '/settings';
  static const String settingsCompany = '/settings/company';
  static const String settingsTheme = '/settings/theme';
  static const String settingsAccounting = '/settings/accounting';
  static const String settingsFinancialYears = '/settings/financial-years';
  static const String settingsCurrentFinancialYear =
      '/settings/financial-year/current';

  static const String dashboard = '/dashboard';
  static const String dashboardMonthlyData = '/dashboard/monthly-data';
  static const String dashboardReceivablesTrend =
      '/dashboard/receivables-trend';
  static const String dashboardPayablesTrend = '/dashboard/payables-trend';

  static const String accounts = '/accounts';
  static const String accountsByType = '/accounts/by-type';
  static const String accountTree = '/accounts/tree';
  static const String accountCashBank = '/accounts/cash-bank';
  static const String accountPaymentParticulars =
      '/accounts/payment-particulars';
  static const String accountAdjustmentParticulars =
      '/accounts/adjustment-particulars';

  static const String parties = '/parties';
  static const String partyByType = '/parties/by-type';
  static String partyHistory(Object id) => '/parties/$id/history';
  static String partyOutstandingInvoices(Object id) =>
      '/parties/$id/outstanding-invoices';
  static String partyRecordPayment(Object id) => '/parties/$id/record-payment';
  static String partyApplyUnapplied(Object id) => '/parties/$id/apply-unapplied';
  static const String states = '/states';

  static String stateCities(int stateId) => '/states/$stateId/cities';

  static const String vouchers = '/vouchers';
  static String voucherDetail(Object id) => '/vouchers/$id';
  static const String payments = '/payments';
  static const String receipts = '/receipts';
  static const String adjustments = '/adjustments';

  static const String ledgers = '/ledgers';
  static String ledgerEntries(Object id) => '/ledgers/$id/entries';
  static String ledgerHistory(Object id) => '/ledgers/$id/history';
  static const String reportsDayBook = '/reports/day-book';
  static const String reportsLedger = '/reports/ledger';
  static const String reportsTrialBalance = '/reports/trial-balance';
  static const String reportsProfitLoss = '/reports/profit-loss';
  static const String reportsReceiptPayment = '/reports/receipt-payment';
  static const String reportsBalanceSheet = '/reports/balance-sheet';
  static const String reportsDebtorsOutstanding = '/reports/debtors-outstanding';
  static const String reportsCreditorsOutstanding =
      '/reports/creditors-outstanding';
  static const String reportsAgingSummary = '/reports/aging-summary';
  static const String reportsUnappliedReceipts = '/reports/unapplied-receipts';
  static const String reportsStockRegister = '/reports/stock-register';
  static const String reportsSettlementAudit = '/reports/settlement-audit';
  static const String reportsInvoiceSettlementDetails =
      '/reports/invoice-settlement-details';
  static const String reportsPaymentSettlementDetails =
      '/reports/payment-settlement-details';
  static const String exportDayBookPdf = '/export/day-book/pdf';
  static const String exportDayBookExcel = '/export/day-book/excel';
  static const String exportReceiptPaymentPdf = '/export/receipt-payment/pdf';
  static const String exportReceiptPaymentExcel = '/export/receipt-payment/excel';
  static const String exportLedgerPdf = '/export/ledger/pdf';
  static const String exportLedgerExcel = '/export/ledger/excel';
  static const String exportTrialBalancePdf = '/export/trial-balance/pdf';
  static const String exportTrialBalanceExcel = '/export/trial-balance/excel';
  static const String exportProfitLossPdf = '/export/profit-loss/pdf';
  static const String exportProfitLossExcel = '/export/profit-loss/excel';
  static const String exportBalanceSheetPdf = '/export/balance-sheet/pdf';
  static const String exportBalanceSheetExcel = '/export/balance-sheet/excel';
  static const String exportDebtorsOutstandingPdf =
      '/export/debtors-outstanding/pdf';
  static const String exportDebtorsOutstandingExcel =
      '/export/debtors-outstanding/excel';
  static const String exportCreditorsOutstandingPdf =
      '/export/creditors-outstanding/pdf';
  static const String exportCreditorsOutstandingExcel =
      '/export/creditors-outstanding/excel';
  static const String exportAgingSummaryPdf = '/export/aging-summary/pdf';
  static const String exportAgingSummaryExcel = '/export/aging-summary/excel';
  static String exportMasterExcel(String type) => '/export/masters/$type/excel';
  static String exportMasterPdf(String type) => '/export/masters/$type/pdf';

  static const String contentFaqs = '/content/faqs';
  static const String contentTestimonials = '/content/testimonials';
  static const String contentSiteSettings = '/content/site-settings';
  static String contentPage(String slug) => '/content/pages/$slug';
  static const String contentContact = '/content/contact';

  static const String financialYears = '/financial-years';
  static const String currentFinancialYear = '/financial-years/current';

  static const String taxRates = '/tax-rates';
  static const String taxRatesDropdown = '/tax-rates/dropdown';
  static const String itemCategories = '/item-categories';
  static const String itemCategoriesDropdown = '/item-categories/dropdown';
  static const String items = '/items';
  static const String itemsDropdown = '/items/dropdown';
  static String itemHistory(Object id) => '/items/$id/history';
  static const String salesInvoices = '/sales-invoices';
  static String salesInvoiceDetail(Object id) => '/sales-invoices/$id';
  static String salesInvoicePdf(Object id) => '/sales-invoices/$id/pdf';
  static String salesInvoicePost(Object id) => '/sales-invoices/$id/post';
  static String salesInvoiceCancel(Object id) => '/sales-invoices/$id/cancel';
  static String salesInvoicePayment(Object id) => '/sales-invoices/$id/payment';
  static String exportSalesInvoicePdf(Object id) => '/export/sales-invoice/$id/pdf';
  static String exportVoucherPdf(Object id) => '/export/voucher/$id/pdf';
  static const String serviceSalesInvoices = '/service-sales-invoices';
  static const String purchaseInvoices = '/purchase-invoices';
  static String purchaseInvoiceDetail(Object id) => '/purchase-invoices/$id';
  static String purchaseInvoicePost(Object id) => '/purchase-invoices/$id/post';
  static String purchaseInvoiceCancel(Object id) => '/purchase-invoices/$id/cancel';
  static String purchaseInvoicePayment(Object id) =>
      '/purchase-invoices/$id/payment';

  static const String subscriptionPlans = '/subscriptions/plans';
  static const String currentSubscription = '/subscriptions/current';
  static const String subscriptionInvoices = '/subscriptions/invoices';
  static const String subscriptionPayments = '/subscriptions/payments';
  static const String subscriptionCancel = '/subscriptions/cancel';
  static const String notifications = '/notifications';
  static const String notificationsReadAll = '/notifications/read-all';
  static const String auditLogs = '/audit-logs';
  static String auditLogDetail(Object id) => '/audit-logs/$id';
  static const String supportTickets = '/support-tickets';
  static String supportTicketDetail(Object id) => '/support-tickets/$id';
  static String supportTicketReply(Object id) => '/support-tickets/$id/reply';
  static String supportTicketStatus(Object id) => '/support-tickets/$id/status';
  static const String themes = '/themes';
  static const String themesCurrent = '/themes/current';
  static const String themesToggleDarkMode = '/themes/toggle-dark-mode';
  static const String syncUpload = '/sync/upload';
  static const String syncRun = '/sync/run';
  static const String syncDownload = '/sync/download';
  static const String syncBootstrap = '/sync/bootstrap';
  static const String syncStatus = '/sync/status';
  static const String deviceRegister = '/devices/register';
}
