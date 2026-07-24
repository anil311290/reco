import 'dart:async';

import '../../../core/config/api_endpoints.dart';
import '../../models/masters/master_entities.dart';
import '../base/offline_first_repository.dart';

class TaxRatesRepository extends OfflineFirstRepository {
  TaxRatesRepository(
    super.apiClient,
    super.databaseService,
    super.networkMonitorService,
    super.syncService,
  );

  static const String _module = 'tax_rates';

  Future<List<TaxRateEntity>> getTaxRates() async {
    final local = await getLocalModuleRecords(_module);
    final entities = local.map(TaxRateEntity.fromRecord).toList();

    if (entities.isNotEmpty) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(refreshTaxRates());
      }
      return entities;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshTaxRates();
    }

    return entities;
  }

  Future<List<TaxRateEntity>> refreshTaxRates() async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.taxRates,
    );
    final records = _extractList(response.data?['data']);
    await mergeRemoteRecords(module: _module, records: records);
    return records.map(TaxRateEntity.fromRecord).toList();
  }

  Future<String> create(TaxRateEntity entity) {
    return queueCreate(
      module: _module,
      endpoint: ApiEndpoints.taxRates,
      payload: entity.toPayload(),
    );
  }

  Future<String> update(TaxRateEntity entity) {
    final serverId = entity.id?.toString();
    return queueUpdate(
      module: _module,
      endpoint: '${ApiEndpoints.taxRates}/$serverId',
      payload: entity.toPayload(),
      localId: entity.localId ?? 'remote-$_module-$serverId',
      serverId: serverId,
    );
  }

  Future<void> delete(TaxRateEntity entity) {
    final serverId = entity.id?.toString();
    return queueDelete(
      module: _module,
      endpoint: '${ApiEndpoints.taxRates}/$serverId',
      payload: entity.toPayload(),
      localId: entity.localId ?? 'remote-$_module-$serverId',
      serverId: serverId,
    );
  }

  Future<void> toggleStatus(TaxRateEntity entity, bool isActive) async {
    final serverId = entity.id?.toString();
    if (serverId == null) {
      return;
    }
    final localId = entity.localId ?? 'remote-$_module-$serverId';
    await databaseService.saveLocalRecord(
      module: _module,
      payload: <String, dynamic>{
        ...entity.toPayload(),
        'id': entity.id,
        'status': isActive ? 'active' : 'inactive',
      },
      syncAction: 'update',
      localId: localId,
      serverId: serverId,
    );
    await databaseService.queueMutation(
      module: _module,
      endpoint: '${ApiEndpoints.taxRates}/$serverId/status',
      method: 'PATCH',
      payload: const <String, dynamic>{},
      recordLocalId: localId,
    );
    if (await networkMonitorService.hasInternetNow()) {
      unawaited(syncService.syncPendingMutations(showSuccessMessage: false));
    }
  }

  Future<List<LookupOption>> getDropdownOptions() async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.taxRatesDropdown,
    );
    final records = _extractList(response.data?['data']);
    return records
        .map(
          (record) => LookupOption(
            id: int.tryParse(record['id'].toString()) ?? 0,
            label: (record['tax_name'] ?? record['name'] ?? '').toString(),
            code: (record['tax_code'] ?? record['code'] ?? '').toString(),
          ),
        )
        .toList();
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
