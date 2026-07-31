class LookupOption {
  const LookupOption({
    required this.id,
    required this.label,
    this.code,
    this.rawId,
    this.group,
    this.kind,
    this.transactionMode,
    this.availableBalance,
  });

  final int id;
  final String label;
  final String? code;
  final String? rawId;
  final String? group;
  final String? kind;
  final String? transactionMode;
  final double? availableBalance;

  String get valueKey {
    final raw = rawId?.trim();
    if (raw != null && raw.isNotEmpty) {
      return raw;
    }
    return id.toString();
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) {
      return true;
    }
    return other is LookupOption &&
        other.valueKey == valueKey &&
        other.kind == kind &&
        other.label == label;
  }

  @override
  int get hashCode => Object.hash(valueKey, kind, label);

  factory LookupOption.fromJson(Map<String, dynamic> json) {
    final rawId = json['id']?.toString();
    return LookupOption(
      id: _parseInt(json['id']),
      label: (json['name'] ?? json['label'] ?? '').toString(),
      code: json['code']?.toString(),
      rawId: rawId,
      group: json['group']?.toString(),
      kind: json['kind']?.toString(),
      transactionMode: json['transaction_mode']?.toString(),
      availableBalance: _parseNullableDouble(json['available_balance']),
    );
  }
}

class PartyEntity {
  const PartyEntity({
    this.id,
    this.localId,
    this.syncStatus = 'synced',
    this.isDirty = false,
    this.partyCode = '',
    this.name = '',
    this.type = 'debtor',
    this.mobile = '',
    this.email = '',
    this.address = '',
    this.stateId,
    this.state = '',
    this.cityId,
    this.city = '',
    this.postalCode = '',
    this.gstin = '',
    this.panNumber = '',
    this.openingBalance = 0,
    this.openingBalanceType = 'debit',
    this.openingDate = '',
    this.remarks = '',
    this.isActive = true,
    this.typeLocked = false,
  });

  final int? id;
  final String? localId;
  final String syncStatus;
  final bool isDirty;
  final String partyCode;
  final String name;
  final String type;
  final String mobile;
  final String email;
  final String address;
  final int? stateId;
  final String state;
  final int? cityId;
  final String city;
  final String postalCode;
  final String gstin;
  final String panNumber;
  final double openingBalance;
  final String openingBalanceType;
  final String openingDate;
  final String remarks;
  final bool isActive;
  final bool typeLocked;

  factory PartyEntity.fromRecord(Map<String, dynamic> record) {
    final payload = _recordPayload(record);
    return PartyEntity(
      id: _tryParseInt(payload['id']),
      localId: record['local_id']?.toString(),
      syncStatus: record['sync_status']?.toString() ?? 'synced',
      isDirty: record['is_dirty'] == true,
      partyCode: (payload['party_code'] ?? '').toString(),
      name: (payload['name'] ?? '').toString(),
      type: (payload['type'] ?? 'debtor').toString(),
      mobile: (payload['mobile'] ?? '').toString(),
      email: (payload['email'] ?? '').toString(),
      address: (payload['address'] ?? '').toString(),
      stateId: _tryParseInt(payload['state_id']),
      state: (payload['state'] ?? '').toString(),
      cityId: _tryParseInt(payload['city_id']),
      city: (payload['city'] ?? '').toString(),
      postalCode: (payload['postal_code'] ?? '').toString(),
      gstin: (payload['gstin'] ?? payload['gst_number'] ?? '').toString(),
      panNumber: (payload['pan_number'] ?? '').toString(),
      openingBalance: _parseDouble(payload['opening_balance']),
      openingBalanceType: (payload['opening_balance_type'] ?? 'debit')
          .toString(),
      openingDate: (payload['opening_date'] ?? '').toString(),
      remarks: (payload['remarks'] ?? '').toString(),
      isActive: _parseBool(payload['is_active'], fallback: true),
      typeLocked: _parseBool(payload['type_locked'], fallback: false),
    );
  }

  Map<String, dynamic> toPayload() {
    return <String, dynamic>{
      'party_code': partyCode.trim().isEmpty ? null : partyCode.trim(),
      'name': name.trim(),
      'type': type,
      'mobile': mobile.trim().isEmpty ? null : mobile.trim(),
      'email': email.trim().isEmpty ? null : email.trim(),
      'address': address.trim(),
      'state_id': stateId,
      'city_id': cityId,
      'postal_code': postalCode.trim(),
      'gstin': gstin.trim().isEmpty ? null : gstin.trim(),
      'pan_number': panNumber.trim().isEmpty ? null : panNumber.trim(),
      'opening_balance': openingBalance,
      'opening_balance_type': openingBalanceType,
      'opening_date': openingDate.trim().isEmpty ? null : openingDate.trim(),
      'remarks': remarks.trim().isEmpty ? null : remarks.trim(),
      'is_active': isActive,
      if (id != null) 'id': id,
      if (state.isNotEmpty) 'state': state,
      if (city.isNotEmpty) 'city': city,
    };
  }
}

