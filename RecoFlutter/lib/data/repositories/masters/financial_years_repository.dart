import 'dart:async';

import '../../../core/config/api_endpoints.dart';
import '../../models/masters/master_entities.dart';
import '../base/offline_first_repository.dart';

class FinancialYearsRepository extends OfflineFirstRepository {
  FinancialYearsRepository(
    super.apiClient,
    super.databaseService,
    super.networkMonitorService,
    super.syncService,
  );

  static const String _module = 'financial_years';

  Future<List<FinancialYearEntity>> getFinancialYears() async {
    final local = await getLocalModuleRecords(_module);
    final entities = local.map(FinancialYearEntity.fromRecord).toList()
      ..sort(_sortFinancialYears);

    if (entities.isNotEmpty) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(refreshFinancialYears());
      }
      return entities;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshFinancialYears();
    }

    return entities;
  }

  Future<List<FinancialYearEntity>> refreshFinancialYears() async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.financialYears,
    );
    final records = _extractList(response.data?['data']);
    await mergeRemoteRecords(module: _module, records: records);
    return records.map(FinancialYearEntity.fromRecord).toList()
      ..sort(_sortFinancialYears);
  }

  Future<FinancialYearEntity?> getCurrentFinancialYear() async {
    try {
      final response = await apiClient.get<Map<String, dynamic>>(
        ApiEndpoints.currentFinancialYear,
      );
      final data = response.data?['data'];
      if (data is Map<String, dynamic>) {
        return FinancialYearEntity.fromRecord(data);
      }
    } catch (_) {
      // Fallback: find current from local records
      final all = await getFinancialYears();
      for (final fy in all) {
        if (fy.isCurrent) return fy;
      }
    }
    return null;
  }

  Future<String> create(FinancialYearEntity entity) {
    return queueCreate(
      module: _module,
      endpoint: ApiEndpoints.financialYears,
      payload: entity.toPayload(),
    );
  }

  Future<String> update(FinancialYearEntity entity) {
    final serverId = entity.id?.toString();
    return queueUpdate(
      module: _module,
      endpoint: '${ApiEndpoints.financialYears}/$serverId',
      payload: entity.toPayload(),
      localId: entity.localId ?? 'remote-$_module-$serverId',
      serverId: serverId,
    );
  }

  Future<void> delete(FinancialYearEntity entity) {
    final serverId = entity.id?.toString();
    return queueDelete(
      module: _module,
      endpoint: '${ApiEndpoints.financialYears}/$serverId',
      payload: entity.toPayload(),
      localId: entity.localId ?? 'remote-$_module-$serverId',
      serverId: serverId,
    );
  }

  Future<void> setAsCurrent(FinancialYearEntity entity) async {
    final serverId = entity.id?.toString();
    if (serverId == null) return;

    final localId = entity.localId ?? 'remote-$_module-$serverId';
    await databaseService.saveLocalRecord(
      module: _module,
      payload: <String, dynamic>{
        ...entity.toPayload(),
        'id': entity.id,
        'is_current': true,
      },
      syncAction: 'update',
      localId: localId,
      serverId: serverId,
    );
    await databaseService.queueMutation(
      module: _module,
      endpoint: '${ApiEndpoints.financialYears}/$serverId/set-current',
      method: 'PATCH',
      payload: const <String, dynamic>{},
      recordLocalId: localId,
    );
    if (await networkMonitorService.hasInternetNow()) {
      unawaited(syncService.syncPendingMutations(showSuccessMessage: false));
    }
  }

  Future<void> closeYear(FinancialYearEntity entity) async {
    final serverId = entity.id?.toString();
    if (serverId == null) return;

    final localId = entity.localId ?? 'remote-$_module-$serverId';
    await databaseService.saveLocalRecord(
      module: _module,
      payload: <String, dynamic>{
        ...entity.toPayload(),
        'id': entity.id,
        'is_closed': true,
      },
      syncAction: 'update',
      localId: localId,
      serverId: serverId,
    );
    await databaseService.queueMutation(
      module: _module,
      endpoint: '${ApiEndpoints.financialYears}/$serverId/close',
      method: 'PATCH',
      payload: const <String, dynamic>{},
      recordLocalId: localId,
    );
    if (await networkMonitorService.hasInternetNow()) {
      unawaited(syncService.syncPendingMutations(showSuccessMessage: false));
    }
  }

  int _sortFinancialYears(FinancialYearEntity a, FinancialYearEntity b) {
    return b.startDate.compareTo(a.startDate);
  }

  List<Map<String, dynamic>> _extractList(dynamic data) {
    if (data is List) {
      return data
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }
    return <Map<String, dynamic>>[];
  }
}
