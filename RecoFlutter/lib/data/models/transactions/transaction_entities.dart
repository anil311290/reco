enum TransactionRecordKind { voucher, salesInvoice, purchaseInvoice }

class TransactionRecord {
  const TransactionRecord({
    required this.kind,
    required this.rawPayload,
    this.id,
    this.localId,
    this.syncStatus = 'synced',
    this.isDirty = false,
    this.number = '',
    this.type = '',
    this.typeLabel = '',
    this.partyId,
    this.partyName = '',
    this.date = '',
    this.dueDate = '',
    this.status = '',
    this.statusLabel = '',
    this.amount = 0,
    this.amountPaid = 0,
    this.balanceDue = 0,
    this.supplierReference = '',
    this.narration = '',
  });

  final TransactionRecordKind kind;
  final int? id;
  final String? localId;
  final String syncStatus;
  final bool isDirty;
  final String number;
  final String type;
  final String typeLabel;
  final int? partyId;
  final String partyName;
  final String date;
  final String dueDate;
  final String status;
  final String statusLabel;
  final double amount;
  final double amountPaid;
  final double balanceDue;
  final String supplierReference;
  final String narration;
  final Map<String, dynamic> rawPayload;

  factory TransactionRecord.fromVoucher(Map<String, dynamic> record) {
    final payload = _recordPayload(record);
    final party = payload['party'];
    final voucherType = (payload['voucher_type'] ?? '').toString();
    return TransactionRecord(
      kind: TransactionRecordKind.voucher,
      id: _tryParseInt(payload['id']),
      localId: record['local_id']?.toString(),
      syncStatus: record['sync_status']?.toString() ?? 'synced',
      isDirty: record['is_dirty'] == true,
      number: (payload['voucher_number'] ?? '').toString(),
      type: voucherType,
      typeLabel: (payload['type_label'] ?? _voucherLabel(voucherType)).toString(),
      partyId: _tryParseInt(payload['party_id']),
      partyName: party is Map<String, dynamic>
          ? (party['name'] ?? '').toString()
          : '',
      date: (payload['voucher_date'] ?? '').toString(),
      status: (payload['status'] ?? '').toString(),
      statusLabel: _titleCase((payload['status'] ?? '').toString()),
      amount: _parseDouble(payload['total_debit']),
      narration: (payload['narration'] ?? payload['remarks'] ?? '').toString(),
      rawPayload: payload,
    );
  }

  factory TransactionRecord.fromSalesInvoice(Map<String, dynamic> record) {
    final payload = _recordPayload(record);
    final party = payload['party'];
    final invoiceType = (payload['invoice_type'] ?? 'item').toString();
    return TransactionRecord(
      kind: TransactionRecordKind.salesInvoice,
      id: _tryParseInt(payload['id']),
      localId: record['local_id']?.toString(),
      syncStatus: record['sync_status']?.toString() ?? 'synced',
      isDirty: record['is_dirty'] == true,
      number: (payload['invoice_number'] ?? '').toString(),
      type: invoiceType,
      typeLabel: 'Sales Invoice',
      partyId: _tryParseInt(payload['party_id']),
      partyName: party is Map<String, dynamic>
          ? (party['name'] ?? '').toString()
          : '',
      date: (payload['invoice_date'] ?? '').toString(),
      dueDate: (payload['due_date'] ?? '').toString(),
      status: (payload['status'] ?? '').toString(),
      statusLabel: (payload['status_label'] ?? '').toString(),
      amount: _parseDouble(payload['total']),
      amountPaid: _parseDouble(payload['amount_paid']),
      balanceDue: _parseDouble(payload['balance_due']),
      narration: (payload['notes'] ?? '').toString(),
      rawPayload: payload,
    );
  }

  factory TransactionRecord.fromPurchaseInvoice(Map<String, dynamic> record) {
    final payload = _recordPayload(record);
    final party = payload['party'];
    return TransactionRecord(
      kind: TransactionRecordKind.purchaseInvoice,
      id: _tryParseInt(payload['id']),
      localId: record['local_id']?.toString(),
      syncStatus: record['sync_status']?.toString() ?? 'synced',
      isDirty: record['is_dirty'] == true,
      number: (payload['invoice_number'] ?? '').toString(),
      type: 'purchase',
      typeLabel: 'Purchase',
      partyId: _tryParseInt(payload['party_id']),
      partyName: party is Map<String, dynamic>
          ? (party['name'] ?? '').toString()
          : '',
      date: (payload['invoice_date'] ?? '').toString(),
      dueDate: (payload['due_date'] ?? '').toString(),
      status: (payload['status'] ?? '').toString(),
      statusLabel: (payload['status_label'] ?? '').toString(),
      amount: _parseDouble(payload['total']),
      amountPaid: _parseDouble(payload['amount_paid']),
      balanceDue: _parseDouble(payload['balance_due']),
      supplierReference: (payload['supplier_invoice_number'] ?? '').toString(),
      narration: (payload['notes'] ?? '').toString(),
      rawPayload: payload,
    );
  }

  Map<String, dynamic> payloadWithStatus(String nextStatus) {
    return <String, dynamic>{...rawPayload, 'status': nextStatus};
  }
}

class TransactionLookupOption {
  const TransactionLookupOption({
    required this.id,
    required this.label,
  });

  final int id;
  final String label;
}

Map<String, dynamic> _recordPayload(Map<String, dynamic> record) {
  final payload = record['payload'];
  if (payload is Map<String, dynamic>) {
    return payload;
  }
  return record;
}

int? _tryParseInt(dynamic value) {
  if (value == null) {
    return null;
  }
  if (value is int) {
    return value;
  }
  return int.tryParse(value.toString());
}

double _parseDouble(dynamic value) {
  if (value is num) {
    return value.toDouble();
  }
  return double.tryParse(value?.toString() ?? '') ?? 0;
}

String _voucherLabel(String type) {
  switch (type) {
    case 'payment':
      return 'Payment';
    case 'receipt':
      return 'Receipt';
    case 'journal':
    case 'adjustment':
      return 'Adjustment';
    case 'income':
      return 'Sales';
    case 'expense':
      return 'Purchase';
    default:
      return _titleCase(type);
  }
}

String _titleCase(String value) {
  if (value.isEmpty) {
    return value;
  }
  return value
      .split('_')
      .where((part) => part.isNotEmpty)
      .map((part) => '${part[0].toUpperCase()}${part.substring(1)}')
      .join(' ');
}
