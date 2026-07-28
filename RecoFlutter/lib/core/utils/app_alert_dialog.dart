import 'package:flutter/material.dart';
import 'package:get/get.dart';

class AppAlertDialog {
  static Future<void> show({
    required String title,
    required String message,
    String actionText = 'OK',
  }) {
    return Get.dialog<void>(
      _AppDialogCard(
        title: title,
        message: message,
        icon: Icons.info_outline_rounded,
        actions: <Widget>[
          _DialogActionButton(
            label: actionText,
            isPrimary: true,
            onTap: () => Get.back<void>(),
          ),
        ],
      ),
      barrierDismissible: true,
    );
  }

  static Future<bool> confirm({
    required String title,
    required String message,
    String cancelText = 'Cancel',
    String confirmText = 'Confirm',
    bool isDestructive = false,
  }) async {
    final result = await Get.dialog<bool>(
      _AppDialogCard(
        title: title,
        message: message,
        icon: isDestructive
            ? Icons.delete_outline_rounded
            : Icons.help_outline_rounded,
        isDestructive: isDestructive,
        actions: <Widget>[
          _DialogActionButton(
            label: cancelText,
            onTap: () => Get.back(result: false),
          ),
          _DialogActionButton(
            label: confirmText,
            isPrimary: true,
            isDestructive: isDestructive,
            onTap: () => Get.back(result: true),
          ),
        ],
      ),
      barrierDismissible: true,
    );
    return result ?? false;
  }

  static Future<bool> confirmDelete({
    required String title,
    required String message,
    String cancelText = 'Cancel',
    String confirmText = 'Delete',
  }) {
    return confirm(
      title: title,
      message: message,
      cancelText: cancelText,
      confirmText: confirmText,
      isDestructive: true,
    );
  }
}

class _AppDialogCard extends StatelessWidget {
  const _AppDialogCard({
    required this.title,
    required this.message,
    required this.actions,
    required this.icon,
    this.isDestructive = false,
  });

  final String title;
  final String message;
  final List<Widget> actions;
  final IconData icon;
  final bool isDestructive;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 24),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Row(
              children: <Widget>[
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: (isDestructive
                            ? scheme.errorContainer
                            : scheme.primaryContainer)
                        .withValues(alpha: .7),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(
                    icon,
                    color: isDestructive ? scheme.error : scheme.primary,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    title,
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            Text(
              message,
              style: theme.textTheme.bodyMedium?.copyWith(
                color: scheme.onSurfaceVariant,
                height: 1.4,
              ),
            ),
            const SizedBox(height: 18),
            Row(
              children: actions
                  .expand(
                    (action) => <Widget>[
                      Expanded(child: action),
                      const SizedBox(width: 10),
                    ],
                  )
                  .toList()
                ..removeLast(),
            ),
          ],
        ),
      ),
    );
  }
}

class _DialogActionButton extends StatelessWidget {
  const _DialogActionButton({
    required this.label,
    required this.onTap,
    this.isPrimary = false,
    this.isDestructive = false,
  });

  final String label;
  final VoidCallback onTap;
  final bool isPrimary;
  final bool isDestructive;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    final background = isPrimary
        ? (isDestructive ? scheme.error : scheme.primary)
        : Colors.transparent;
    final foreground = isPrimary
        ? (isDestructive ? scheme.onError : scheme.onPrimary)
        : scheme.onSurface;
    final borderColor = isPrimary
        ? Colors.transparent
        : scheme.outlineVariant;

    return InkWell(
      borderRadius: BorderRadius.circular(10),
      onTap: onTap,
      child: Ink(
        height: 44,
        decoration: BoxDecoration(
          color: background,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: borderColor),
        ),
        child: Center(
          child: Text(
            label,
            style: theme.textTheme.titleSmall?.copyWith(
              color: foreground,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ),
    );
  }
}
