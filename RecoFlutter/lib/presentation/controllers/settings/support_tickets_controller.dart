import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../data/repositories/settings/support_tickets_repository.dart';

class SupportTicketsController extends GetxController {
  SupportTicketsController(this._repository);

  final SupportTicketsRepository _repository;

  final isLoading = false.obs;
  final isLoadingMore = false.obs;
  final isRefreshing = false.obs;
  final refreshTurns = 0.0.obs;
  final selectedStatus = ''.obs;
  final tickets = <Map<String, dynamic>>[].obs;
  final searchController = TextEditingController();
  final visibleTickets = <Map<String, dynamic>>[].obs;
  final currentPage = 1.obs;
  final lastPage = 1.obs;
  final total = 0.obs;
  final stats = <String, int>{
    'total': 0,
    'open': 0,
    'in_progress': 0,
    'waiting_on_customer': 0,
    'resolved': 0,
  }.obs;

  static const int pageSize = 20;
  bool get hasMore => currentPage.value < lastPage.value;

  @override
  void onInit() {
    super.onInit();
    loadTickets();
  }

  @override
  void onClose() {
    searchController.dispose();
    super.onClose();
  }

  Future<void> loadTickets() async {
    final isManualRefresh = !isLoading.value;
    if (isManualRefresh) {
      isRefreshing.value = true;
      refreshTurns.value += 1;
    }
    isLoading.value = true;
    currentPage.value = 1;
    try {
      final status = selectedStatus.value.isEmpty ? null : selectedStatus.value;
      final localResult = await _repository.getPaginatedTickets(
        status: status,
        page: 1,
        perPage: pageSize,
      );
      _applyPage(localResult, reset: true);

      final remoteResult = await _repository.refreshPaginatedTickets(
        status: status,
        page: 1,
        perPage: pageSize,
      );
      _applyPage(remoteResult, reset: true);
      _recomputeState();
    } finally {
      isLoading.value = false;
      if (isManualRefresh) {
        isRefreshing.value = false;
      }
    }
  }

  Future<void> applyStatus(String status) async {
    selectedStatus.value = status;
    await loadTickets();
  }

  Future<void> loadMore() async {
    if (isLoading.value || isLoadingMore.value || !hasMore) {
      return;
    }
    isLoadingMore.value = true;
    try {
      final status = selectedStatus.value.isEmpty ? null : selectedStatus.value;
      final nextPage = currentPage.value + 1;
      final result = await _repository.refreshPaginatedTickets(
        status: status,
        page: nextPage,
        perPage: pageSize,
      );
      _applyPage(result, reset: false);
      _recomputeState();
    } finally {
      isLoadingMore.value = false;
    }
  }

  void applySearch(String value) {
    _recomputeVisibleTickets(query: value);
  }

  void upsertTicket(Map<String, dynamic> ticket) {
    final incomingId = ticket['id']?.toString();
    final incomingLocalId = ticket['local_id']?.toString();

    final index = tickets.indexWhere((item) {
      final ticketId = item['id']?.toString();
      final localId = item['local_id']?.toString();
      return (incomingId != null && incomingId.isNotEmpty && ticketId == incomingId) ||
          (incomingLocalId != null &&
              incomingLocalId.isNotEmpty &&
              localId == incomingLocalId);
    });

    if (index >= 0) {
      tickets[index] = ticket;
    } else {
      tickets.insert(0, ticket);
    }

    _recomputeState();
  }

  void _applyPage(dynamic result, {required bool reset}) {
    currentPage.value = result.currentPage;
    lastPage.value = result.lastPage;
    total.value = result.total;
    final incoming = result.items
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    if (reset) {
      tickets.assignAll(
        _mergeTickets(
          incoming,
          base: const <Map<String, dynamic>>[],
        ),
      );
    } else {
      tickets.assignAll(
        _mergeTickets(
          incoming,
          base: tickets,
        ),
      );
    }
  }

  List<Map<String, dynamic>> _mergeTickets(
    List<Map<String, dynamic>> incoming, {
    required List<Map<String, dynamic>> base,
  }) {
    final merged = <String, Map<String, dynamic>>{};
    for (final item in base) {
      merged[_ticketKey(item)] = item;
    }
    for (final item in incoming) {
      merged[_ticketKey(item)] = item;
    }
    return merged.values.toList();
  }

  String _ticketKey(Map<String, dynamic> item) {
    final id = item['id']?.toString();
    if (id != null && id.isNotEmpty) {
      return 'id:$id';
    }
    final localId = item['local_id']?.toString();
    if (localId != null && localId.isNotEmpty) {
      return 'local:$localId';
    }
    final number = item['ticket_number']?.toString() ?? '';
    return 'ticket:$number';
  }

  void _recomputeState() {
    stats.assignAll(<String, int>{
      'total': tickets.length,
      'open': tickets.where((item) => item['status'] == 'open').length,
      'in_progress':
          tickets.where((item) => item['status'] == 'in_progress').length,
      'waiting_on_customer':
          tickets.where((item) => item['status'] == 'waiting_on_customer').length,
      'resolved': tickets
          .where(
            (item) => item['status'] == 'resolved' || item['status'] == 'closed',
          )
          .length,
    });
    _recomputeVisibleTickets(query: searchController.text);
  }

  void _recomputeVisibleTickets({String query = ''}) {
    final normalized = query.trim().toLowerCase();
    visibleTickets.assignAll(
      tickets.where((item) {
        if (normalized.isEmpty) {
          return true;
        }
        final subject = (item['subject'] ?? '').toString().toLowerCase();
        final ticketNumber =
            (item['ticket_number'] ?? '').toString().toLowerCase();
        final category = (item['category'] ?? '').toString().toLowerCase();
        return subject.contains(normalized) ||
            ticketNumber.contains(normalized) ||
            category.contains(normalized);
      }),
    );
  }
}
