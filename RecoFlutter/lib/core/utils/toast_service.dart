import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';

import 'app_message_position_config.dart';

enum MessageTypeGetx { success, error, warning }

final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

const String _successIconPath = 'assets/icons/ic_success.png';
const String _errorIconPath = 'assets/icons/ic_error.png';
const String _warningIconPath = 'assets/icons/ic_warning.png';

const Duration _defaultMessageDuration = Duration(milliseconds: 2200);

OverlayEntry? _activeMessageOverlay;
Timer? _activeMessageTimer;

Color _messageColor(MessageTypeGetx type, BuildContext context) {
  switch (type) {
    case MessageTypeGetx.success:
      return const Color(0xFF16A34A);
    case MessageTypeGetx.error:
      return const Color(0xFFDC2626);
    case MessageTypeGetx.warning:
      return const Color(0xFFF59E0B);
  }
}

String _messageIconPath(MessageTypeGetx type) {
  switch (type) {
    case MessageTypeGetx.success:
      return _successIconPath;
    case MessageTypeGetx.error:
      return _errorIconPath;
    case MessageTypeGetx.warning:
      return _warningIconPath;
  }
}

BuildContext? get _context => navigatorKey.currentContext;

void showMessageGetx(
  String msg,
  MessageTypeGetx type, {
  Duration? duration,
}) {
  final context = _context;
  if (context == null) {
    return;
  }

  Future<void>.delayed(Duration.zero, () {
    final currentContext = _context;
    final overlayState = navigatorKey.currentState?.overlay;
    if (currentContext == null || overlayState == null) {
      return;
    }

    _activeMessageTimer?.cancel();
    _activeMessageOverlay?.remove();

    _activeMessageOverlay = OverlayEntry(
      builder: (overlayContext) => AppMessagePositionConfig.buildPositioned(
        context: overlayContext,
        child: _buildMessageContent(
          currentContext,
          msg,
          type,
        ),
      ),
    );

    overlayState.insert(_activeMessageOverlay!);

    _activeMessageTimer = Timer(
      duration ?? _defaultMessageDuration,
      () {
        _activeMessageOverlay?.remove();
        _activeMessageOverlay = null;
        _activeMessageTimer = null;
      },
    );
  });
}

Widget _buildMessageContent(
  BuildContext context,
  String message,
  MessageTypeGetx type,
) {
  final color = _messageColor(type, context);
  final background = Theme.of(context).colorScheme.surface;

  return Material(
    color: Colors.transparent,
    child: Container(
      constraints: const BoxConstraints(minHeight: 54),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: .55)),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: color.withValues(alpha: .14),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Row(
        children: <Widget>[
          Container(
            width: 5,
            decoration: BoxDecoration(
              color: color,
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(14),
                bottomLeft: Radius.circular(14),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Image.asset(
            _messageIconPath(type),
            color: color,
            height: 24,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 12),
              child: Text(
                message,
                style: TextStyle(
                  color: color,
                  fontWeight: FontWeight.w600,
                  fontSize: 13.5,
                  height: 1.4,
                ),
              ),
            ),
          ),
          const SizedBox(width: 16),
        ],
      ),
    ),
  );
}

void showSuccessMessage(String msg, {Duration? duration}) =>
    showMessageGetx(msg, MessageTypeGetx.success, duration: duration);

void showErrorMessage(String msg, {Duration? duration}) =>
    showMessageGetx(msg, MessageTypeGetx.error, duration: duration);

void showWarningMessage(String msg, {Duration? duration}) =>
    showMessageGetx(msg, MessageTypeGetx.warning, duration: duration);

/// Shows a modal error alert dialog with the provided [message] as the body.
///
/// Used to surface server-returned error messages (e.g. validation or business
/// logic errors) that the [ApiClient] interceptor catches, so users see a
/// clear, dismissible error rather than a transient snackbar.
///
/// - [title] is shown above [message] (defaults to "Error").
/// - Pass additional [details] (e.g. Laravel `errors` map) to render under the
///   main message.
Future<void> showErrorDialog(
  String message, {
  String title = 'Error',
  Map<String, dynamic>? details,
}) async {
  final context = navigatorKey.currentContext;
  if (context == null || !context.mounted) {
    // Fallback to overlay message if no navigator context available.
    showErrorMessage(message);
    return;
  }

  // Avoid stacking multiple dialogs for concurrent errors.
  if (Get.isDialogOpen ?? false) {
    Get.back<void>();
  }

  final theme = Theme.of(context);
  final errorColor = const Color(0xFFDC2626);

  final detailsWidgets = <Widget>[];
  if (details != null && details.isNotEmpty) {
    detailsWidgets.add(const SizedBox(height: 10));
    detailsWidgets.add(
      Container(
        width: double.infinity,
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: errorColor.withValues(alpha: .08),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: errorColor.withValues(alpha: .25)),
        ),
        child: Text(
          _formatErrorDetails(details),
          style: theme.textTheme.bodySmall?.copyWith(
            color: theme.colorScheme.onSurface.withValues(alpha: .8),
            height: 1.35,
          ),
        ),
      ),
    );
  }

  await Get.dialog<void>(
    AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      titlePadding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
      contentPadding: const EdgeInsets.fromLTRB(20, 12, 20, 0),
      actionsPadding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
      title: Row(
        children: <Widget>[
          Icon(Icons.error_outline_rounded, color: errorColor, size: 22),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              title,
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
      content: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            Text(
              message,
              style: theme.textTheme.bodyMedium?.copyWith(height: 1.4),
            ),
            ...detailsWidgets,
          ],
        ),
      ),
      actions: <Widget>[
        TextButton(
          style: TextButton.styleFrom(
            minimumSize: const Size(80, 40),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(10),
            ),
          ),
          onPressed: () => Get.back<void>(),
          child: const Text(
            'OK',
            style: TextStyle(fontWeight: FontWeight.w700),
          ),
        ),
      ],
    ),
    barrierDismissible: true,
  );
}

String _formatErrorDetails(Map<String, dynamic> details) {
  final buffer = StringBuffer();
  details.forEach((key, value) {
    if (value == null) return;
    if (value is List) {
      final listText = value.map((e) => e.toString()).join(', ');
      buffer.writeln('• $key: $listText');
    } else if (value is Map) {
      value.forEach((subKey, subValue) {
        buffer.writeln('• $key.$subKey: $subValue');
      });
    } else {
      buffer.writeln('• $key: $value');
    }
  });
  return buffer.toString().trimRight();
}

extension MessageExtension on GetInterface {
  void showSuccessToast(String msg, {Duration? duration}) =>
      showSuccessMessage(msg, duration: duration);

  void showErrorToast(String msg, {Duration? duration}) =>
      showErrorMessage(msg, duration: duration);

  void showWarningToast(String msg, {Duration? duration}) =>
      showWarningMessage(msg, duration: duration);
}
