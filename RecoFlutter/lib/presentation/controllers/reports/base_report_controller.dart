import 'dart:convert';
import 'dart:io';

import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/config/app_config.dart';
import '../../../core/services/network_monitor_service.dart';
import '../../../core/utils/app_action_loader.dart';
import '../../../core/utils/app_date_formatter.dart';
import '../../../core/utils/amount_formatter.dart';
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
  final hasLoadedOnce = false.obs;
  final reportData = <String, dynamic>{}.obs;

  bool get isOnline => networkMonitorService.isOnline.value;
  bool get shouldShowInitialLoader =>
      !hasLoadedOnce.value || (isLoading.value && !_hasRenderableData(reportData));

  String get endpoint;
  Map<String, dynamic> get queryParameters;

  String formatCurrency(dynamic value) {
    return AmountFormatter.currency(value);
  }

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
      hasLoadedOnce.value = true;
      isLoading.value = false;
    }
  }

  Future<void> exportPdf({
    required String exportEndpoint,
    Map<String, dynamic>? queryParameters,
  }) async {
    await AppActionLoader.run(
      () => _exportFile(
        exportEndpoint: exportEndpoint,
        queryParameters: queryParameters,
        fallbackExtension: 'pdf',
        successMessage: 'PDF exported successfully.',
        errorMessage: 'Unable to generate PDF.',
        linkCopiedMessage: 'PDF link copied.',
        label: 'Report PDF',
      ),
      message: 'Preparing PDF...',
    );
  }

  Future<void> exportExcel({
    required String reportName,
    String? exportEndpoint,
    Map<String, dynamic>? queryParameters,
  }) async {
    await AppActionLoader.run(
      () async {
        if (exportEndpoint != null) {
          final exported = await _exportFile(
            exportEndpoint: exportEndpoint,
            queryParameters: queryParameters,
            fallbackExtension: 'xlsx',
            successMessage: 'Excel exported successfully.',
            errorMessage: 'Unable to generate Excel.',
            linkCopiedMessage: 'Excel link copied.',
            label: reportName,
          );
          if (exported) {
            return;
          }
        }

        final rows = _extractRowsForExport();
        if (rows.isEmpty) {
          AppSnackbar.error('No report data available for export.');
          return;
        }

        final csv = _buildCsv(rows);
        final directory = await getTemporaryDirectory();
        final fileName =
            '${reportName.toLowerCase().replaceAll(RegExp(r'[^a-z0-9]+'), '_')}_${DateTime.now().millisecondsSinceEpoch}.csv';
        final file = File(p.join(directory.path, fileName));
        await file.writeAsString(csv);

        await _openOrShare(
          filePath: file.path,
          reportName: reportName,
          successMessage: '$reportName exported successfully.',
        );
      },
      message: 'Preparing file...',
    );
  }

  Future<bool> _exportFile({
    required String exportEndpoint,
    Map<String, dynamic>? queryParameters,
    required String fallbackExtension,
    required String successMessage,
    required String errorMessage,
    required String linkCopiedMessage,
    required String label,
  }) async {
    try {
      final response = await repository.exportFile(
        exportEndpoint,
        queryParameters: queryParameters,
      );
      final data = response['data'];
      if (data is Map<String, dynamic>) {
        final base64 = data['content_base64']?.toString();
        if (base64 != null && base64.isNotEmpty) {
          final bytes = base64Decode(base64);
          final directory = await getTemporaryDirectory();
          final reportName = exportEndpoint
              .split('/')
              .length > 2
              ? exportEndpoint.split('/')[2]
              : exportEndpoint.split('/').last
              ;
          final safeName = reportName
              .replaceAll('-', '_');
          final fileName =
              '${safeName}_${DateTime.now().millisecondsSinceEpoch}.$fallbackExtension';
          final file = File(p.join(directory.path, fileName));
          await file.writeAsBytes(bytes);
          await _openOrShare(
            filePath: file.path,
            reportName: label,
            successMessage: successMessage,
          );
          return true;
        }

        final url = _resolveExportUrl(
          data['download_url']?.toString(),
          data['path']?.toString(),
        );
        if (url != null && url.isNotEmpty) {
          final uri = Uri.tryParse(url);
          if (uri != null) {
            final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
            if (launched) return true;
          }
          await Clipboard.setData(ClipboardData(text: url));
          AppSnackbar.success(linkCopiedMessage);
          return true;
        }
      }
    } catch (_) {}
    AppSnackbar.error(errorMessage);
    return false;
  }

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

  bool _hasRenderableData(Map<String, dynamic> source) {
    if (source.isEmpty) {
      return false;
    }
    final data = source['data'];
    if (data is List) {
      return data.isNotEmpty;
    }
    if (data is Map<String, dynamic>) {
      return data.isNotEmpty;
    }
    return data != null;
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

  String formatDate(String value) => AppDateFormatter.formatDisplay(value);
}
