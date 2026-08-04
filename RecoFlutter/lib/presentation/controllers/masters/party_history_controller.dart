import 'dart:io';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';

import '../../../core/utils/app_action_loader.dart';
import '../../../core/utils/app_date_formatter.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../core/utils/simple_table_pdf_builder.dart';
import '../../../data/models/masters/master_entities.dart';
import '../../../data/repositories/masters/parties_repository.dart';

class PartyHistoryController extends GetxController {
  PartyHistoryController(
    this._repository, {
    required this.partyId,
    this.seedParty,
  });

  final PartiesRepository _repository;
  final int partyId;
  final PartyEntity? seedParty;

  final isLoading = false.obs;
  final party = Rxn<PartyEntity>();
  final transactions = <Map<String, dynamic>>[].obs;
  final totalDebit = 0.0.obs;
  final totalCredit = 0.0.obs;
  final closingBalance = 0.0.obs;
  final closingType = ''.obs;
  final fromDateController = TextEditingController();
  final toDateController = TextEditingController();

  @override
  void onInit() {
    super.onInit();
    party.value = seedParty;
    loadHistory();
  }

  Future<void> loadHistory({bool forceRefresh = false}) async {
    isLoading.value = true;
    try {
      final result = forceRefresh
          ? await _repository.refreshPartyHistory(
              partyId,
              queryParameters: queryParameters,
            )
          : await _repository.getPartyHistory(
              partyId,
              queryParameters: queryParameters,
            );
      final data = result['data'];
      if (data is Map<String, dynamic>) {
        final partyData = data['party'];
        if (partyData is Map<String, dynamic>) {
          party.value = PartyEntity.fromRecord(partyData);
        }
        totalDebit.value = _asDouble(data['total_debit']);
        totalCredit.value = _asDouble(data['total_credit']);
        closingBalance.value = _asDouble(data['closing_balance']);
        closingType.value = (data['closing_type'] ?? '').toString();
        transactions.assignAll(
          (data['transactions'] is List)
              ? (data['transactions'] as List)
                  .whereType<Map>()
                  .map((item) => Map<String, dynamic>.from(item))
                  .toList()
              : <Map<String, dynamic>>[],
        );
      }
    } finally {
      isLoading.value = false;
    }
  }

  Map<String, dynamic> get queryParameters => <String, dynamic>{
        if (fromDateController.text.isNotEmpty)
          'date_from': AppDateFormatter.toApiDate(fromDateController.text.trim()),
        if (toDateController.text.isNotEmpty)
          'date_to': AppDateFormatter.toApiDate(toDateController.text.trim()),
      };

  String formatCurrency(num? value) => 'Rs ${_asDouble(value).toStringAsFixed(2)}';

  String formatDate(String value) => AppDateFormatter.formatDisplay(value);

  Future<void> exportExcel() async {
    await AppActionLoader.run(
      () async {
        final rows = _exportRows;
        if (rows.isEmpty) {
          AppSnackbar.error('No transaction history available for export.');
          return;
        }

        final csv = _buildCsv(rows);
        final directory = await getTemporaryDirectory();
        final safeName = _safeName('${party.value?.name ?? 'party'}_history');
        final file = File(
          p.join(
            directory.path,
            '${safeName}_${DateTime.now().millisecondsSinceEpoch}.csv',
          ),
        );
        await file.writeAsString(csv);
        await _openOrShare(
          filePath: file.path,
          reportName: 'Party History',
          successMessage: 'Party history exported successfully.',
        );
      },
      message: 'Preparing file...',
    );
  }

  Future<void> exportPdf() async {
    await AppActionLoader.run(
      () async {
        final rows = _exportRows;
        if (rows.isEmpty) {
          AppSnackbar.error('PDF export is not available right now.');
          return;
        }

        final directory = await getTemporaryDirectory();
        final safeName = _safeName('${party.value?.name ?? 'party'}_history');
        final file = File(
          p.join(
            directory.path,
            '${safeName}_${DateTime.now().millisecondsSinceEpoch}.pdf',
          ),
        );
        await file.writeAsBytes(
          SimpleTablePdfBuilder.build(
            title: 'Party History - ${party.value?.name ?? ''}',
            rows: rows,
            summaryLines: <String>[
              'Code: ${party.value?.partyCode ?? '-'}',
              'Type: ${party.value?.type ?? '-'}',
              'Dr ${formatCurrency(totalDebit.value)} | Cr ${formatCurrency(totalCredit.value)} | Closing ${formatCurrency(closingBalance.value)} ${closingType.value}',
            ],
          ),
        );
        await _openOrShare(
          filePath: file.path,
          reportName: 'Party History',
          successMessage: 'Party history exported successfully.',
        );
      },
      message: 'Preparing PDF...',
    );
  }

  @override
  void onClose() {
    fromDateController.dispose();
    toDateController.dispose();
    super.onClose();
  }

  double _asDouble(dynamic value) {
    if (value is num) {
      return value.toDouble();
    }
    return double.tryParse(value?.toString() ?? '') ?? 0;
  }

  List<Map<String, dynamic>> get _exportRows => transactions
      .map(
        (row) => <String, dynamic>{
          'Date': formatDate((row['date'] ?? '').toString()),
          'Voucher #': (row['voucher_number'] ?? '-').toString(),
          'Particulars': (row['description'] ?? '-').toString(),
          'Debit': _asDouble(row['debit']).toStringAsFixed(2),
          'Credit': _asDouble(row['credit']).toStringAsFixed(2),
          'Balance':
              '${_asDouble(row['running_balance']).toStringAsFixed(2)} ${((row['running_type'] ?? '').toString()).toUpperCase()}',
        },
      )
      .toList();

  String _buildCsv(List<Map<String, dynamic>> rows) {
    final headers = <String>{};
    for (final row in rows) {
      headers.addAll(row.keys);
    }
    final orderedHeaders = headers.toList();
    final buffer = StringBuffer()
      ..writeln(orderedHeaders.map(_escapeCsv).join(','));
    for (final row in rows) {
      buffer.writeln(
        orderedHeaders
            .map((header) => _escapeCsv(row[header]?.toString() ?? ''))
            .join(','),
      );
    }
    return buffer.toString();
  }

  String _escapeCsv(String value) {
    final escaped = value.replaceAll('"', '""');
    return '"$escaped"';
  }

  String _safeName(String value) => value
      .toLowerCase()
      .replaceAll(RegExp(r'[^a-z0-9]+'), '_')
      .replaceAll(RegExp(r'_+'), '_')
      .replaceAll(RegExp(r'^_|_$'), '');

  Future<void> _openOrShare({
    required String filePath,
    required String reportName,
    required String successMessage,
  }) async {
    final openResult = await OpenFilex.open(filePath);
    if (openResult.type == ResultType.done) {
      AppSnackbar.success(successMessage);
      return;
    }

    final shareResult = await SharePlus.instance.share(
      ShareParams(
        files: <XFile>[XFile(filePath)],
        subject: '$reportName Export',
        text: '$reportName exported successfully.',
      ),
    );
    if (shareResult.status == ShareResultStatus.success ||
        shareResult.status == ShareResultStatus.dismissed) {
      AppSnackbar.success(successMessage);
      return;
    }

    AppSnackbar.error('Unable to open the file.');
  }

}
