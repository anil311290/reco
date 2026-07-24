class DbConstants {
  DbConstants._();

  static const String databaseName = 'reco_flutter.db';
  static const int databaseVersion = 1;

  static const String apiCacheTable = 'api_cache';
  static const String offlineRecordsTable = 'offline_records';
  static const String syncQueueTable = 'sync_queue';
}
