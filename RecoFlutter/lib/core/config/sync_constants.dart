class SyncStatus {
  SyncStatus._();

  static const String pending = 'pending';
  static const String syncing = 'syncing';
  static const String synced = 'synced';
  static const String failed = 'failed';
}

class SyncAction {
  SyncAction._();

  static const String none = 'none';
  static const String create = 'create';
  static const String update = 'update';
  static const String delete = 'delete';
}
