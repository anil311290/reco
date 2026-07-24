import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../data/repositories/settings/support_tickets_repository.dart';

class SupportTicketsController extends GetxController {
  SupportTicketsController(this._repository);

  final SupportTicketsRepository _repository;

  final isLoading = false.obs;
  final selectedStatus = ''.obs;
  final tickets = <Map<String, dynamic>>[].obs;
  final searchController = TextEditingController();
  final visibleTickets = <Map<String, dynamic>>[].obs;
  final stats = <String, int>{
    'total': 0,
    'open': 0,
    'in_progress': 0,
    'waiting_on_customer': 0,
    'resolved': 0,
  }.obs;

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
    isLoading.value = true;
    try {
      tickets.assignAll(
        await _repository.getTickets(
          status: selectedStatus.value.isEmpty ? null : selectedStatus.value,
        ),
      );
      _recomputeState();
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> applyStatus(String status) async {
    selectedStatus.value = status;
    await loadTickets();
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
