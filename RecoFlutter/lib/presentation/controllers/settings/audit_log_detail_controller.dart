import 'package:get/get.dart';

import '../../../data/repositories/settings/audit_logs_repository.dart';

class AuditLogDetailController extends GetxController {
  AuditLogDetailController(this._repository);

  final AuditLogsRepository _repository;

  final isLoading = false.obs;
  final log = Rxn<Map<String, dynamic>>();

  Future<void> load(int id, {Map<String, dynamic>? initialLog}) async {
    if (initialLog != null) {
      log.value = Map<String, dynamic>.from(initialLog);
    }

    isLoading.value = true;
    try {
      final detail = await _repository.getAuditLogDetail(id);
      if (detail != null && detail.isNotEmpty) {
        log.value = detail;
      }
    } finally {
      isLoading.value = false;
    }
  }
}
