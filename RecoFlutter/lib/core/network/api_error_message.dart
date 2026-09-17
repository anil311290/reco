import 'package:dio/dio.dart';

/// Extracts a user-facing message from a failed API call.
String extractApiErrorMessage(Object error) {
  if (error is DioException) {
    final responseData = error.response?.data;
    if (responseData is Map<String, dynamic>) {
      final message = responseData['message']?.toString();
      if (message != null && message.trim().isNotEmpty) {
        return _sanitizeServerMessage(message);
      }

      final errors = responseData['errors'];
      if (errors is Map) {
        final buffer = StringBuffer();
        errors.forEach((key, value) {
          if (value is List && value.isNotEmpty) {
            buffer.writeln('• $key: ${value.first}');
          } else if (value != null) {
            buffer.writeln('• $key: $value');
          }
        });
        final details = buffer.toString().trim();
        if (details.isNotEmpty) {
          return details;
        }
      }
    }

    final dioMessage = error.message?.trim();
    if (dioMessage != null && dioMessage.isNotEmpty) {
      return dioMessage;
    }
  }

  final raw = error.toString().trim();
  if (raw.startsWith('Exception: ')) {
    return raw.substring('Exception: '.length);
  }
  return raw.isEmpty ? 'Something went wrong. Please try again.' : raw;
}

String _sanitizeServerMessage(String message) {
  if (message.contains('SQLSTATE') ||
      message.contains('Operation not permitted') ||
      message.contains('Connection:')) {
    return 'Unable to reach the server database. Please try again.';
  }
  return message;
}
