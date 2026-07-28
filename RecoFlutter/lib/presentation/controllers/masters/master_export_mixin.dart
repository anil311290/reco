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
        await file.writeAsBytes(_buildSimplePdf(reportName, fallbackRows));

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
    final contentObjectNumbers = <int>[];
    int objectNumber = 1;

    final fontObjectNumber = objectNumber++;
    objects.add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');

    for (final pageLines in pages) {
      final content = _buildPdfContentStream(pageLines);
      final contentObjectNumber = objectNumber++;
      contentObjectNumbers.add(contentObjectNumber);
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

    for (var index = 0; index < pageObjectNumbers.length; index++) {
      final pageObjectIndex = pageObjectNumbers[index] - 1;
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
