import 'dart:async';

import 'package:get/get.dart';

import '../../../data/models/transactions/transaction_entities.dart';
import '../../../data/repositories/masters/parties_repository.dart';

class TransactionsLookupController extends GetxController {
  TransactionsLookupController(this._partiesRepository);

  final PartiesRepository _partiesRepository;

  final parties = <TransactionLookupOption>[].obs;

  @override
  void onInit() {
    super.onInit();
    unawaited(loadParties());
  }

  Future<void> loadParties() async {
    final records = await _partiesRepository.getParties();
    parties.assignAll(
      records
          .map(
            (party) => TransactionLookupOption(
              id: party.id ?? 0,
              label: party.name,
            ),
          )
          .where((item) => item.id > 0 && item.label.trim().isNotEmpty)
          .toList(),
    );
  }
}
