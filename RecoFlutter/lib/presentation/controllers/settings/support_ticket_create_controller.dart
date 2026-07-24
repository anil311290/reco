import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_snackbar.dart';
import '../../../data/repositories/settings/support_tickets_repository.dart';

class SupportTicketCreateController extends GetxController {
  SupportTicketCreateController(this._repository);

  final SupportTicketsRepository _repository;

  final formKey = GlobalKey<FormState>();
  final subjectController = TextEditingController();
  final messageController = TextEditingController();
  final selectedCategory = 'general'.obs;
  final selectedPriority = 'normal'.obs;
  final isSubmitting = false.obs;

  @override
  void onClose() {
    subjectController.dispose();
    messageController.dispose();
    super.onClose();
  }

  Future<void> submit() async {
    if (!(formKey.currentState?.validate() ?? false)) {
      return;
    }

    isSubmitting.value = true;
    try {
      final ticket = await _repository.createTicket(<String, dynamic>{
        'subject': subjectController.text.trim(),
        'message': messageController.text.trim(),
        'category': selectedCategory.value,
        'priority': selectedPriority.value,
      });
      AppSnackbar.success('Support ticket created successfully.');
      Get.back<Map<String, dynamic>>(result: ticket);
    } catch (error) {
      AppSnackbar.error(error.toString());
    } finally {
      isSubmitting.value = false;
    }
  }
}
