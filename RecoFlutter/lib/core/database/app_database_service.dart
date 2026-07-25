import 'dart:convert';

import 'package:path/path.dart' as path;
import 'package:path_provider/path_provider.dart';
import 'package:sqflite/sqflite.dart';
import 'package:uuid/uuid.dart';

import '../config/db_constants.dart';
import '../config/sync_constants.dart';

class AppDatabaseService {
  AppDatabaseService();

  Database? _database;
  final Uuid _uuid = const Uuid();

  Future<AppDatabaseService> init() async {
    if (_database != null) {
      return this;
    }

    final supportDirectory = await getApplicationSupportDirectory();
    final dbPath = path.join(supportDirectory.path, DbConstants.databaseName);

    _database = await openDatabase(
      dbPath,
      version: DbConstants.databaseVersion,
      onConfigure: (db) async {
        await db.execute('PRAGMA foreign_keys = ON');
      },
      onCreate: (db, version) async {
        await _createTables(db);
      },
    );

    return this;
  }

  Database get database {
    final db = _database;
    if (db == null) {
      throw StateError('Database has not been initialized.');
    }
    return db;
  }

  Future<void> _createTables(Database db) async {
    await db.execute('''
      CREATE TABLE ${DbConstants.apiCacheTable} (
        cache_key TEXT PRIMARY KEY,
        module TEXT,
        endpoint TEXT NOT NULL,
        query_params_json TEXT,
        response_json TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        last_synced_at TEXT
      )
    ''');

    await db.execute('''
      CREATE TABLE ${DbConstants.offlineRecordsTable} (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        local_id TEXT NOT NULL UNIQUE,
        module TEXT NOT NULL,
        server_id TEXT,
        payload_json TEXT NOT NULL,
        sync_status TEXT NOT NULL,
        sync_action TEXT NOT NULL DEFAULT '${SyncAction.none}',
        is_dirty INTEGER NOT NULL DEFAULT 0,
        deleted_at TEXT,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        last_synced_at TEXT
      )
    ''');

    await db.execute('''
      CREATE UNIQUE INDEX offline_records_module_server_id_unique
      ON ${DbConstants.offlineRecordsTable}(module, server_id)
      WHERE server_id IS NOT NULL
    ''');

    await db.execute('''
      CREATE TABLE ${DbConstants.syncQueueTable} (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        queue_id TEXT NOT NULL UNIQUE,
        module TEXT NOT NULL,
        record_local_id TEXT,
        endpoint TEXT NOT NULL,
        method TEXT NOT NULL,
        payload_json TEXT NOT NULL,
        query_params_json TEXT,
        sync_status TEXT NOT NULL,
        retry_count INTEGER NOT NULL DEFAULT 0,
        last_error TEXT,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        last_attempt_at TEXT
      )
    ''');
  }

  Future<Map<String, dynamic>?> getCachedResponse(String cacheKey) async {
    final rows = await database.query(
      DbConstants.apiCacheTable,
      where: 'cache_key = ?',
      whereArgs: <Object?>[cacheKey],
      limit: 1,
    );

    if (rows.isEmpty) {
      return null;
    }

    return jsonDecode(rows.first['response_json'] as String)
        as Map<String, dynamic>;
  }

