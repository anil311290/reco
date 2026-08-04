import 'dart:convert';
import 'dart:math' as math;

class SimpleTablePdfBuilder {
  const SimpleTablePdfBuilder._();

  static List<int> build({
    required String title,
    required List<Map<String, dynamic>> rows,
    List<String> summaryLines = const <String>[],
  }) {
    final headers = _orderedHeaders(rows);
    final pages = _paginate(
      title: title,
      headers: headers,
      rows: rows,
      summaryLines: summaryLines,
    );

    final objects = <String>[];
    final pageObjectNumbers = <int>[];
    int objectNumber = 1;

    final regularFontObjectNumber = objectNumber++;
    objects.add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');

    final boldFontObjectNumber = objectNumber++;
    objects.add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>');

    for (final page in pages) {
      final content = _buildPageContent(
        title: title,
        headers: headers,
        rows: page.rows,
        summaryLines: page.summaryLines,
        pageNumber: page.pageNumber,
        totalPages: pages.length,
      );
      final contentObjectNumber = objectNumber++;
      objects.add(
        '<< /Length ${utf8.encode(content).length} >>\nstream\n$content\nendstream',
      );

      final pageObjectNumber = objectNumber++;
      pageObjectNumbers.add(pageObjectNumber);
      objects.add(
        '<< /Type /Page /Parent 0 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 $regularFontObjectNumber 0 R /F2 $boldFontObjectNumber 0 R >> >> /Contents $contentObjectNumber 0 R >>',
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

  static List<String> _orderedHeaders(List<Map<String, dynamic>> rows) {
    final headers = <String>[];
    for (final row in rows) {
      for (final key in row.keys) {
        if (!headers.contains(key)) {
          headers.add(key);
        }
      }
    }
    return headers;
  }

  static List<_PdfPageData> _paginate({
    required String title,
    required List<String> headers,
    required List<Map<String, dynamic>> rows,
    required List<String> summaryLines,
  }) {
    const double pageHeight = 842;
    const double topMargin = 36;
    const double bottomMargin = 34;
    const double titleBlockHeight = 52;
    const double summaryLineHeight = 14;
    const double headerHeight = 24;
    const double rowHeight = 22;

    final availableHeight = pageHeight - topMargin - bottomMargin;
    final summaryHeight = summaryLines.isEmpty
        ? 0.0
        : (summaryLines.length * summaryLineHeight) + 6;

    final firstPageTableHeight =
        availableHeight - titleBlockHeight - summaryHeight - headerHeight;
    final nextPageTableHeight =
        availableHeight - 28 - headerHeight;

    final firstPageRows = math.max(1, (firstPageTableHeight / rowHeight).floor());
    final otherPageRows = math.max(1, (nextPageTableHeight / rowHeight).floor());

    final pages = <_PdfPageData>[];
    var rowIndex = 0;
    var pageNumber = 1;

    while (rowIndex < rows.length || pageNumber == 1) {
      final rowsPerPage = pageNumber == 1 ? firstPageRows : otherPageRows;
      final endIndex = math.min(rowIndex + rowsPerPage, rows.length);
      final pageRows = rowIndex < rows.length
          ? rows.sublist(rowIndex, endIndex)
          : <Map<String, dynamic>>[];
      pages.add(
        _PdfPageData(
          pageNumber: pageNumber,
          rows: pageRows,
          summaryLines: pageNumber == 1 ? summaryLines : const <String>[],
        ),
      );
      rowIndex = endIndex;
      pageNumber += 1;
    }

    return pages;
  }

  static String _buildPageContent({
    required String title,
    required List<String> headers,
    required List<Map<String, dynamic>> rows,
    required List<String> summaryLines,
    required int pageNumber,
    required int totalPages,
  }) {
    const double pageWidth = 595;
    const double pageHeight = 842;
    const double left = 32;
    const double right = 32;
    const double top = 36;
    const double bottom = 34;
    const double titleFontSize = 15;
    const double bodyFontSize = 8.6;
    const double smallFontSize = 8;
    const double rowHeight = 22;
    const double headerHeight = 24;

    final width = pageWidth - left - right;
    final columnWidths = _columnWidths(headers, width);
    final buffer = StringBuffer();
    var cursorY = pageHeight - top;

    _writeText(
      buffer,
      text: title,
      x: left,
      y: cursorY,
      font: 'F2',
      fontSize: titleFontSize,
    );
    cursorY -= 20;
    _writeText(
      buffer,
      text: 'Generated from app fallback export',
      x: left,
      y: cursorY,
      font: 'F1',
      fontSize: smallFontSize,
      color: const _Rgb(0.38, 0.42, 0.50),
    );
    cursorY -= 18;

    for (final line in summaryLines) {
      _writeText(
        buffer,
        text: line,
        x: left,
        y: cursorY,
        font: 'F1',
        fontSize: smallFontSize,
        color: const _Rgb(0.22, 0.26, 0.33),
      );
      cursorY -= 14;
    }

    if (summaryLines.isNotEmpty) {
      cursorY -= 4;
    }

    _drawFilledRect(
      buffer,
      x: left,
      y: cursorY - headerHeight + 5,
      width: width,
      height: headerHeight,
      fill: const _Rgb(0.93, 0.95, 0.98),
      stroke: const _Rgb(0.82, 0.85, 0.90),
    );

    var x = left;
    for (var index = 0; index < headers.length; index++) {
      final header = headers[index];
      final colWidth = columnWidths[index];
      if (index > 0) {
        _drawLine(
          buffer,
          x1: x,
          y1: cursorY - headerHeight + 5,
          x2: x,
          y2: cursorY + 5,
          color: const _Rgb(0.82, 0.85, 0.90),
        );
      }
      _writeText(
        buffer,
        text: _fitText(header, colWidth - 8, 8.4),
        x: x + 4,
        y: cursorY - 12,
        font: 'F2',
        fontSize: 8.4,
        color: const _Rgb(0.16, 0.19, 0.25),
      );
      x += colWidth;
    }
    cursorY -= headerHeight;

    for (var rowIndex = 0; rowIndex < rows.length; rowIndex++) {
      final row = rows[rowIndex];
      final bool shaded = rowIndex.isEven;
      _drawFilledRect(
        buffer,
        x: left,
        y: cursorY - rowHeight + 5,
        width: width,
        height: rowHeight,
        fill: shaded ? const _Rgb(0.985, 0.987, 0.992) : const _Rgb(1, 1, 1),
        stroke: const _Rgb(0.88, 0.90, 0.93),
      );

      x = left;
      for (var index = 0; index < headers.length; index++) {
        final header = headers[index];
        final colWidth = columnWidths[index];
        if (index > 0) {
          _drawLine(
            buffer,
            x1: x,
            y1: cursorY - rowHeight + 5,
            x2: x,
            y2: cursorY + 5,
            color: const _Rgb(0.90, 0.92, 0.95),
          );
        }
        _writeText(
          buffer,
          text: _fitText((row[header] ?? '-').toString().replaceAll('\n', ' '), colWidth - 8, bodyFontSize),
          x: x + 4,
          y: cursorY - 13,
          font: 'F1',
          fontSize: bodyFontSize,
          color: const _Rgb(0.14, 0.16, 0.20),
        );
        x += colWidth;
      }
      cursorY -= rowHeight;
    }

    _drawLine(
      buffer,
      x1: left,
      y1: bottom,
      x2: pageWidth - right,
      y2: bottom,
      color: const _Rgb(0.85, 0.88, 0.92),
    );
    _writeText(
      buffer,
      text: 'Page $pageNumber / $totalPages',
      x: pageWidth - right - 58,
      y: bottom - 12,
      font: 'F1',
      fontSize: 8,
      color: const _Rgb(0.40, 0.44, 0.50),
    );

    return buffer.toString();
  }

  static List<double> _columnWidths(List<String> headers, double totalWidth) {
    if (headers.isEmpty) {
      return <double>[totalWidth];
    }
    final weights = headers.map((header) {
      final lower = header.toLowerCase();
      if (lower.contains('particular') ||
          lower.contains('description') ||
          lower.contains('name') ||
          lower.contains('notes')) {
        return 2.3;
      }
      if (lower.contains('balance') || lower.contains('ledger')) {
        return 1.6;
      }
      return 1.15;
    }).toList();
    final totalWeight = weights.fold<double>(0, (sum, item) => sum + item);
    return weights.map((weight) => totalWidth * (weight / totalWeight)).toList();
  }

  static String _fitText(String text, double width, double fontSize) {
    final normalized = text.trim().isEmpty ? '-' : text.trim();
    final maxChars = math.max(1, (width / (fontSize * 0.56)).floor());
    if (normalized.length <= maxChars) {
      return normalized;
    }
    if (maxChars <= 3) {
      return normalized.substring(0, maxChars);
    }
    return '${normalized.substring(0, maxChars - 3)}...';
  }

  static void _writeText(
    StringBuffer buffer, {
    required String text,
    required double x,
    required double y,
    required String font,
    required double fontSize,
    _Rgb color = const _Rgb(0, 0, 0),
  }) {
    final escaped = text
        .replaceAll(r'\', r'\\')
        .replaceAll('(', r'\(')
        .replaceAll(')', r'\)');
    buffer
      ..writeln('BT')
      ..writeln('/$font $fontSize Tf')
      ..writeln('${color.r} ${color.g} ${color.b} rg')
      ..writeln('1 0 0 1 ${x.toStringAsFixed(2)} ${y.toStringAsFixed(2)} Tm')
      ..writeln('($escaped) Tj')
      ..writeln('ET');
  }

  static void _drawFilledRect(
    StringBuffer buffer, {
    required double x,
    required double y,
    required double width,
    required double height,
    required _Rgb fill,
    required _Rgb stroke,
  }) {
    buffer
      ..writeln('q')
      ..writeln('${fill.r} ${fill.g} ${fill.b} rg')
      ..writeln('${stroke.r} ${stroke.g} ${stroke.b} RG')
      ..writeln('${x.toStringAsFixed(2)} ${y.toStringAsFixed(2)} ${width.toStringAsFixed(2)} ${height.toStringAsFixed(2)} re B')
      ..writeln('Q');
  }

  static void _drawLine(
    StringBuffer buffer, {
    required double x1,
    required double y1,
    required double x2,
    required double y2,
    required _Rgb color,
  }) {
    buffer
      ..writeln('q')
      ..writeln('${color.r} ${color.g} ${color.b} RG')
      ..writeln('0.6 w')
      ..writeln('${x1.toStringAsFixed(2)} ${y1.toStringAsFixed(2)} m')
      ..writeln('${x2.toStringAsFixed(2)} ${y2.toStringAsFixed(2)} l S')
      ..writeln('Q');
  }
}

class _PdfPageData {
  const _PdfPageData({
    required this.pageNumber,
    required this.rows,
    required this.summaryLines,
  });

  final int pageNumber;
  final List<Map<String, dynamic>> rows;
  final List<String> summaryLines;
}

class _Rgb {
  const _Rgb(this.r, this.g, this.b);

  final double r;
  final double g;
  final double b;
}
