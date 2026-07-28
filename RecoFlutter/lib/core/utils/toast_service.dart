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

extension MessageExtension on GetInterface {
  void showSuccessToast(String msg, {Duration? duration}) =>
      showSuccessMessage(msg, duration: duration);

  void showErrorToast(String msg, {Duration? duration}) =>
      showErrorMessage(msg, duration: duration);

  void showWarningToast(String msg, {Duration? duration}) =>
      showWarningMessage(msg, duration: duration);
}
