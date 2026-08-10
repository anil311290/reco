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
import '../../../core/utils/app_action_loader.dart';
import '../../../core/utils/app_snackbar.dart';
import '../../../core/utils/simple_table_pdf_builder.dart';
import '../../../data/repositories/masters/masters_export_repository.dart';

mixin MasterExportMixin on GetxController {
  Future<void> exportMasterExcel({
    required MastersExportRepository repository,
    required String type,
    required String reportName,
    required Map<String, dynamic> queryParameters,
    required List<Map<String, dynamic>> fallbackRows,
  }) async {
    await AppActionLoader.run(
      () async {
        try {
          final response = await repository.exportExcel(
            type: type,
            queryParameters: queryParameters,
          );
          final handled = await _handleBinaryExport(response, reportName, 'xlsx');
          if (handled) return;
        } catch (_) {
          // Fallback to local CSV.
        }

        if (fallbackRows.isEmpty) {
          AppSnackbar.error('No data available for export.');
          return;
        }

        final csv = _buildCsv(fallbackRows);
        final directory = await getTemporaryDirectory();
        final fileName =
            '${reportName.toLowerCase().replaceAll(RegExp(r'[^a-z0-9]+'), '_')}_${DateTime.now().millisecondsSinceEpoch}.csv';
        final file = File(p.join(directory.path, fileName));
        await file.writeAsString(csv);

        final shareResult = await SharePlus.instance.share(
          ShareParams(
            files: <XFile>[XFile(file.path)],
            subject: '$reportName Export',
            text: '$reportName exported successfully.',
          ),
        );
        if (shareResult.status == ShareResultStatus.success ||
            shareResult.status == ShareResultStatus.dismissed) {
          AppSnackbar.success('$reportName exported successfully.');
          return;
        }
        AppSnackbar.error('Unable to open the Excel export.');
      },
      message: 'Preparing file...',
    );
  }

  Future<void> exportMasterPdf({
    required MastersExportRepository repository,
    required String type,
    required Map<String, dynamic> queryParameters,
    String? reportName,
    List<Map<String, dynamic>> fallbackRows = const <Map<String, dynamic>>[],
  }) async {
    await AppActionLoader.run(
      () async {
        try {
          final response = await repository.exportPdf(
            type: type,
            queryParameters: queryParameters,
          );
          final handled = await _handleBinaryExport(response, reportName ?? type, 'pdf');
          if (handled) return;
        } catch (_) {
          // Fallback to local PDF.
        }

        if ((reportName == null || reportName.trim().isEmpty) || fallbackRows.isEmpty) {
          AppSnackbar.error('PDF export is not available right now.');
          return;
        }

        final directory = await getTemporaryDirectory();
        final fileName =
            '${reportName.toLowerCase().replaceAll(RegExp(r'[^a-z0-9]+'), '_')}_${DateTime.now().millisecondsSinceEpoch}.pdf';
        final file = File(p.join(directory.path, fileName));
        await file.writeAsBytes(
          SimpleTablePdfBuilder.build(
            title: reportName,
            rows: fallbackRows,
          ),
        );

        await _openOrShare(
          filePath: file.path,
          reportName: reportName,
          successMessage: '$reportName exported successfully.',
        );
      },
      message: 'Preparing PDF...',
    );
  }

  /// Try to decode base64 content from API response, save to temp file,
  /// and open/share it. Returns true if successful.
  Future<bool> _handleBinaryExport(
    Map<String, dynamic> response,
    String reportName,
    String extension,
  ) async {
    final data = response['data'];
    if (data is! Map<String, dynamic>) return false;

    final base64 = data['content_base64']?.toString();
    if (base64 == null || base64.isEmpty) {
      // Try URL fallback
      final url = _resolveExportUrl(
        data['download_url']?.toString(),
        data['path']?.toString(),
      );
      if (url.isNotEmpty) {
        final uri = Uri.tryParse(url);
        if (uri != null) {
          final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
          if (launched) return true;
          await Clipboard.setData(ClipboardData(text: url));
          AppSnackbar.success('Export link copied.');
          return true;
        }
      }
      return false;
    }

    try {
      final bytes = base64Decode(base64);
      final directory = await getTemporaryDirectory();
      final safeName = reportName
          .toLowerCase()
          .replaceAll(RegExp(r'[^a-z0-9]+'), '_');
      final fileName = '${safeName}_${DateTime.now().millisecondsSinceEpoch}.$extension';
      final file = File(p.join(directory.path, fileName));
      await file.writeAsBytes(bytes);
      await _openOrShare(
        filePath: file.path,
        reportName: reportName,
        successMessage: '$reportName exported successfully.',
      );
      return true;
    } catch (_) {
      return false;
    }
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

  String _resolveExportUrl(String? downloadUrl, String? path) {
    if (downloadUrl != null && downloadUrl.isNotEmpty) return downloadUrl;
    if (path != null && path.isNotEmpty) {
      if (path.startsWith('http://') || path.startsWith('https://')) return path;
      return '${AppConfig.origin}$path';
    }
    return '';
  }

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

}
