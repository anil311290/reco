import '../../../core/config/api_endpoints.dart';
import '../../models/masters/master_entities.dart';
import '../base/offline_first_repository.dart';

class LocationsRepository extends OfflineFirstRepository {
  LocationsRepository(
    super.apiClient,
    super.databaseService,
    super.networkMonitorService,
    super.syncService,
  );

  static const String _module = 'locations';

  Future<List<LookupOption>> getStates() async {
    final cacheKey = buildCacheKey(ApiEndpoints.states);
    final cached = await getCachedObject(cacheKey);
    if (cached != null && cached['data'] is List) {
      _refreshStatesSilently();
      return _extractLookupList(cached['data']);
    }

    return _refreshStates(cacheKey);
  }

  Future<List<LookupOption>> getCities(int stateId) async {
    final endpoint = ApiEndpoints.stateCities(stateId);
    final cacheKey = buildCacheKey(endpoint);
    final cached = await getCachedObject(cacheKey);
    if (cached != null && cached['data'] is List) {
      _refreshCitiesSilently(stateId, cacheKey);
      return _extractLookupList(cached['data']);
    }

    return _refreshCities(stateId, cacheKey);
  }

  Future<List<LookupOption>> _refreshStates(String cacheKey) async {
    final response = await apiClient.get<List<dynamic>>(ApiEndpoints.states);
    final payload = <String, dynamic>{'data': response.data ?? <dynamic>[]};
    await saveCachedObject(
      cacheKey: cacheKey,
      module: _module,
      endpoint: ApiEndpoints.states,
      response: payload,
    );
    return _extractLookupList(payload['data']);
  }

  Future<List<LookupOption>> _refreshCities(
    int stateId,
    String cacheKey,
  ) async {
    final endpoint = ApiEndpoints.stateCities(stateId);
    final response = await apiClient.get<List<dynamic>>(endpoint);
    final payload = <String, dynamic>{'data': response.data ?? <dynamic>[]};
    await saveCachedObject(
      cacheKey: cacheKey,
      module: _module,
      endpoint: endpoint,
      response: payload,
    );
    return _extractLookupList(payload['data']);
  }

  void _refreshStatesSilently() {
    if (networkMonitorService.hasInternetNow() case final future) {
      future.then((online) {
        if (online) {
          _refreshStates(buildCacheKey(ApiEndpoints.states));
        }
      });
    }
  }

  void _refreshCitiesSilently(int stateId, String cacheKey) {
    if (networkMonitorService.hasInternetNow() case final future) {
      future.then((online) {
        if (online) {
          _refreshCities(stateId, cacheKey);
        }
      });
    }
  }

  List<LookupOption> _extractLookupList(dynamic data) {
    if (data is! List) {
      return <LookupOption>[];
    }
    return data
        .whereType<Map>()
        .map((item) => LookupOption.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }
}
