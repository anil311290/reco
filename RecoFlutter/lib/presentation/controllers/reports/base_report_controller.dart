import 'dart:io';

import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/config/app_config.dart';
import '../../../core/services/network_monitor_service.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../data/repositories/reports/reports_repository.dart';

abstract class BaseReportController extends GetxController {
  BaseReportController(
    this.repository,
    this.networkMonitorService,
  );

  final ReportsRepository repository;
  final NetworkMonitorService networkMonitorService;

  final isLoading = false.obs;
  final reportData = <String, dynamic>{}.obs;

  bool get isOnline => networkMonitorService.isOnline.value;

  String get endpoint;
  Map<String, dynamic> get queryParameters;

  Future<void> loadReport() async {
    isLoading.value = true;
    try {
      final result = await repository.getReport(
        endpoint,
        queryParameters: queryParameters,
      );
      if (result.isNotEmpty) {
        reportData.value = result;
      }
      if (await networkMonitorService.hasInternetNow()) {
        final fresh = await repository.refreshReport(
          endpoint,
          queryParameters: queryParameters,
        );
        if (fresh.isNotEmpty) {
          reportData.value = fresh;
        }
      }
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> exportPdf({
    required String exportEndpoint,
    Map<String, dynamic>? queryParameters,
  }) async {
    final response = await repository.exportPdf(
      exportEndpoint,
      queryParameters: queryParameters,
    );
    final data = response['data'];
    if (data is Map<String, dynamic>) {
      final url = _resolveExportUrl(
        data['download_url']?.toString(),
        data['path']?.toString(),
      );
      if (url != null && url.isNotEmpty) {
        final uri = Uri.tryParse(url);
        if (uri != null) {
          final launched = await launchUrl(
            uri,
            mode: LaunchMode.inAppBrowserView,
          );
          if (launched) {
            return;
          }
        }
        await Clipboard.setData(ClipboardData(text: url));
        AppSnackbar.success('PDF link copied successfully.');
        return;
      }
    }
    AppSnackbar.error('Unable to generate PDF link.');
  }

  Future<void> exportExcel({required String reportName}) async {
    final rows = _extractRowsForExport();
    if (rows.isEmpty) {
      AppSnackbar.error('Export ke liye report data available nahi hai.');
      return;
    }

    final csv = _buildCsv(rows);
    final directory = await getTemporaryDirectory();
    final fileName =
        '${reportName.toLowerCase().replaceAll(RegExp(r'[^a-z0-9]+'), '_')}_${DateTime.now().millisecondsSinceEpoch}.csv';
    final file = File(p.join(directory.path, fileName));
    await file.writeAsString(csv);

    await SharePlus.instance.share(
      ShareParams(
        files: <XFile>[XFile(file.path)],
        subject: '$reportName Export',
        text: '$reportName exported successfully.',
      ),
    );
  }

  List<Map<String, dynamic>> _extractRowsForExport() {
    final data = reportData['data'];
    if (data is! Map<String, dynamic>) {
      return <Map<String, dynamic>>[];
    }

    for (final key in <String>[
      'rows',
      'entries',
      'accounts',
      'debtors',
      'creditors',
    ]) {
      final value = data[key];
      if (value is List) {
        return value
            .whereType<Map>()
            .map((item) => _flattenMap(Map<String, dynamic>.from(item)))
            .toList();
      }
    }

    final sectionRows = <Map<String, dynamic>>[];
    for (final sectionKey in <String>[
      'income',
      'expense',
      'assets',
      'liabilities',
      'equity',
    ]) {
      final section = data[sectionKey];
      if (section is Map<String, dynamic> && section['accounts'] is List) {
        for (final item in (section['accounts'] as List).whereType<Map>()) {
          sectionRows.add(
            _flattenMap(<String, dynamic>{
              'section': sectionKey,
              ...Map<String, dynamic>.from(item),
            }),
          );
        }
      }
    }
    if (sectionRows.isNotEmpty) {
      return sectionRows;
    }

    return <Map<String, dynamic>>[_flattenMap(data)];
  }

  Map<String, dynamic> _flattenMap(
    Map<String, dynamic> source, [
    String prefix = '',
  ]) {
    final result = <String, dynamic>{};
    source.forEach((key, value) {
      final nextKey = prefix.isEmpty ? key : '${prefix}_$key';
      if (value is Map<String, dynamic>) {
        result.addAll(_flattenMap(value, nextKey));
      } else {
        result[nextKey] = value;
      }
    });
    return result;
  }

  String _buildCsv(List<Map<String, dynamic>> rows) {
    final headers = <String>{};
    for (final row in rows) {
      headers.addAll(row.keys);
    }
    final orderedHeaders = headers.toList()..sort();
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

  String? _resolveExportUrl(String? downloadUrl, String? path) {
    if (downloadUrl != null && downloadUrl.isNotEmpty) {
      return downloadUrl;
    }
    if (path != null && path.isNotEmpty) {
      if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
      }
      return '${AppConfig.origin}$path';
    }
    return null;
  }

  String formatCurrency(dynamic value) {
    final amount = double.tryParse(value?.toString() ?? '') ?? 0;
    return 'Rs ${amount.toStringAsFixed(2)}';
  }

  String formatDate(String value) =>
      value.length >= 10 ? value.substring(0, 10) : value;
}
