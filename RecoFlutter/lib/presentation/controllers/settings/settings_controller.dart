import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/database/app_database_service.dart';
import '../../../core/services/network_monitor_service.dart';
import '../../../core/services/local_storage_service.dart';
import '../../../core/services/sync_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/repositories/auth/auth_repository.dart';
import '../../../data/repositories/reports/reports_repository.dart';

class SettingsController extends GetxController {
  SettingsController(
    this._authRepository,
    this._reportsRepository,
    this._localStorageService,
    this._databaseService,
    this._syncService,
    this._networkMonitorService,
  );

  final AuthRepository _authRepository;
  final ReportsRepository _reportsRepository;
  final LocalStorageService _localStorageService;
  final AppDatabaseService _databaseService;
  final SyncService _syncService;
  final NetworkMonitorService _networkMonitorService;

  final isLoading = false.obs;
  final isRefreshing = false.obs;
  final refreshTurns = 0.0.obs;
  final isRefreshingProfile = false.obs;
  final isSyncTriggering = false.obs;
  final isLoggingOut = false.obs;
  final profile = <String, dynamic>{}.obs;
  final currentFinancialYear = <String, dynamic>{}.obs;
  final pendingSyncCount = 0.obs;

  final presetColors = const <Color>[
    Color(0xFF2563EB),
    Color(0xFFE94B67),
    Color(0xFF16A34A),
    Color(0xFFF59E0B),
    Color(0xFF0F766E),
    Color(0xFFDC2626),
    Color(0xFF111827),
    Color(0xFF7C3AED),
  ];

  String get companyName {
    final user = profile.isNotEmpty ? profile : (_localStorageService.user ?? {});
    return (user['company_name'] ??
            user['company']?['name'] ??
            user['name'] ??
            'Reco User')
        .toString();
  }

  String get userName {
    final user = profile.isNotEmpty ? profile : (_localStorageService.user ?? {});
    return (user['name'] ?? user['user_name'] ?? 'Admin User').toString();
  }

  String get userEmail {
    final user = profile.isNotEmpty ? profile : (_localStorageService.user ?? {});
    return (user['email'] ?? '').toString();
  }

  String get userPhone {
    final user = profile.isNotEmpty ? profile : (_localStorageService.user ?? {});
    return (user['mobile'] ?? user['phone'] ?? '').toString();
  }

  String get financialYearLabel {
    if (currentFinancialYear.isEmpty) {
      return 'Not available';
    }
    return (currentFinancialYear['name'] ??
            currentFinancialYear['financial_year'] ??
            currentFinancialYear['label'] ??
            'Current year')
        .toString();
  }

  String get syncStatusLabel {
    if (!_networkMonitorService.isOnline.value) {
      return pendingSyncCount.value > 0
          ? '$pendingSyncCount pending, offline'
          : 'Offline mode';
    }
    if (_syncService.isSyncing.value || isSyncTriggering.value) {
      return 'Sync in progress...';
    }
    return pendingSyncCount.value > 0
        ? '$pendingSyncCount changes pending sync'
        : 'All local changes synced';
  }

  bool get isDarkMode => AppTheme.mode.value == ThemeMode.dark;
  Color get primaryColor => AppTheme.primaryColor.value;
  String get primaryColorHex {
    final color = primaryColor.toARGB32() & 0xFFFFFF;
    return '#${color.toRadixString(16).padLeft(6, '0').toUpperCase()}';
  }

  @override
  void onInit() {
    super.onInit();
    loadSettingsData();
  }

  Future<void> loadSettingsData() async {
    isLoading.value = true;
    try {
      await Future.wait(<Future<void>>[
        loadProfile(),
        loadCurrentFinancialYear(),
        loadPendingSyncCount(),
      ]);
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> loadProfile() async {
    isRefreshingProfile.value = true;
    try {
      final data = await _authRepository.fetchProfile();
      profile.assignAll(data);
      final mergedUser = <String, dynamic>{
        ...?_localStorageService.user,
        ...data,
      };
      await _localStorageService.saveUser(mergedUser);
    } catch (_) {
      profile.assignAll(_localStorageService.user ?? <String, dynamic>{});
    } finally {
      isRefreshingProfile.value = false;
    }
  }

  Future<void> loadCurrentFinancialYear() async {
    try {
      final data = await _reportsRepository.getCurrentFinancialYear();
      currentFinancialYear.assignAll(data ?? <String, dynamic>{});
    } catch (_) {
      currentFinancialYear.clear();
    }
  }

  Future<void> loadPendingSyncCount() async {
    final pending = await _databaseService.getPendingSyncQueue();
    pendingSyncCount.value = pending.length;
  }

  Future<void> runManualSync() async {
    if (isSyncTriggering.value) {
      return;
    }
    isSyncTriggering.value = true;
    try {
      await _syncService.syncPendingMutations();
      await loadPendingSyncCount();
    } finally {
      isSyncTriggering.value = false;
    }
  }

  Future<void> refreshAll() async {
    if (isRefreshing.value) {
      return;
    }
    isRefreshing.value = true;
    refreshTurns.value += 1;
    try {
      await loadSettingsData();
    } finally {
      isRefreshing.value = false;
    }
  }

  void toggleTheme() {
    AppTheme.toggle();
    update();
  }

  void updatePrimaryColor(Color color) {
    AppTheme.updatePrimaryColor(color);
    update();
  }

  Future<void> confirmLogout() async {
    final theme = Get.theme;
    final scheme = theme.colorScheme;

    await Get.dialog<void>(
      Dialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
        ),
        insetPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Row(
                children: <Widget>[
                  Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: scheme.error.withValues(alpha: .10),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(
                      Icons.logout_rounded,
                      color: scheme.error,
                      size: 20,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'Logout',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Text(
                'Do you want to log out of the current session?',
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: scheme.onSurfaceVariant,
                  height: 1.35,
                ),
              ),
              const SizedBox(height: 20),
              Row(
                children: <Widget>[
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () => Get.back<void>(),
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size.fromHeight(46),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                      child: const Text('Cancel'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: FilledButton(
                      onPressed: () {
                        Get.back<void>();
                        logout();
                      },
                      style: FilledButton.styleFrom(
                        backgroundColor: scheme.error,
                        foregroundColor: scheme.onError,
                        minimumSize: const Size.fromHeight(46),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                      child: const Text('Logout'),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
      barrierDismissible: true,
    );
  }

  Future<void> logout() async {
    if (isLoggingOut.value) {
      return;
    }
    isLoggingOut.value = true;
    try {
      await _authRepository.logout();
    } catch (_) {
      // Local cleanup should still continue when API logout fails.
    }
    await _localStorageService.clearSession();
    AppSnackbar.success('Session cleared successfully.');
    isLoggingOut.value = false;
    Get.offAllNamed('/login');
  }
}
