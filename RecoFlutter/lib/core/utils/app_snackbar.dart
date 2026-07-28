import 'toast_service.dart';

class AppSnackbar {
  AppSnackbar._();

  static void success(String message) {
    showSuccessMessage(message);
  }

  static void error(String message) {
    showErrorMessage(message);
  }

  static void warning(String message) {
    showWarningMessage(message);
  }
}
