import 'package:flutter/cupertino.dart';
import 'package:get/get.dart';

class AppAlertDialog {
  static Future<void> show({
    required String title,
    required String message,
    String actionText = 'OK',
  }) {
    return Get.dialog<void>(
      CupertinoAlertDialog(
        title: Text(title),
        content: Padding(
          padding: const EdgeInsets.only(top: 12),
          child: Text(message),
        ),
        actions: [
          CupertinoDialogAction(
            onPressed: Get.back,
            isDefaultAction: true,
            child: Text(actionText),
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
    String confirmText = 'Delete',
    bool isDestructive = true,
  }) async {
    final result = await Get.dialog<bool>(
      CupertinoAlertDialog(
        title: Text(title),
        content: Padding(
          padding: const EdgeInsets.only(top: 12),
          child: Text(message),
        ),
        actions: [
          CupertinoDialogAction(
            onPressed: () => Get.back(result: false),
            child: Text(cancelText),
          ),
          CupertinoDialogAction(
            onPressed: () => Get.back(result: true),
            isDestructiveAction: isDestructive,
            child: Text(confirmText),
          ),
        ],
      ),
      barrierDismissible: true,
    );
    return result ?? false;
  }
}
