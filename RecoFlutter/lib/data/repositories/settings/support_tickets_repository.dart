import 'dart:async';

import 'package:uuid/uuid.dart';

import '../../../core/config/api_endpoints.dart';
import '../../../core/config/sync_constants.dart';
import '../../models/common/paginated_result.dart';
import '../base/offline_first_repository.dart';

class SupportTicketsRepository extends OfflineFirstRepository {
  SupportTicketsRepository(
    super.apiClient,
    super.databaseService,
    super.networkMonitorService,
    super.syncService,
  );

  static const String _module = 'support_tickets';
  static const Uuid _uuid = Uuid();

  Future<List<Map<String, dynamic>>> getTickets({
    String? status,
    int perPage = 50,
  }) async {
    final local = await _getLocalTickets(status: status);

    if (local.isNotEmpty) {
      if (await networkMonitorService.hasInternetNow()) {
        unawaited(refreshTickets(status: status, perPage: perPage));
      }
      return local;
    }

    if (await networkMonitorService.hasInternetNow()) {
      return refreshTickets(status: status, perPage: perPage);
    }

    return local;
  }

  Future<PaginatedResult<Map<String, dynamic>>> getPaginatedTickets({
    String? status,
    int page = 1,
    int perPage = 20,
  }) async {
    final local = await _getLocalTickets(status: status);
    return _slicePage(local, page: page, perPage: perPage);
  }

  Future<List<Map<String, dynamic>>> refreshTickets({
    String? status,
    int perPage = 50,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.supportTickets,
      queryParameters: <String, dynamic>{
        if (status != null && status.isNotEmpty) 'status': status,
        'per_page': perPage,
      },
    );

    final records = _extractList(response.data?['data']);
    await mergeRemoteRecords(module: _module, records: records);
    return records;
  }

  Future<PaginatedResult<Map<String, dynamic>>> refreshPaginatedTickets({
    String? status,
    int page = 1,
    int perPage = 20,
  }) async {
    final response = await apiClient.get<Map<String, dynamic>>(
      ApiEndpoints.supportTickets,
      queryParameters: <String, dynamic>{
        if (status != null && status.isNotEmpty) 'status': status,
        'page': page,
        'per_page': perPage,
      },
    );

    final data = response.data?['data'];
    final records = _extractList(data);
    await mergeRemoteRecords(module: _module, records: records);

    if (data is Map<String, dynamic> && data['data'] is List) {
      return PaginatedResult<Map<String, dynamic>>(
        items: records,
        currentPage: int.tryParse(data['current_page']?.toString() ?? '$page') ?? page,
        lastPage: int.tryParse(data['last_page']?.toString() ?? '$page') ?? page,
        perPage: int.tryParse(data['per_page']?.toString() ?? '$perPage') ?? perPage,
        total: int.tryParse(data['total']?.toString() ?? '${records.length}') ?? records.length,
      );
    }

    return getPaginatedTickets(
      status: status,
      page: page,
      perPage: perPage,
    );
  }

  Future<Map<String, dynamic>?> getTicketDetail({
    String? ticketId,
    String? localId,
  }) async {
    final local = await _findLocalTicket(ticketId: ticketId, localId: localId);
    final serverId = ticketId ?? local?['id']?.toString();

    if (serverId != null &&
        serverId.isNotEmpty &&
        await networkMonitorService.hasInternetNow()) {
      final response = await apiClient.get<Map<String, dynamic>>(
        ApiEndpoints.supportTicketDetail(serverId),
      );
      final ticket = _extractObject(response.data?['data']);
      if (ticket != null) {
        await mergeRemoteRecords(module: _module, records: <Map<String, dynamic>>[
          ticket,
        ]);
        return ticket;
      }
    }

    return local;
  }

