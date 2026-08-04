class AppDateFormatter {
  static const List<String> _months = <String>[
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
  ];

  static DateTime? parse(dynamic value) {
    final raw = value?.toString().trim() ?? '';
    if (raw.isEmpty) {
      return null;
    }

    final isoParsed = DateTime.tryParse(raw);
    if (isoParsed != null) {
      return isoParsed;
    }

    final displayMatch = RegExp(
      r'^(\d{1,2})-([A-Za-z]{3})-(\d{4})(?:\s+(\d{1,2}):(\d{2})(?:\s*([AP]M))?)?$',
      caseSensitive: false,
    ).firstMatch(raw);
    if (displayMatch != null) {
      final day = int.tryParse(displayMatch.group(1) ?? '');
      final monthName = _capitalize(displayMatch.group(2) ?? '');
      final year = int.tryParse(displayMatch.group(3) ?? '');
      final month = _months.indexOf(monthName) + 1;
      if (day != null && year != null && month > 0) {
        var hour = int.tryParse(displayMatch.group(4) ?? '') ?? 0;
        final minute = int.tryParse(displayMatch.group(5) ?? '') ?? 0;
        final meridiem = (displayMatch.group(6) ?? '').toUpperCase();
        if (meridiem == 'PM' && hour < 12) {
          hour += 12;
        } else if (meridiem == 'AM' && hour == 12) {
          hour = 0;
        }
        return DateTime(year, month, day, hour, minute);
      }
    }

    final slashMatch = RegExp(r'^(\d{1,2})/(\d{1,2})/(\d{4})$').firstMatch(raw);
    if (slashMatch != null) {
      final day = int.tryParse(slashMatch.group(1) ?? '');
      final month = int.tryParse(slashMatch.group(2) ?? '');
      final year = int.tryParse(slashMatch.group(3) ?? '');
      if (day != null && month != null && year != null) {
        return DateTime(year, month, day);
      }
    }

    return null;
  }

  static String formatDisplay(dynamic value, {String fallback = ''}) {
    final parsed = parse(value);
    if (parsed == null) {
      return fallback.isNotEmpty ? fallback : (value?.toString() ?? '');
    }
    return '${parsed.day.toString().padLeft(2, '0')}-${_months[parsed.month - 1]}-${parsed.year}';
  }

  static String formatDateTime(dynamic value, {String fallback = ''}) {
    final parsed = parse(value);
    if (parsed == null) {
      return fallback.isNotEmpty ? fallback : (value?.toString() ?? '');
    }
    final hour24 = parsed.hour;
    final hour12 = hour24 == 0 ? 12 : (hour24 > 12 ? hour24 - 12 : hour24);
    final minute = parsed.minute.toString().padLeft(2, '0');
    final meridiem = hour24 >= 12 ? 'PM' : 'AM';
    return '${formatDisplay(parsed)} $hour12:$minute $meridiem';
  }

  static String toApiDate(dynamic value, {String fallback = ''}) {
    final parsed = parse(value);
    if (parsed == null) {
      return fallback.isNotEmpty ? fallback : (value?.toString() ?? '');
    }
    return '${parsed.year.toString().padLeft(4, '0')}-${parsed.month.toString().padLeft(2, '0')}-${parsed.day.toString().padLeft(2, '0')}';
  }

  static int compareDates(dynamic left, dynamic right) {
    final leftDate = parse(left);
    final rightDate = parse(right);
    if (leftDate == null || rightDate == null) {
      return 0;
    }
    return leftDate.compareTo(rightDate);
  }

  static String _capitalize(String value) {
    if (value.isEmpty) {
      return value;
    }
    final normalized = value.toLowerCase();
    return '${normalized[0].toUpperCase()}${normalized.substring(1)}';
  }
}
