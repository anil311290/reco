import 'package:flutter/material.dart';
import 'package:get/get.dart';

class AppActionLoader {
  static bool _isVisible = false;

  static Future<T> run<T>(
    Future<T> Function() action, {
    String message = 'Please wait...',
  }) async {
    _show(message: message);
    try {
      return await action();
    } finally {
      _hide();
    }
  }

  static void _show({required String message}) {
    if (_isVisible) {
      return;
    }
    _isVisible = true;
    Get.dialog<void>(
      PopScope(
        canPop: false,
        child: _ActionLoaderDialog(message: message),
      ),
      barrierDismissible: false,
    );
  }

  static void _hide() {
    if (!_isVisible) {
      return;
    }
    _isVisible = false;
    if (Get.isDialogOpen == true) {
      Get.back<void>();
    }
  }
}

class _ActionLoaderDialog extends StatelessWidget {
  const _ActionLoaderDialog({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 42),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            SizedBox(
              width: 24,
              height: 24,
              child: CircularProgressIndicator(
                strokeWidth: 2.6,
                color: theme.colorScheme.primary,
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Text(
                message,
                style: theme.textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
