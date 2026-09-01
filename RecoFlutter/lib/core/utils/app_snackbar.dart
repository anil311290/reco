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

  /// Shows a modal alert dialog with the provided [message].
  ///
  /// Use this for server-returned error messages coming from the
  /// [ApiClient] interceptor (e.g. Laravel validation/business errors),
  /// so the user sees a clear, dismissible error instead of a transient
  /// snackbar that auto-hides.
  ///
  /// - [title] is the dialog heading (defaults to "Error").
  /// - [details] is an optional map (e.g. Laravel `errors`) rendered as
  ///   a sub-list under the main message.
  static Future<void> errorDialog(
    String message, {
    String title = 'Error',
    Map<String, dynamic>? details,
  }) {
    return showErrorDialog(message, title: title, details: details);
  }
}