  Future<void> saveCachedResponse({
    required String cacheKey,
    required String module,
    required String endpoint,
    required Map<String, dynamic> response,
    Map<String, dynamic>? queryParameters,
  }) async {
    final now = DateTime.now().toIso8601String();
    await database.insert(DbConstants.apiCacheTable, <String, Object?>{
      'cache_key': cacheKey,
      'module': module,
      'endpoint': endpoint,
      'query_params_json': jsonEncode(queryParameters ?? <String, dynamic>{}),
      'response_json': jsonEncode(response),
      'updated_at': now,
      'last_synced_at': now,
    }, conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<void> clearCachedResponses({
    String? module,
    List<String>? endpointPrefixes,
  }) async {
    if ((module == null || module.isEmpty) &&
        (endpointPrefixes == null || endpointPrefixes.isEmpty)) {
      return;
    }

    final clauses = <String>[];
    final args = <Object?>[];

    if (module != null && module.isNotEmpty) {
      clauses.add('module = ?');
      args.add(module);
    }

    if (endpointPrefixes != null && endpointPrefixes.isNotEmpty) {
      final prefixClauses = <String>[];
      for (final prefix in endpointPrefixes.where((item) => item.isNotEmpty)) {
        prefixClauses.add('endpoint LIKE ?');
        args.add('$prefix%');
      }
      if (prefixClauses.isNotEmpty) {
        clauses.add('(${prefixClauses.join(' OR ')})');
      }
    }

    if (clauses.isEmpty) {
      return;
    }

    await database.delete(
      DbConstants.apiCacheTable,
      where: clauses.join(' AND '),
      whereArgs: args,
    );
  }

  Future<List<Map<String, dynamic>>> getModuleRecords(String module) async {
    final rows = await database.query(
      DbConstants.offlineRecordsTable,
      where: 'module = ? AND deleted_at IS NULL',
      whereArgs: <Object?>[module],
      orderBy: 'updated_at DESC',
    );

    return rows.map(_mapOfflineRecordRow).toList();
  }

  Future<String> saveLocalRecord({
    required String module,
    required Map<String, dynamic> payload,
    required String syncAction,
    String? localId,
    String? serverId,
    String syncStatus = SyncStatus.pending,
    bool isDirty = true,
    bool markDeleted = false,
  }) async {
    final String resolvedLocalId = localId ?? _uuid.v7();
    final String now = DateTime.now().toIso8601String();

    await database.transaction((txn) async {
      final existing = await txn.query(
        DbConstants.offlineRecordsTable,
        where: 'local_id = ?',
        whereArgs: <Object?>[resolvedLocalId],
        limit: 1,
      );

      final data = <String, Object?>{
        'local_id': resolvedLocalId,
        'module': module,
        'server_id': serverId,
        'payload_json': jsonEncode(payload),
        'sync_status': syncStatus,
        'sync_action': syncAction,
        'is_dirty': isDirty ? 1 : 0,
        'deleted_at': markDeleted ? now : null,
        'updated_at': now,
        'last_synced_at': isDirty ? null : now,
      };

      if (existing.isEmpty) {
        data['created_at'] = now;
        await txn.insert(DbConstants.offlineRecordsTable, data);
      } else {
        await txn.update(
          DbConstants.offlineRecordsTable,
          data,
          where: 'local_id = ?',
          whereArgs: <Object?>[resolvedLocalId],
        );
      }
    });

    return resolvedLocalId;
  }

  Future<void> mergeRemoteRecords({
    required String module,
    required List<Map<String, dynamic>> records,
    String serverIdKey = 'id',
  }) async {
    await database.transaction((txn) async {
      final existingRows = await txn.query(
        DbConstants.offlineRecordsTable,
        where: 'module = ?',
        whereArgs: <Object?>[module],
      );

      final existingByServerId = <String, Map<String, Object?>>{};
      final dirtyServerIds = <String>{};

      for (final row in existingRows) {
        final serverId = row['server_id']?.toString();
        if (serverId != null && serverId.isNotEmpty) {
          existingByServerId[serverId] = row;
          if ((row['is_dirty'] as int? ?? 0) == 1) {
            dirtyServerIds.add(serverId);
          }
        }
      }

      final batch = txn.batch();
      final now = DateTime.now().toIso8601String();

      for (final record in records) {
        final serverId = record[serverIdKey]?.toString();
        if (serverId == null ||
            serverId.isEmpty ||
            dirtyServerIds.contains(serverId)) {
          continue;
        }

        final existing = existingByServerId[serverId];
        final localId =
            existing?['local_id']?.toString() ?? 'remote-$module-$serverId';

        final payload = <String, Object?>{
          'local_id': localId,
          'module': module,
          'server_id': serverId,
          'payload_json': jsonEncode(record),
          'sync_status': SyncStatus.synced,
          'sync_action': SyncAction.none,
          'is_dirty': 0,
          'deleted_at': null,
          'updated_at': now,
          'last_synced_at': now,
        };

        if (existing == null) {
          payload['created_at'] = now;
          batch.insert(DbConstants.offlineRecordsTable, payload);
        } else {
          batch.update(
            DbConstants.offlineRecordsTable,
            payload,
            where: 'local_id = ?',
            whereArgs: <Object?>[localId],
          );
        }
      }

      await batch.commit(noResult: true);
    });
  }

  Future<void> markRecordSynced({
    required String localId,
    required String module,
    required Map<String, dynamic> payload,
    String? serverId,
  }) async {
    final now = DateTime.now().toIso8601String();
    final existing = await database.query(
      DbConstants.offlineRecordsTable,
      columns: <String>['payload_json'],
      where: 'local_id = ?',
      whereArgs: <Object?>[localId],
      limit: 1,
    );

    Map<String, dynamic> mergedPayload = payload;
    if (existing.isNotEmpty) {
      final existingJson = existing.first['payload_json']?.toString();
      if (existingJson != null && existingJson.isNotEmpty) {
        final decoded = jsonDecode(existingJson);
        if (decoded is Map<String, dynamic>) {
          mergedPayload = <String, dynamic>{
            ...decoded,
            ...payload,
          };
        }
      }
    }

    await database.update(
      DbConstants.offlineRecordsTable,
      <String, Object?>{
        'module': module,
        'server_id': serverId,
        'payload_json': jsonEncode(mergedPayload),
        'sync_status': SyncStatus.synced,
        'sync_action': SyncAction.none,
        'is_dirty': 0,
        'deleted_at': null,
        'updated_at': now,
        'last_synced_at': now,
      },
      where: 'local_id = ?',
      whereArgs: <Object?>[localId],
    );
  }

  Future<void> removeRecordAfterSync(String localId) async {
    await database.delete(
      DbConstants.offlineRecordsTable,
      where: 'local_id = ?',
      whereArgs: <Object?>[localId],
    );
  }

  Future<void> queueMutation({
    required String module,
    required String endpoint,
    required String method,
    required Map<String, dynamic> payload,
    String? recordLocalId,
    Map<String, dynamic>? queryParameters,
  }) async {
    final now = DateTime.now().toIso8601String();
    await database.insert(DbConstants.syncQueueTable, <String, Object?>{
      'queue_id': _uuid.v7(),
      'module': module,
      'record_local_id': recordLocalId,
      'endpoint': endpoint,
      'method': method,
      'payload_json': jsonEncode(payload),
      'query_params_json': jsonEncode(queryParameters ?? <String, dynamic>{}),
      'sync_status': SyncStatus.pending,
      'retry_count': 0,
      'created_at': now,
      'updated_at': now,
    }, conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<List<Map<String, Object?>>> getPendingSyncQueue() async {
    return database.query(
      DbConstants.syncQueueTable,
      where: 'sync_status IN (?, ?)',
      whereArgs: <Object?>[SyncStatus.pending, SyncStatus.failed],
      orderBy: 'created_at ASC',
    );
  }

  Future<void> markQueueSyncing(String queueId) async {
    final now = DateTime.now().toIso8601String();
    await database.update(
      DbConstants.syncQueueTable,
      <String, Object?>{
        'sync_status': SyncStatus.syncing,
        'updated_at': now,
        'last_attempt_at': now,
      },
      where: 'queue_id = ?',
      whereArgs: <Object?>[queueId],
    );
  }

  Future<void> markQueueSynced(String queueId) async {
    await database.delete(
      DbConstants.syncQueueTable,
      where: 'queue_id = ?',
      whereArgs: <Object?>[queueId],
    );
  }

  Future<void> markQueueFailed(String queueId, String error) async {
    final now = DateTime.now().toIso8601String();
    await database.rawUpdate(
      '''
      UPDATE ${DbConstants.syncQueueTable}
      SET sync_status = ?, retry_count = retry_count + 1, last_error = ?, updated_at = ?, last_attempt_at = ?
      WHERE queue_id = ?
      ''',
      <Object?>[SyncStatus.failed, error, now, now, queueId],
    );
  }

  Map<String, dynamic> _mapOfflineRecordRow(Map<String, Object?> row) {
    return <String, dynamic>{
      'local_id': row['local_id'],
      'module': row['module'],
      'server_id': row['server_id'],
      'sync_status': row['sync_status'],
      'sync_action': row['sync_action'],
      'is_dirty': (row['is_dirty'] as int? ?? 0) == 1,
      'deleted_at': row['deleted_at'],
      'created_at': row['created_at'],
      'updated_at': row['updated_at'],
      'last_synced_at': row['last_synced_at'],
      'payload': jsonDecode(row['payload_json'] as String),
    };
  }
}
