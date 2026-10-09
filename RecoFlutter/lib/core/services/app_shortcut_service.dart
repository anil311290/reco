import 'dart:async';

import 'package:flutter/services.dart';
import 'package:get/get.dart';

import '../../presentation/controllers/main/main_controller.dart';
import '../../presentation/views/transactions/transaction_options_screen.dart';
import 'local_storage_service.dart';

class AppShortcutService extends GetxService {
  AppShortcutService(this._localStorageService);

  static const MethodChannel _channel = MethodChannel(
    'reco_flutter/app_shortcuts',
  );

  final LocalStorageService _localStorageService;
  String? _pendingAction;
  bool _isOpening = false;

  Future<AppShortcutService> init() async {
    _channel.setMethodCallHandler(_handleNativeCall);
    try {
      final initialAction = await _channel
          .invokeMethod<String>('getInitialShortcutAction')
          .timeout(const Duration(milliseconds: 800));
      _setPendingAction(initialAction);
      unawaited(consumePendingShortcut());
    } catch (_) {
      // Android shortcuts are best-effort; unsupported platforms simply skip.
    }
    return this;
  }

  Future<dynamic> _handleNativeCall(MethodCall call) async {
    if (call.method == 'shortcutAction') {
      final action = call.arguments?.toString();
      _setPendingAction(action);
      unawaited(consumePendingShortcut());
    }
    return null;
  }

  void _setPendingAction(String? action) {
    final mappedAction = _mapShortcutAction(action);
    if (mappedAction == null) {
      return;
    }
    _pendingAction = mappedAction;
  }

  Future<void> consumePendingShortcut() async {
    final action = _pendingAction;
    if (action == null ||
        _isOpening ||
        !_localStorageService.hasActiveSession) {
      return;
    }

    _isOpening = true;
    try {
      for (var attempt = 0; attempt < 8; attempt++) {
        if (Get.isRegistered<MainController>()) {
          break;
        }
        await Future<void>.delayed(const Duration(milliseconds: 150));
      }

      if (!Get.isRegistered<MainController>()) {
        return;
      }

      _pendingAction = null;
      Get.find<MainController>().changeTab(1);
      await Future<void>.delayed(const Duration(milliseconds: 180));
      await openTransactionForm(action);
    } finally {
      _isOpening = false;
    }
  }

  String? _mapShortcutAction(String? action) {
    switch (action) {
      case 'sales':
      case 'purchase':
      case 'payment':
      case 'receipt':
      case 'adjustment':
        return action;
    }
    return null;
  }
}
