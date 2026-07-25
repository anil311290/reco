import 'package:get/get.dart';

import '../database/app_database_service.dart';
import '../network/api_client.dart';
import '../services/local_storage_service.dart';
import '../services/network_monitor_service.dart';
import '../services/sync_service.dart';
import '../theme/theme_controller.dart';
import '../../data/repositories/accounts/accounts_repository.dart';
import '../../data/repositories/auth/auth_repository.dart';
import '../../data/repositories/dashboard/dashboard_repository.dart';
import '../../data/repositories/masters/item_categories_repository.dart';
import '../../data/repositories/masters/masters_export_repository.dart';
import '../../data/repositories/masters/items_repository.dart';
import '../../data/repositories/masters/locations_repository.dart';
import '../../data/repositories/masters/parties_repository.dart';
import '../../data/repositories/masters/tax_rates_repository.dart';
import '../../data/repositories/masters/financial_years_repository.dart';
import '../../data/repositories/reports/reports_repository.dart';
import '../../data/repositories/settings/notifications_repository.dart';
import '../../data/repositories/settings/audit_logs_repository.dart';
import '../../data/repositories/settings/security_repository.dart';
import '../../data/repositories/settings/settings_repository.dart';
import '../../data/repositories/settings/subscriptions_repository.dart';
import '../../data/repositories/settings/support_tickets_repository.dart';
import '../../data/repositories/transactions/transactions_repository.dart';
import '../../presentation/controllers/splash/splash_controller.dart';

class InitialBinding extends Bindings {
  @override
  void dependencies() {
    if (!Get.isRegistered<LocalStorageService>()) {
      Get.put(LocalStorageService(), permanent: true);
    }
    if (!Get.isRegistered<ThemeController>()) {
      Get.put(ThemeController(), permanent: true);
    }
    if (!Get.isRegistered<AppDatabaseService>()) {
      throw StateError(
        'AppDatabaseService must be initialized before bindings.',
      );
    }
    if (!Get.isRegistered<ApiClient>()) {
      Get.put(ApiClient(Get.find<LocalStorageService>()), permanent: true);
    }
    if (!Get.isRegistered<NetworkMonitorService>()) {
      throw StateError(
        'NetworkMonitorService must be initialized before bindings.',
      );
    }
    if (!Get.isRegistered<SyncService>()) {
      throw StateError('SyncService must be initialized before bindings.');
    }
    Get.lazyPut<SplashController>(
      () => SplashController(
        Get.find<LocalStorageService>(),
        Get.find<AuthRepository>(),
        Get.find<NetworkMonitorService>(),
      ),
      fenix: true,
    );
    Get.lazyPut<AuthRepository>(
      () => AuthRepository(Get.find<ApiClient>()),
      fenix: true,
    );
    Get.lazyPut<DashboardRepository>(
      () => DashboardRepository(
        Get.find<ApiClient>(),
        Get.find<AppDatabaseService>(),
        Get.find<NetworkMonitorService>(),
        Get.find<SyncService>(),
      ),
      fenix: true,
    );
    Get.lazyPut<AccountsRepository>(
      () => AccountsRepository(
        Get.find<ApiClient>(),
        Get.find<AppDatabaseService>(),
        Get.find<NetworkMonitorService>(),
        Get.find<SyncService>(),
      ),
      fenix: true,
    );
    Get.lazyPut<PartiesRepository>(
      () => PartiesRepository(
        Get.find<ApiClient>(),
        Get.find<AppDatabaseService>(),
        Get.find<NetworkMonitorService>(),
        Get.find<SyncService>(),
      ),
      fenix: true,
    );
    Get.lazyPut<ItemCategoriesRepository>(
      () => ItemCategoriesRepository(
        Get.find<ApiClient>(),
        Get.find<AppDatabaseService>(),
        Get.find<NetworkMonitorService>(),
        Get.find<SyncService>(),
      ),
      fenix: true,
    );
    Get.lazyPut<TaxRatesRepository>(
      () => TaxRatesRepository(
        Get.find<ApiClient>(),
        Get.find<AppDatabaseService>(),
        Get.find<NetworkMonitorService>(),
        Get.find<SyncService>(),
      ),
      fenix: true,
    );
    Get.lazyPut<ItemsRepository>(
      () => ItemsRepository(
        Get.find<ApiClient>(),
        Get.find<AppDatabaseService>(),
        Get.find<NetworkMonitorService>(),
        Get.find<SyncService>(),
      ),
      fenix: true,
    );
    Get.lazyPut<LocationsRepository>(
      () => LocationsRepository(
        Get.find<ApiClient>(),
        Get.find<AppDatabaseService>(),
        Get.find<NetworkMonitorService>(),
        Get.find<SyncService>(),
      ),
      fenix: true,
    );
    Get.lazyPut<MastersExportRepository>(
      () => MastersExportRepository(Get.find<ApiClient>()),
      fenix: true,
    );
    Get.lazyPut<TransactionsRepository>(
      () => TransactionsRepository(
        Get.find<ApiClient>(),
        Get.find<AppDatabaseService>(),
        Get.find<NetworkMonitorService>(),
        Get.find<SyncService>(),
      ),
      fenix: true,
    );
    Get.lazyPut<ReportsRepository>(
      () => ReportsRepository(
        Get.find<ApiClient>(),
        Get.find<AppDatabaseService>(),
        Get.find<NetworkMonitorService>(),
        Get.find<SyncService>(),
      ),
      fenix: true,
    );
    Get.lazyPut<SettingsRepository>(
      () => SettingsRepository(Get.find<ApiClient>()),
      fenix: true,
    );
    Get.lazyPut<SecurityRepository>(
      () => SecurityRepository(Get.find<ApiClient>()),
      fenix: true,
    );
    Get.lazyPut<NotificationsRepository>(
      () => NotificationsRepository(Get.find<ApiClient>()),
      fenix: true,
    );
    Get.lazyPut<AuditLogsRepository>(
      () => AuditLogsRepository(
        Get.find<ApiClient>(),
        Get.find<AppDatabaseService>(),
        Get.find<NetworkMonitorService>(),
        Get.find<SyncService>(),
      ),
      fenix: true,
    );
    Get.lazyPut<SupportTicketsRepository>(
      () => SupportTicketsRepository(
        Get.find<ApiClient>(),
        Get.find<AppDatabaseService>(),
        Get.find<NetworkMonitorService>(),
        Get.find<SyncService>(),
      ),
      fenix: true,
    );
    Get.lazyPut<SubscriptionsRepository>(
      () => SubscriptionsRepository(Get.find<ApiClient>()),
      fenix: true,
    );
    Get.lazyPut<FinancialYearsRepository>(
      () => FinancialYearsRepository(
        Get.find<ApiClient>(),
        Get.find<AppDatabaseService>(),
        Get.find<NetworkMonitorService>(),
        Get.find<SyncService>(),
      ),
      fenix: true,
    );
  }
}
