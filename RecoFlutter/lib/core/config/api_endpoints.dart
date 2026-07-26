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
  static const String states = '/states';

  static String stateCities(int stateId) => '/states/$stateId/cities';

  static const String vouchers = '/vouchers';
  static const String payments = '/payments';
  static const String receipts = '/receipts';
  static const String adjustments = '/adjustments';

  static const String ledgers = '/ledgers';
  static String ledgerHistory(Object id) => '/ledgers/$id/history';
  static const String reportsDayBook = '/reports/day-book';
  static const String reportsCashBook = '/reports/cash-book';
  static const String reportsBankBook = '/reports/bank-book';
  static const String reportsLedger = '/reports/ledger';
  static const String reportsTrialBalance = '/reports/trial-balance';
  static const String reportsProfitLoss = '/reports/profit-loss';
  static const String reportsBalanceSheet = '/reports/balance-sheet';
  static const String reportsDebtorsOutstanding = '/reports/debtors-outstanding';
  static const String reportsCreditorsOutstanding =
      '/reports/creditors-outstanding';
  static const String exportDayBookPdf = '/export/day-book/pdf';
  static const String exportDayBookExcel = '/export/day-book/excel';
  static const String exportCashBookPdf = '/export/cash-book/pdf';
  static const String exportCashBookExcel = '/export/cash-book/excel';
  static const String exportBankBookPdf = '/export/bank-book/pdf';
  static const String exportBankBookExcel = '/export/bank-book/excel';
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
  static String exportMasterExcel(String type) => '/export/masters/$type/excel';
  static String exportMasterPdf(String type) => '/export/masters/$type/pdf';

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
  static const String serviceSalesInvoices = '/service-sales-invoices';
  static const String purchaseInvoices = '/purchase-invoices';

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