class AccountEntity {
  const AccountEntity({
    this.id,
    this.localId,
    this.syncStatus = 'synced',
    this.isDirty = false,
    this.accountCode = '',
    this.accountName = '',
    this.accountType = 'asset',
    this.transactionMode = '',
    this.openingBalance = 0,
    this.balanceType = 'debit',
    this.openingDate = '',
    this.remarks = '',
    this.isActive = true,
    this.isSystem = false,
    this.isInUse = false,
    this.entrySource = '',
  });

  final int? id;
  final String? localId;
  final String syncStatus;
  final bool isDirty;
  final String accountCode;
  final String accountName;
  final String accountType;
  final String transactionMode;
  final double openingBalance;
  final String balanceType;
  final String openingDate;
  final String remarks;
  final bool isActive;
  final bool isSystem;
  final bool isInUse;
  final String entrySource;

  factory AccountEntity.fromRecord(Map<String, dynamic> record) {
    final payload = _recordPayload(record);
    return AccountEntity(
      id: _tryParseInt(payload['id']),
      localId: record['local_id']?.toString(),
      syncStatus: record['sync_status']?.toString() ?? 'synced',
      isDirty: record['is_dirty'] == true,
      accountCode: (payload['account_code'] ?? '').toString(),
      accountName: (payload['account_name'] ?? '').toString(),
      accountType: (payload['account_type'] ?? 'asset').toString(),
      transactionMode: (payload['transaction_mode'] ?? '').toString(),
      openingBalance: _parseDouble(payload['opening_balance']),
      balanceType: (payload['balance_type'] ?? 'debit').toString(),
      openingDate: (payload['opening_date'] ?? '').toString(),
      remarks: (payload['remarks'] ?? '').toString(),
      isActive: _parseBool(payload['is_active'], fallback: true),
      isSystem: _parseBool(payload['is_system'], fallback: false),
      isInUse: _parseBool(payload['is_in_use'], fallback: false),
      entrySource: (payload['entry_source'] ?? '').toString(),
    );
  }

  Map<String, dynamic> toPayload() {
    return <String, dynamic>{
      'account_name': accountName.trim(),
      'account_type': accountType,
      'transaction_mode': transactionMode.trim().isEmpty
          ? null
          : transactionMode.trim(),
      'opening_balance': openingBalance,
      'balance_type': balanceType,
      'opening_date': openingDate.trim().isEmpty ? null : openingDate.trim(),
      'remarks': remarks.trim().isEmpty ? null : remarks.trim(),
      'is_active': isActive,
      if (id != null) 'id': id,
      if (accountCode.isNotEmpty) 'account_code': accountCode,
    };
  }
}

class ItemCategoryEntity {
  const ItemCategoryEntity({
    this.id,
    this.localId,
    this.syncStatus = 'synced',
    this.isDirty = false,
    this.name = '',
    this.description = '',
    this.sortOrder = 0,
    this.isActive = true,
  });

  final int? id;
  final String? localId;
  final String syncStatus;
  final bool isDirty;
  final String name;
  final String description;
  final int sortOrder;
  final bool isActive;

  factory ItemCategoryEntity.fromRecord(Map<String, dynamic> record) {
    final payload = _recordPayload(record);
    return ItemCategoryEntity(
      id: _tryParseInt(payload['id']),
      localId: record['local_id']?.toString(),
      syncStatus: record['sync_status']?.toString() ?? 'synced',
      isDirty: record['is_dirty'] == true,
      name: (payload['name'] ?? '').toString(),
      description: (payload['description'] ?? '').toString(),
      sortOrder: _parseInt(payload['sort_order']),
      isActive: _parseBool(payload['is_active'], fallback: true),
    );
  }

  Map<String, dynamic> toPayload() {
    return <String, dynamic>{
      'name': name.trim(),
      'description': description.trim().isEmpty ? null : description.trim(),
      'sort_order': sortOrder,
      'is_active': isActive,
      if (id != null) 'id': id,
    };
  }
}