  Future<Map<String, dynamic>> createTicket(Map<String, dynamic> payload) async {
    if (await networkMonitorService.hasInternetNow()) {
      final response = await apiClient.post<Map<String, dynamic>>(
        ApiEndpoints.supportTickets,
        data: payload,
      );
      final ticket = _extractObject(response.data?['data']);
      if (ticket != null) {
        await mergeRemoteRecords(module: _module, records: <Map<String, dynamic>>[
          ticket,
        ]);
        return ticket;
      }
    }

    final now = DateTime.now();
    final localId = _uuid.v7();
    final localTicket = <String, dynamic>{
      'local_id': localId,
      'subject': payload['subject'],
      'category': payload['category'] ?? 'general',
      'priority': payload['priority'] ?? 'normal',
      'status': 'open',
      'ticket_number': 'DRAFT-${now.millisecondsSinceEpoch.toString().substring(7)}',
      'created_at': now.toIso8601String(),
      'last_message_at': now.toIso8601String(),
      'messages': <Map<String, dynamic>>[
        <String, dynamic>{
          'local_id': _uuid.v7(),
          'message': payload['message'],
          'created_at': now.toIso8601String(),
          'is_internal': false,
          'is_mine': true,
          'user': <String, dynamic>{'name': 'You'},
        },
      ],
    };

    await databaseService.saveLocalRecord(
      module: _module,
      payload: localTicket,
      syncAction: 'create',
      localId: localId,
      syncStatus: SyncStatus.pending,
      isDirty: true,
    );

    await databaseService.queueMutation(
      module: _module,
      endpoint: ApiEndpoints.supportTickets,
      method: 'POST',
      payload: payload,
      recordLocalId: localId,
    );

    return localTicket;
  }

  Future<Map<String, dynamic>?> replyToTicket({
    required Map<String, dynamic> ticket,
    required String message,
    bool isInternal = false,
  }) async {
    final serverId = ticket['id']?.toString();
    if (serverId == null || serverId.isEmpty) {
      throw StateError(
        'The draft ticket has not synced yet. Please try again when the internet is available.',
      );
    }

    if (await networkMonitorService.hasInternetNow()) {
      await apiClient.post<Map<String, dynamic>>(
        ApiEndpoints.supportTicketReply(serverId),
        data: <String, dynamic>{
          'message': message,
          'is_internal': isInternal,
        },
      );
      return getTicketDetail(ticketId: serverId);
    }

    final updated = _appendLocalMessage(
      ticket: ticket,
      message: message,
      isInternal: isInternal,
    );
    final localId =
        ticket['local_id']?.toString() ?? 'remote-$_module-$serverId';

    await databaseService.saveLocalRecord(
      module: _module,
      payload: updated,
      syncAction: SyncAction.none,
      localId: localId,
      serverId: serverId,
      syncStatus: SyncStatus.pending,
      isDirty: false,
    );

    await databaseService.queueMutation(
      module: _module,
      endpoint: ApiEndpoints.supportTicketReply(serverId),
      method: 'POST',
      payload: <String, dynamic>{
        'message': message,
        'is_internal': isInternal,
      },
    );

    return updated;
  }

  Future<Map<String, dynamic>?> updateTicketStatus({
    required Map<String, dynamic> ticket,
    required String status,
    int? assignedTo,
  }) async {
    final serverId = ticket['id']?.toString();
    if (serverId == null || serverId.isEmpty) {
      return ticket;
    }

    final payload = <String, dynamic>{
      'status': status,
      'assigned_to': assignedTo,
    };

    if (await networkMonitorService.hasInternetNow()) {
      final response = await apiClient.patch<Map<String, dynamic>>(
        ApiEndpoints.supportTicketStatus(serverId),
        data: payload,
      );
      final updated = _extractObject(response.data?['data']);
      if (updated != null) {
        await mergeRemoteRecords(module: _module, records: <Map<String, dynamic>>[
          updated,
        ]);
      }
      return updated;
    }

    final updated = <String, dynamic>{...ticket, 'status': status};
    final localId =
        ticket['local_id']?.toString() ?? 'remote-$_module-$serverId';

    await databaseService.saveLocalRecord(
      module: _module,
      payload: updated,
      syncAction: SyncAction.none,
      localId: localId,
      serverId: serverId,
      syncStatus: SyncStatus.pending,
      isDirty: false,
    );

    await databaseService.queueMutation(
      module: _module,
      endpoint: ApiEndpoints.supportTicketStatus(serverId),
      method: 'PATCH',
      payload: payload,
    );

    return updated;
  }

