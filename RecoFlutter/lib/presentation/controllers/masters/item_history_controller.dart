import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';

import '../../../core/utils/app_action_loader.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/models/masters/master_entities.dart';
import '../../../data/repositories/masters/items_repository.dart';

class ItemHistoryController extends GetxController {
  ItemHistoryController(
    this._repository, {
    required this.itemId,
    this.seedItem,
  });

  final ItemsRepository _repository;
  final int itemId;
  final ItemEntity? seedItem;

  final isLoading = false.obs;
  final item = Rxn<ItemEntity>();
  final transactions = <Map<String, dynamic>>[].obs;
  final totalIn = 0.0.obs;
  final totalOut = 0.0.obs;
  final totalSalesAmount = 0.0.obs;
  final totalPurchaseAmount = 0.0.obs;
  final closingQty = 0.0.obs;
  final currentPage = 1.obs;
  final perPage = 15.obs;
  final totalRecords = 0.obs;
  final lastPage = 1.obs;
  final fromDateController = TextEditingController();
  final toDateController = TextEditingController();

  @override
  void onInit() {
    super.onInit();
    item.value = seedItem;
    loadHistory();
  }

  Future<void> loadHistory({
    bool forceRefresh = false,
    int? page,
  }) async {
    isLoading.value = true;
    try {
      if (page != null) {
        currentPage.value = page;
      }
      final result = forceRefresh
          ? await _repository.refreshItemHistory(
              itemId,
              queryParameters: queryParameters,
            )
          : await _repository.getItemHistory(
              itemId,
              queryParameters: queryParameters,
            );
      final data = result['data'];
      if (data is Map<String, dynamic>) {
        final itemData = data['item'];
        if (itemData is Map<String, dynamic>) {
          item.value = ItemEntity.fromRecord(itemData);
        }
        totalIn.value = _asDouble(data['total_in']);
        totalOut.value = _asDouble(data['total_out']);
        totalSalesAmount.value = _asDouble(data['total_sales_amount']);
        totalPurchaseAmount.value = _asDouble(data['total_purchase_amount']);
        closingQty.value = _asDouble(data['closing_qty']);
        final pagination = data['pagination'];
        if (pagination is Map<String, dynamic>) {
          currentPage.value = _asInt(pagination['current_page'], fallback: 1);
          perPage.value = _asInt(pagination['per_page'], fallback: 15);
          totalRecords.value = _asInt(pagination['total']);
          lastPage.value = _asInt(pagination['last_page'], fallback: 1);
        }
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
          'date_from': fromDateController.text.trim(),
        if (toDateController.text.isNotEmpty)
          'date_to': toDateController.text.trim(),
        'page': currentPage.value,
        'per_page': perPage.value,
      };

  String formatCurrency(num? value) => '₹ ${_asDouble(value).toStringAsFixed(2)}';
  String formatQuantity(num? value) => _asDouble(value).toStringAsFixed(3);
  String formatDate(String value) {
    if (value.trim().isEmpty) {
      return '—';
    }
    final parsed = DateTime.tryParse(value);
    if (parsed == null) {
      return value.length >= 10 ? value.substring(0, 10) : value;
    }
    final day = parsed.day.toString().padLeft(2, '0');
    final month = parsed.month.toString().padLeft(2, '0');
    return '$day/$month/${parsed.year}';
  }

  int get firstRecordIndex {
    if (totalRecords.value == 0) {
      return 0;
    }
    return ((currentPage.value - 1) * perPage.value) + 1;
  }

  int get lastRecordIndex {
    final candidate = currentPage.value * perPage.value;
    return candidate > totalRecords.value ? totalRecords.value : candidate;
  }

  Future<void> exportExcel() async {
    await AppActionLoader.run(
      () async {
        final rows = _exportRows;
        if (rows.isEmpty) {
          AppSnackbar.error('No item history available for export.');
          return;
        }

        final csv = _buildCsv(rows);
        final directory = await getTemporaryDirectory();
        final safeName = _safeName('${item.value?.name ?? 'item'}_history');
        final file = File(
          p.join(
            directory.path,
            '${safeName}_${DateTime.now().millisecondsSinceEpoch}.csv',
          ),
        );
        await file.writeAsString(csv);
        await _openOrShare(
          filePath: file.path,
          reportName: 'Item History',
          successMessage: 'Item history exported successfully.',
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
        final safeName = _safeName('${item.value?.name ?? 'item'}_history');
        final file = File(
          p.join(
            directory.path,
            '${safeName}_${DateTime.now().millisecondsSinceEpoch}.pdf',
          ),
        );
        await file.writeAsBytes(
          _buildSimplePdf(
            'Item History - ${item.value?.name ?? ''}',
            rows,
          ),
        );
        await _openOrShare(
          filePath: file.path,
          reportName: 'Item History',
          successMessage: 'Item history exported successfully.',
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

  int _asInt(dynamic value, {int fallback = 0}) {
    if (value is int) {
      return value;
    }
    if (value is num) {
      return value.toInt();
    }
    return int.tryParse(value?.toString() ?? '') ?? fallback;
  }

  List<Map<String, dynamic>> get _exportRows => transactions
      .map(
        (row) => <String, dynamic>{
          'Date': formatDate((row['date'] ?? '').toString()),
          'Type': (row['type_label'] ?? '-').toString(),
          'Invoice #': (row['invoice_number'] ?? '-').toString(),
          'Party': (row['party_name'] ?? '-').toString(),
          'Qty In': _asDouble(row['qty_in']).toStringAsFixed(3),
          'Qty Out': _asDouble(row['qty_out']).toStringAsFixed(3),
          'Rate': _asDouble(row['rate']).toStringAsFixed(2),
          'Amount': _asDouble(row['amount']).toStringAsFixed(2),
          'Balance Qty': _asDouble(row['running_qty']).toStringAsFixed(3),
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

  List<int> _buildSimplePdf(
    String title,
    List<Map<String, dynamic>> rows,
  ) {
    final headers = <String>{};
    for (final row in rows) {
      headers.addAll(row.keys);
    }
    final orderedHeaders = headers.toList();
    final lines = <String>[
      title.replaceAll('_', ' ').toUpperCase(),
      '',
      orderedHeaders.join(' | '),
      ...rows.map(
        (row) => orderedHeaders
            .map((header) => (row[header]?.toString() ?? '-').replaceAll('\n', ' '))
            .join(' | '),
      ),
    ];

    const int linesPerPage = 34;
    final pages = <List<String>>[];
    for (var index = 0; index < lines.length; index += linesPerPage) {
      final end = (index + linesPerPage < lines.length)
          ? index + linesPerPage
          : lines.length;
      pages.add(lines.sublist(index, end));
    }

    final objects = <String>[];
    final pageObjectNumbers = <int>[];
    int objectNumber = 1;

    final fontObjectNumber = objectNumber++;
    objects.add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');

    for (final pageLines in pages) {
      final content = _buildPdfContentStream(pageLines);
      final contentObjectNumber = objectNumber++;
      objects.add(
        '<< /Length ${utf8.encode(content).length} >>\nstream\n$content\nendstream',
      );

      final pageObjectNumber = objectNumber++;
      pageObjectNumbers.add(pageObjectNumber);
      objects.add(
        '<< /Type /Page /Parent 0 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 $fontObjectNumber 0 R >> >> /Contents $contentObjectNumber 0 R >>',
      );
    }

    final pagesObjectNumber = objectNumber++;
    final kids = pageObjectNumbers.map((id) => '$id 0 R').join(' ');
    objects.add(
      '<< /Type /Pages /Kids [ $kids ] /Count ${pageObjectNumbers.length} >>',
    );

    for (final pageObjectNumber in pageObjectNumbers) {
      final pageObjectIndex = pageObjectNumber - 1;
      objects[pageObjectIndex] = objects[pageObjectIndex].replaceFirst(
        '/Parent 0 0 R',
        '/Parent $pagesObjectNumber 0 R',
      );
    }

    final catalogObjectNumber = objectNumber++;
    objects.add('<< /Type /Catalog /Pages $pagesObjectNumber 0 R >>');

    final buffer = StringBuffer('%PDF-1.4\n');
    final offsets = <int>[0];
    for (var index = 0; index < objects.length; index++) {
      offsets.add(utf8.encode(buffer.toString()).length);
      buffer.write('${index + 1} 0 obj\n${objects[index]}\nendobj\n');
    }

    final xrefStart = utf8.encode(buffer.toString()).length;
    buffer.write('xref\n0 ${objects.length + 1}\n');
    buffer.write('0000000000 65535 f \n');
    for (var index = 1; index < offsets.length; index++) {
      buffer.writeln('${offsets[index].toString().padLeft(10, '0')} 00000 n ');
    }
    buffer.write(
      'trailer\n<< /Size ${objects.length + 1} /Root $catalogObjectNumber 0 R >>\nstartxref\n$xrefStart\n%%EOF',
    );

    return utf8.encode(buffer.toString());
  }

  String _buildPdfContentStream(List<String> lines) {
    final buffer = StringBuffer('BT\n/F1 10 Tf\n40 800 Td\n12 TL\n');
    for (final line in lines) {
      final escaped = line
          .replaceAll(r'\', r'\\')
          .replaceAll('(', r'\(')
          .replaceAll(')', r'\)');
      buffer.writeln('($escaped) Tj');
      buffer.writeln('T*');
    }
    buffer.write('ET');
    return buffer.toString();
  }
}