class TaxRateEntity {
  const TaxRateEntity({
    this.id,
    this.localId,
    this.syncStatus = 'synced',
    this.isDirty = false,
    this.taxCode = '',
    this.taxName = '',
    this.taxRate = 0,
    this.taxType = 'addition',
    this.taxCategory = 'GST',
    this.notes = '',
    this.status = 'active',
  });

  final int? id;
  final String? localId;
  final String syncStatus;
  final bool isDirty;
  final String taxCode;
  final String taxName;
  final double taxRate;
  final String taxType;
  final String taxCategory;
  final String notes;
  final String status;

  bool get isActive => status == 'active';

  factory TaxRateEntity.fromRecord(Map<String, dynamic> record) {
    final payload = _recordPayload(record);
    return TaxRateEntity(
      id: _tryParseInt(payload['id']),
      localId: record['local_id']?.toString(),
      syncStatus: record['sync_status']?.toString() ?? 'synced',
      isDirty: record['is_dirty'] == true,
      taxCode: (payload['tax_code'] ?? payload['code'] ?? '').toString(),
      taxName: (payload['tax_name'] ?? payload['name'] ?? '').toString(),
      taxRate: _parseDouble(payload['tax_rate'] ?? payload['rate']),
      taxType:
          (payload['tax_type'] ?? payload['calculation_type'] ?? 'addition')
              .toString(),
      taxCategory: (payload['tax_category'] ?? payload['category'] ?? 'GST')
          .toString(),
      notes: (payload['notes'] ?? '').toString(),
      status: (payload['status'] ?? 'active').toString(),
    );
  }

  Map<String, dynamic> toPayload() {
    return <String, dynamic>{
      'tax_code': taxCode.trim().isEmpty ? null : taxCode.trim(),
      'tax_name': taxName.trim(),
      'tax_rate': taxRate,
      'tax_type': taxType,
      'tax_category': taxCategory,
      'notes': notes.trim().isEmpty ? null : notes.trim(),
      'status': status,
      if (id != null) 'id': id,
    };
  }
}

class ItemEntity {
  const ItemEntity({
    this.id,
    this.localId,
    this.syncStatus = 'synced',
    this.isDirty = false,
    this.itemCode = '',
    this.name = '',
    this.hsnSacCode = '',
    this.type = 'goods',
    this.categoryId,
    this.categoryName = '',
    this.taxRateId,
    this.taxLabel = '',
    this.incomeAccountId,
    this.expenseAccountId,
    this.purchasePrice = 0,
    this.sellingPrice = 0,
    this.unit = '',
    this.description = '',
    this.barcode = '',
    this.openingStock = 0,
    this.currentStock = 0,
    this.reorderLevel = 0,
    this.isStockable = true,
    this.isActive = true,
  });

  final int? id;
  final String? localId;
  final String syncStatus;
  final bool isDirty;
  final String itemCode;
  final String name;
  final String hsnSacCode;
  final String type;
  final int? categoryId;
  final String categoryName;
  final int? taxRateId;
  final String taxLabel;
  final int? incomeAccountId;
  final int? expenseAccountId;
  final double purchasePrice;
  final double sellingPrice;
  final String unit;
  final String description;
  final String barcode;
  final double openingStock;
  final double currentStock;
  final double reorderLevel;
  final bool isStockable;
  final bool isActive;

  factory ItemEntity.fromRecord(Map<String, dynamic> record) {
    final payload = _recordPayload(record);
    final tax = payload['tax_rate'];
    return ItemEntity(
      id: _tryParseInt(payload['id']),
      localId: record['local_id']?.toString(),
      syncStatus: record['sync_status']?.toString() ?? 'synced',
      isDirty: record['is_dirty'] == true,
      itemCode: (payload['item_code'] ?? '').toString(),
      name: (payload['name'] ?? '').toString(),
      hsnSacCode: (payload['hsn_sac_code'] ?? '').toString(),
      type: (payload['type'] ?? 'goods').toString(),
      categoryId: _tryParseInt(payload['category_id']),
      categoryName: (payload['category_name'] ?? '').toString(),
      taxRateId: _tryParseInt(payload['tax_rate_id']),
      taxLabel: tax is Map<String, dynamic>
          ? '${tax['tax_name'] ?? tax['name'] ?? ''} ${tax['tax_rate'] ?? tax['rate'] ?? ''}'
          : '',
      incomeAccountId: _tryParseInt(payload['income_account_id']),
      expenseAccountId: _tryParseInt(payload['expense_account_id']),
      purchasePrice: _parseDouble(payload['purchase_price']),
      sellingPrice: _parseDouble(payload['selling_price']),
      unit: (payload['unit'] ?? '').toString(),
      description: (payload['description'] ?? '').toString(),
      barcode: (payload['barcode'] ?? '').toString(),
      openingStock: _parseDouble(payload['opening_stock']),
      currentStock: _parseDouble(payload['current_stock']),
      reorderLevel: _parseDouble(payload['reorder_level']),
      isStockable: _parseBool(payload['is_stockable'], fallback: true),
      isActive: _parseBool(payload['is_active'], fallback: true),
    );
  }

