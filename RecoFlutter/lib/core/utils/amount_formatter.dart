class AmountFormatter {
  const AmountFormatter._();

  static double parse(dynamic value) {
    if (value is num) {
      return value.toDouble();
    }
    final normalized = value?.toString().trim() ?? '';
    if (normalized.isEmpty) {
      return 0;
    }
    return double.tryParse(normalized.replaceAll(',', '')) ?? 0;
  }

  static String amount(dynamic value) {
    return parse(value).toStringAsFixed(2);
  }

  static String currency(
    dynamic value, {
    String symbol = '₹',
    bool withSpace = false,
  }) {
    final amountValue = amount(value);
    return withSpace ? '$symbol $amountValue' : '$symbol$amountValue';
  }
}
