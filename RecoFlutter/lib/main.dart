import 'package:flutter/widgets.dart';
import 'package:get/get.dart';
import 'package:get_storage/get_storage.dart';

import 'core/app/app.dart';
import 'core/database/app_database_service.dart';
import 'core/network/api_client.dart';
import 'core/services/local_storage_service.dart';
import 'core/services/network_monitor_service.dart';
import 'core/services/sync_service.dart';
import 'core/theme/theme_controller.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await GetStorage.init();

  final localStorageService = Get.put(LocalStorageService(), permanent: true);
  Get.put(ThemeController(), permanent: true);

  final databaseService = await AppDatabaseService().init();
  Get.put(databaseService, permanent: true);

  final apiClient = Get.put(ApiClient(localStorageService), permanent: true);

  final networkMonitorService = await NetworkMonitorService().init();
  Get.put(networkMonitorService, permanent: true);

  final syncService = await SyncService(
    apiClient,
    databaseService,
    networkMonitorService,
  ).init();
  Get.put(syncService, permanent: true);

  runApp(const RecoApp());
}