  Map<String, dynamic> toPayload() {
    return <String, dynamic>{
      'item_code': itemCode.trim(),
      'name': name.trim(),
      'hsn_sac_code': hsnSacCode.trim().isEmpty ? null : hsnSacCode.trim(),
      'type': type,
      'category_id': categoryId,
      'tax_rate_id': taxRateId,
      'income_account_id': incomeAccountId,
      'expense_account_id': expenseAccountId,
      'purchase_price': purchasePrice,
      'selling_price': sellingPrice,
      'unit': unit.trim().isEmpty ? null : unit.trim(),
      'description': description.trim().isEmpty ? null : description.trim(),
      'barcode': barcode.trim().isEmpty ? null : barcode.trim(),
      'opening_stock': openingStock,
      'is_stockable': isStockable,
      'is_active': isActive,
      if (id != null) 'id': id,
    };
  }
}

class FinancialYearEntity {
  const FinancialYearEntity({
    this.id,
    this.localId,
    this.syncStatus = 'synced',
    this.isDirty = false,
    this.name = '',
    this.startDate = '',
    this.endDate = '',
    this.isCurrent = false,
    this.isClosed = false,
    this.closedAt,
  });

  final int? id;
  final String? localId;
  final String syncStatus;
  final bool isDirty;
  final String name;
  final String startDate;
  final String endDate;
  final bool isCurrent;
  final bool isClosed;
  final String? closedAt;

  String get statusLabel {
    if (isClosed) return 'Closed';
    if (isCurrent) return 'Current';
    return 'Open';
  }

  factory FinancialYearEntity.fromRecord(Map<String, dynamic> record) {
    final payload = _recordPayload(record);
    return FinancialYearEntity(
      id: _tryParseInt(payload['id']),
      localId: record['local_id']?.toString(),
      syncStatus: record['sync_status']?.toString() ?? 'synced',
      isDirty: record['is_dirty'] == true,
      name: (payload['name'] ?? '').toString(),
      startDate: (payload['start_date'] ?? '').toString(),
      endDate: (payload['end_date'] ?? '').toString(),
      isCurrent: _parseBool(payload['is_current'], fallback: false),
      isClosed: _parseBool(payload['is_closed'], fallback: false),
      closedAt: payload['closed_at']?.toString(),
    );
  }

  Map<String, dynamic> toPayload() {
    return <String, dynamic>{
      'name': name.trim(),
      'start_date': startDate.trim(),
      'end_date': endDate.trim(),
      if (id != null) 'id': id,
    };
  }
}

Map<String, dynamic> _recordPayload(Map<String, dynamic> record) {
  final payload = record['payload'];
  if (payload is Map<String, dynamic>) {
    return payload;
  }
  return record;
}

int _parseInt(dynamic value) => _tryParseInt(value) ?? 0;

int? _tryParseInt(dynamic value) {
  if (value == null) {
    return null;
  }
  if (value is int) {
    return value;
  }
  if (value is num) {
    return value.toInt();
  }
  return int.tryParse(value.toString());
}

double _parseDouble(dynamic value) {
  if (value == null) {
    return 0;
  }
  if (value is double) {
    return value;
  }
  if (value is num) {
    return value.toDouble();
  }
  return double.tryParse(value.toString()) ?? 0;
}

double? _parseNullableDouble(dynamic value) {
  if (value == null) {
    return null;
  }
  if (value is double) {
    return value;
  }
  if (value is num) {
    return value.toDouble();
  }
  return double.tryParse(value.toString());
}

bool _parseBool(dynamic value, {required bool fallback}) {
  if (value == null) {
    return fallback;
  }
  if (value is bool) {
    return value;
  }
  if (value is num) {
    return value == 1;
  }
  final normalized = value.toString().toLowerCase();
  if (normalized == 'true' || normalized == 'active' || normalized == '1') {
    return true;
  }
  if (normalized == 'false' || normalized == 'inactive' || normalized == '0') {
    return false;
  }
  return fallback;
}
