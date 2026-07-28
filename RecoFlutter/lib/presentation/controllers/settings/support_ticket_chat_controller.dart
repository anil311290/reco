import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_snackbar.dart';
import '../../../data/repositories/settings/support_tickets_repository.dart';

class SupportTicketChatController extends GetxController {
  SupportTicketChatController(this._repository);

  final SupportTicketsRepository _repository;

  final isLoading = false.obs;
  final isRefreshing = false.obs;
  final refreshTurns = 0.0.obs;
  final isSending = false.obs;
  final currentTicket = Rxn<Map<String, dynamic>>();
  final replyController = TextEditingController();

  String? _ticketId;
  String? _localId;

  @override
  void onClose() {
    replyController.dispose();
    super.onClose();
  }

  Future<void> loadTicket({
    required Map<String, dynamic> initialTicket,
  }) async {
    _ticketId = initialTicket['id']?.toString();
    _localId = initialTicket['local_id']?.toString();
    currentTicket.value = Map<String, dynamic>.from(initialTicket);

    isLoading.value = true;
    try {
      final fresh = await _repository.getTicketDetail(
        ticketId: _ticketId,
        localId: _localId,
      );
      if (fresh != null) {
        currentTicket.value = fresh;
        _ticketId = fresh['id']?.toString() ?? _ticketId;
        _localId = fresh['local_id']?.toString() ?? _localId;
      }
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> refreshTicket() async {
    if (currentTicket.value == null) {
      return;
    }
    isRefreshing.value = true;
    refreshTurns.value += 1;
    try {
      await loadTicket(initialTicket: currentTicket.value!);
    } finally {
      isRefreshing.value = false;
    }
  }

  Future<void> sendReply() async {
    final message = replyController.text.trim();
    final ticket = currentTicket.value;
    if (message.isEmpty || ticket == null) {
      return;
    }

    isSending.value = true;
    try {
      final updated = await _repository.replyToTicket(
        ticket: ticket,
        message: message,
      );
      replyController.clear();
      if (updated != null) {
        currentTicket.value = updated;
      }
      AppSnackbar.success('Message sent successfully.');
    } catch (error) {
      AppSnackbar.error(error.toString());
    } finally {
      isSending.value = false;
    }
  }

  bool get canReply {
    final ticket = currentTicket.value;
    if (ticket == null) {
      return false;
    }
    final status = ticket['status']?.toString() ?? '';
    final hasServerId = (ticket['id']?.toString() ?? '').isNotEmpty;
    return status != 'closed' && hasServerId;
  }
}