  Future<List<Map<String, dynamic>>> _getLocalTickets({String? status}) async {
    final records = await getLocalModuleRecords(_module);
    final payloads = records
        .map(
          (record) => <String, dynamic>{
            ...(record['payload'] as Map<String, dynamic>),
            'local_id': record['local_id'],
          },
        )
        .toList();

    if (status == null || status.isEmpty) {
      return payloads;
    }

    return payloads
        .where((item) => item['status']?.toString() == status)
        .toList();
  }

  PaginatedResult<Map<String, dynamic>> _slicePage(
    List<Map<String, dynamic>> items, {
    required int page,
    required int perPage,
  }) {
    if (items.isEmpty) {
      return PaginatedResult<Map<String, dynamic>>(
        items: const <Map<String, dynamic>>[],
        currentPage: 1,
        lastPage: 1,
        perPage: perPage,
        total: 0,
      );
    }
    final safePage = page < 1 ? 1 : page;
    final start = (safePage - 1) * perPage;
    if (start >= items.length) {
      final lastPage = ((items.length + perPage - 1) ~/ perPage).clamp(1, 999999);
      return PaginatedResult<Map<String, dynamic>>(
        items: const <Map<String, dynamic>>[],
        currentPage: safePage,
        lastPage: lastPage,
        perPage: perPage,
        total: items.length,
      );
    }
    final end = (start + perPage) > items.length ? items.length : start + perPage;
    final lastPage = ((items.length + perPage - 1) ~/ perPage).clamp(1, 999999);
    return PaginatedResult<Map<String, dynamic>>(
      items: items.sublist(start, end),
      currentPage: safePage,
      lastPage: lastPage,
      perPage: perPage,
      total: items.length,
    );
  }

  Future<Map<String, dynamic>?> _findLocalTicket({
    String? ticketId,
    String? localId,
  }) async {
    final records = await getLocalModuleRecords(_module);
    for (final record in records) {
      final payload = Map<String, dynamic>.from(
        record['payload'] as Map<String, dynamic>,
      );
      final recordLocalId = record['local_id']?.toString();
      final recordServerId = record['server_id']?.toString() ?? payload['id']?.toString();
      if ((localId != null && localId == recordLocalId) ||
          (ticketId != null && ticketId == recordServerId)) {
        return <String, dynamic>{...payload, 'local_id': recordLocalId};
      }
    }
    return null;
  }

  Map<String, dynamic> _appendLocalMessage({
    required Map<String, dynamic> ticket,
    required String message,
    required bool isInternal,
  }) {
    final now = DateTime.now().toIso8601String();
    final messages = (ticket['messages'] as List? ?? <dynamic>[])
        .map(
          (item) => item is Map<String, dynamic>
              ? Map<String, dynamic>.from(item)
              : Map<String, dynamic>.from(item as Map),
        )
        .toList();

    messages.add(<String, dynamic>{
      'local_id': _uuid.v7(),
      'message': message,
      'created_at': now,
      'is_internal': isInternal,
      'is_mine': true,
      'user': <String, dynamic>{'name': 'You'},
    });

    return <String, dynamic>{
      ...ticket,
      'messages': messages,
      'last_message_at': now,
      'status': 'open',
    };
  }

  List<Map<String, dynamic>> _extractList(dynamic data) {
    if (data is List) {
      return data
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }

    if (data is Map<String, dynamic> && data['data'] is List) {
      return (data['data'] as List)
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }

    return <Map<String, dynamic>>[];
  }

  Map<String, dynamic>? _extractObject(dynamic data) {
    if (data is Map<String, dynamic>) {
      return Map<String, dynamic>.from(data);
    }
    if (data is Map) {
      return Map<String, dynamic>.from(data);
    }
    return null;
  }
}
