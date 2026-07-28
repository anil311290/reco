import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/services/network_monitor_service.dart';
import '../../../core/services/sync_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/repositories/accounts/accounts_repository.dart';
import '../../../data/repositories/settings/audit_logs_repository.dart';
import '../../../data/repositories/settings/notifications_repository.dart';
import '../../../data/repositories/settings/settings_repository.dart';
import '../../../data/repositories/settings/subscriptions_repository.dart';
import '../../../data/repositories/settings/support_tickets_repository.dart';
import '../../controllers/settings/admin_settings_controller.dart';
import '../../controllers/settings/audit_logs_controller.dart';
import '../../controllers/settings/notifications_center_controller.dart';
import '../../controllers/settings/settings_controller.dart';
import '../../controllers/settings/subscription_controller.dart';
import '../../controllers/settings/support_tickets_controller.dart';
import 'admin_settings_screen.dart';
import 'audit_logs_screen.dart';
import 'notifications_center_screen.dart';
import 'subscription_screen.dart';
import 'support_tickets_screen.dart';
import 'widgets/settings_ui_components.dart';

class SettingsScreen extends GetView<SettingsController> {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final network = Get.find<NetworkMonitorService>();
    final syncService = Get.find<SyncService>();

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Settings',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: <Widget>[
          AnimatedRotation(
            turns: controller.refreshTurns.value,
            duration: const Duration(milliseconds: 700),
            child: IconButton(
              onPressed: controller.isRefreshing.value
                  ? null
                  : controller.refreshAll,
              icon: Icon(
                Icons.refresh_rounded,
                color: controller.isRefreshing.value
                    ? theme.colorScheme.primary
                    : null,
              ),
            ),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: Obx(
        () => RefreshIndicator(
          onRefresh: controller.refreshAll,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: <Widget>[
              _ProfileCard(controller: controller),
              const SizedBox(height: 16),
              SettingsGroupCard(
                title: 'Web Settings',
                children: <Widget>[
                  SettingsMenuTile(
                    icon: Icons.business_outlined,
                    title: 'Company Settings',
                    subtitle: 'Company, theme, accounting and financial year',
                    onTap: _openAdminSettings,
                  ),
                  SettingsMenuTile(
                    icon: Icons.credit_card_outlined,
                    title: 'Subscription Plans',
                    subtitle: 'Current plan, plans, invoices and payments',
                    onTap: _openSubscription,
                  ),
                  SettingsMenuTile(
                    icon: Icons.notifications_none_rounded,
                    title: 'Notifications',
                    subtitle: 'In-app alerts for sync, support and activity',
                    onTap: _openNotifications,
                  ),
                  SettingsMenuTile(
                    icon: Icons.manage_history_rounded,
                    title: 'Audit Logs',
                    subtitle: 'Track create, update, delete and login activity',
                    onTap: _openAuditLogs,
                  ),
                  // SettingsMenuTile(
                  //   icon: Icons.lock_outline_rounded,
                  //   title: 'Security Settings',
                  //   subtitle: 'PIN, app lock, biometric and auto lock',
                  //   onTap: _openSecuritySettings,
                  // ),
                ],
              ),
              SettingsGroupCard(
                title: 'App Preferences',
                children: <Widget>[
                  SettingsMenuTile(
                    icon: Icons.palette_outlined,
                    title: 'Primary Color',
                    subtitle: controller.primaryColorHex,
                    trailing: Container(
                      width: 24,
                      height: 24,
                      decoration: BoxDecoration(
                        color: controller.primaryColor,
                        shape: BoxShape.circle,
                      ),
                    ),
                    onTap: () => _showColorSheet(context),
                  ),
                  SettingsMenuTile(
                    icon: Icons.dark_mode_outlined,
                    title: 'Theme',
                    subtitle:
                        AppTheme.mode.value == ThemeMode.dark ? 'Dark' : 'Light',
                    trailing: Switch(
                      value: controller.isDarkMode,
                      onChanged: (_) => controller.toggleTheme(),
                    ),
                    onTap: controller.toggleTheme,
                  ),
                  SettingsMenuTile(
                    icon: network.isOnline.value
                        ? Icons.cloud_done_outlined
                        : Icons.cloud_off_outlined,
                    title: 'Sync Center',
                    subtitle: controller.syncStatusLabel,
                    trailing: syncService.isSyncing.value ||
                            controller.isSyncTriggering.value
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : FilledButton.tonal(
                            onPressed: controller.runManualSync,
                            style: FilledButton.styleFrom(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 12,
                                vertical: 8,
                              ),
                            ),
                            child: const Text('Sync'),
                          ),
                  ),
                ],
              ),
              SettingsGroupCard(
                title: 'Support',
                children: <Widget>[
                  SettingsMenuTile(
                    icon: Icons.help_outline_rounded,
                    title: 'Help & Support',
                    subtitle: 'Ticket, contact and onboarding help',
                    onTap: _openSupportTickets,
                  ),
                  SettingsMenuTile(
                    icon: Icons.logout_rounded,
                    title: controller.isLoggingOut.value
                        ? 'Logging out...'
                        : 'Logout',
                    subtitle: 'End current session on this device',
                    color: theme.colorScheme.error,
                    trailing: controller.isLoggingOut.value
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : Icon(
                            Icons.chevron_right_rounded,
                            color: theme.colorScheme.error,
                          ),
                    onTap: controller.isLoggingOut.value
                        ? null
                        : controller.confirmLogout,
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showColorSheet(BuildContext context) {
    showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      builder: (_) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 20),
            child: Wrap(
              runSpacing: 14,
              spacing: 14,
              children: controller.presetColors
                  .map(
                    (color) => SettingsColorDot(
                      color: color,
                      isSelected:
                          color.toARGB32() == controller.primaryColor.toARGB32(),
                      onTap: () {
                        controller.updatePrimaryColor(color);
                        Get.back<void>();
                      },
                    ),
                  )
                  .toList(),
            ),
          ),
        );
      },
    );
  }

  void _openAdminSettings() {
    Get.to(
      () => const AdminSettingsScreen(),
      binding: BindingsBuilder(
        () {
          Get.put(
            AdminSettingsController(
              Get.find<SettingsRepository>(),
              Get.find<AccountsRepository>(),
            ),
          );
        },
      ),
    );
  }

  void _openNotifications() {
    Get.to(
      () => const NotificationsCenterScreen(),
      binding: BindingsBuilder(
        () {
          Get.put(
            NotificationsCenterController(Get.find<NotificationsRepository>()),
          );
        },
      ),
    );
  }

  void _openSupportTickets() {
    Get.to(
      () => const SupportTicketsScreen(),
      binding: BindingsBuilder(
        () {
          Get.put(
            SupportTicketsController(Get.find<SupportTicketsRepository>()),
          );
        },
      ),
    );
  }

  void _openAuditLogs() {
    Get.to(
      () => const AuditLogsScreen(),
      binding: BindingsBuilder(
        () {
          Get.put(
            AuditLogsController(Get.find<AuditLogsRepository>()),
          );
        },
      ),
    );
  }

  void _openSubscription() {
    Get.to(
      () => const SubscriptionScreen(),
      binding: BindingsBuilder(
        () {
          Get.put(
            SubscriptionController(Get.find<SubscriptionsRepository>()),
          );
        },
      ),
    );
  }
}

class _ProfileCard extends StatelessWidget {
  const _ProfileCard({required this.controller});

  final SettingsController controller;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: <Color>[
            theme.colorScheme.primary,
            theme.colorScheme.primary.withValues(alpha: .82),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(24),
      ),
      child: Row(
        children: <Widget>[
          Container(
            width: 58,
            height: 58,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: .14),
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(
                controller.companyName.isEmpty
                    ? 'R'
                    : controller.companyName.trim()[0].toUpperCase(),
                style: theme.textTheme.titleLarge?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  controller.companyName,
                  style: theme.textTheme.titleMedium?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  controller.userName,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: Colors.white.withValues(alpha: .92),
                  ),
                ),
                if (controller.userEmail.isNotEmpty) ...<Widget>[
                  const SizedBox(height: 2),
                  Text(
                    controller.userEmail,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: Colors.white.withValues(alpha: .78),
                    ),
                  ),
                ],
              ],
            ),
          ),
          if (controller.isRefreshingProfile.value)
            const SizedBox(
              width: 18,
              height: 18,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
              ),
            ),
        ],
      ),
    );
  }
}
