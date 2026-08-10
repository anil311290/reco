import 'package:data_table_2/data_table_2.dart';
import 'package:dropdown_button2/dropdown_button2.dart';
import 'package:flutter/material.dart';

import '../../../../core/utils/app_spacing.dart';
class MasterLineTabs extends StatefulWidget {
  const MasterLineTabs({
    super.key,
    required this.labels,
    required this.value,
    required this.onChanged,
  });

  final List<String> labels;
  final int value;
  final ValueChanged<int> onChanged;

  @override
  State<MasterLineTabs> createState() => _MasterLineTabsState();
}


class _MasterLineTabsState extends State<MasterLineTabs> {
  final _scrollController = ScrollController();
  late final List<GlobalKey> _tabKeys;

  @override
  void initState() {
    super.initState();
    _tabKeys = List.generate(widget.labels.length, (_) => GlobalKey());
    WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToSelected());
  }

  @override
  void didUpdateWidget(covariant MasterLineTabs oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.value != widget.value) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToSelected());
    }
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToSelected() {
    if (!mounted || widget.value < 0 || widget.value >= _tabKeys.length) return;
    final context = _tabKeys[widget.value].currentContext;
    if (context == null) return;
    Scrollable.ensureVisible(
      context,
      duration: const Duration(milliseconds: 240),
      curve: Curves.easeOutCubic,
      alignment: .5,
    );
  }

  @override
  Widget build(BuildContext context) {
    final primary = Theme.of(context).colorScheme.primary;
    final textColor = Theme.of(context).colorScheme.onSurface;

    return Container(
      height: 41,
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        border: Border(
          bottom: BorderSide(
            color: Theme.of(context).dividerColor.withValues(alpha: .5),
          ),
        ),
      ),
      child: SingleChildScrollView(
        controller: _scrollController,
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.zero,
        child: Row(
          children: List.generate(widget.labels.length, (index) {
            final selected = widget.value == index;
            return GestureDetector(
              key: _tabKeys[index],
              behavior: HitTestBehavior.opaque,
              onTap: () => widget.onChanged(index),
              child: Container(
                height: 40,
                padding: const EdgeInsets.symmetric(horizontal: 14),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.end,
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Expanded(
                      child: Center(
                        child: Text(
                          widget.labels[index],
                          maxLines: 1,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            color: selected ? primary : textColor,
                            fontSize: 14,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                    ),
                    AnimatedContainer(
                      duration: const Duration(milliseconds: 180),
                      width: selected
                          ? _indicatorWidth(widget.labels[index])
                          : 0,
                      height: 3,
                      decoration: BoxDecoration(
                        color: selected ? primary : Colors.transparent,
                        borderRadius: const BorderRadius.vertical(
                          top: Radius.circular(8),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            );
          }),
        ),
      ),
    );
  }

  double _indicatorWidth(String label) {
    return (label.length * 8.0).clamp(48.0, 126.0);
  }
}


class SearchBox extends StatelessWidget {
  const SearchBox({
    required this.hint,
    this.controller,
    this.onChanged,
    this.height = 40,
    this.bottomPadding = 12,
    super.key,
  });

  final String hint;
  final TextEditingController? controller;
  final ValueChanged<String>? onChanged;
  final double height;
  final double bottomPadding;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: bottomPadding),
      child: SizedBox(
        height: height,
        child: TextField(
          controller: controller,
          onChanged: onChanged,
          textAlignVertical: TextAlignVertical.center,
          decoration: InputDecoration(
            hintText: hint,
            prefixIcon: const Icon(Icons.search, size: 20),
            isDense: true,
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 12,
              vertical: 0,
            ),
          ),
        ),
      ),
    );
  }
}

class PaginatedTablePane extends StatelessWidget {
  const PaginatedTablePane({
    super.key,
    required this.child,
    required this.hasMore,
    required this.isLoadingMore,
    required this.loadedCount,
    required this.totalCount,
    this.onLoadMore,
  });

  final Widget child;
  final bool hasMore;
  final bool isLoadingMore;
  final int loadedCount;
  final int totalCount;
  final Future<void> Function()? onLoadMore;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: <Widget>[
        Expanded(
          child: NotificationListener<ScrollNotification>(
            onNotification: (notification) {
              if (notification.metrics.axis != Axis.vertical) {
                return false;
              }
              if (!hasMore || isLoadingMore || onLoadMore == null) {
                return false;
              }
              if (notification.metrics.pixels >=
                  notification.metrics.maxScrollExtent - 160) {
                onLoadMore!.call();
              }
              return false;
            },
            child: child,
          ),
        ),
        if (isLoadingMore || hasMore) ...<Widget>[
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: <Widget>[
              if (isLoadingMore) ...<Widget>[
                const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
                const SizedBox(width: 8),
                Text(
                  'Loading more...',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ] else
                Text(
                  'Showing $loadedCount of $totalCount. Scroll to load more.',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                ),
            ],
          ),
        ],
      ],
    );
  }
}

class CompactSearchFilterBar extends StatelessWidget {
  const CompactSearchFilterBar({
    required this.hint,
    required this.onFilterTap,
    this.controller,
    this.onChanged,
    this.filterTooltip = 'Filters',
    this.onExcelTap,
    this.onPdfTap,
    super.key,
  });

  final String hint;
  final TextEditingController? controller;
  final ValueChanged<String>? onChanged;
  final VoidCallback onFilterTap;
  final String filterTooltip;
  final VoidCallback? onExcelTap;
  final VoidCallback? onPdfTap;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: SearchBox(
                hint: hint,
                controller: controller,
                onChanged: onChanged,
                bottomPadding: 0,
              ),
            ),
            const SizedBox(width: 10),
            Tooltip(
              message: filterTooltip,
              child: InkWell(
                onTap: onFilterTap,
                borderRadius: BorderRadius.circular(10),
                child: Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: Theme.of(context).cardColor,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: scheme.outlineVariant),
                  ),
                  child: Icon(
                    Icons.tune_rounded,
                    size: 20,
                    color: scheme.onSurface,
                  ),
                ),
              ),
            ),
          ],
        ),
        if (onExcelTap != null || onPdfTap != null) ...[
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              if (onExcelTap != null)
                _MasterExportButton(
                  label: 'Excel',
                  icon: Icons.table_view_rounded,
                  color: const Color(0xFF15803D),
                  onTap: onExcelTap!,
                ),
              if (onExcelTap != null && onPdfTap != null)
                const SizedBox(width: 8),
              if (onPdfTap != null)
                _MasterExportButton(
                  label: 'PDF',
                  icon: Icons.picture_as_pdf_rounded,
                  color: const Color(0xFFDC2626),
                  onTap: onPdfTap!,
                ),
            ],
          ),
        ],
      ],
    );
  }
}

class _MasterExportButton extends StatelessWidget {
  const _MasterExportButton({
    required this.label,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(999),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: color.withValues(alpha: .08),
          borderRadius: BorderRadius.circular(999),
          border: Border.all(color: color.withValues(alpha: .24)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 16, color: color),
            const SizedBox(width: 6),
            Text(
              label,
              style: Theme.of(context).textTheme.labelMedium?.copyWith(
                color: color,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// class MasterLineTabs extends StatelessWidget {
//   const MasterLineTabs({
//     super.key,
//     required this.labels,
//     required this.value,
//     required this.onChanged,
//   });
//
//   final List<String> labels;
//   final int value;
//   final ValueChanged<int> onChanged;
//
//   @override
//   Widget build(BuildContext context) {
//     final theme = Theme.of(context);
//     return Container(
//       width: double.infinity,
//       margin: const EdgeInsets.fromLTRB(16, 12, 16, 0),
//       child: SingleChildScrollView(
//         scrollDirection: Axis.horizontal,
//         child: Row(
//           children: List<Widget>.generate(labels.length, (index) {
//             final selected = value == index;
//             return Padding(
//               padding: EdgeInsets.only(
//                 right: index == labels.length - 1 ? 0 : 18,
//               ),
//               child: InkWell(
//                 onTap: () => onChanged(index),
//                 borderRadius: BorderRadius.circular(4),
//                 child: Container(
//                   padding: const EdgeInsets.symmetric(vertical: 12),
//                   decoration: BoxDecoration(
//                     border: Border(
//                       bottom: BorderSide(
//                         color: selected
//                             ? theme.colorScheme.primary
//                             : Colors.transparent,
//                         width: 2,
//                       ),
//                     ),
//                   ),
//                   child: Text(
//                     labels[index],
//                     style: theme.textTheme.labelLarge?.copyWith(
//                       fontWeight: selected ? FontWeight.w800 : FontWeight.w600,
//                       color: selected
//                           ? theme.colorScheme.primary
//                           : theme.colorScheme.onSurfaceVariant,
//                     ),
//                   ),
//                 ),
//               ),
//             );
//           }),
//         ),
//       ),
//     );
//   }
// }


class MasterTextField extends StatelessWidget {
  const MasterTextField({
    super.key,
    required this.controller,
    required this.label,
    required this.hintText,
    this.keyboardType,
    this.validator,
    this.readOnly = false,
    this.onTap,
  });

  final TextEditingController controller;
  final String label;
  final String hintText;
  final TextInputType? keyboardType;
  final String? Function(String?)? validator;
  final bool readOnly;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      validator: validator,
      readOnly: readOnly,
      onTap: onTap,
      decoration: InputDecoration(
        labelText: label,
        hintText: hintText,
        isDense: true,
        floatingLabelBehavior: FloatingLabelBehavior.auto,
      ),
    );
  }
}

class MasterDropdownField<T> extends StatelessWidget {
  const MasterDropdownField({
    super.key,
    required this.label,
    required this.items,
    required this.itemLabelBuilder,
    required this.onChanged,
    this.value,
    this.hintText,
  });

  final String label;
  final List<T> items;
  final String Function(T item) itemLabelBuilder;
  final ValueChanged<T?> onChanged;
  final T? value;
  final String? hintText;

  @override
  Widget build(BuildContext context) {
    return DropdownButtonFormField2<T>(
      valueListenable: ValueNotifier<T?>(items.contains(value) ? value : null),
      onChanged: onChanged,
      isExpanded: true,
      decoration: InputDecoration(
        labelText: label,
        hintText: hintText,
        isDense: true,
        floatingLabelBehavior: FloatingLabelBehavior.auto,
      ),
      buttonStyleData: FormFieldButtonStyleData(
        height: 52,
        padding: const EdgeInsets.only(right: 8),
        decoration: BoxDecoration(borderRadius: BorderRadius.circular(16)),
      ),
      iconStyleData: const IconStyleData(
        icon: Icon(Icons.keyboard_arrow_down_rounded),
      ),
      dropdownStyleData: DropdownStyleData(
        maxHeight: 280,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          color: Theme.of(context).colorScheme.surface,
        ),
      ),
      menuItemStyleData: const MenuItemStyleData(),
      items: items
          .map(
            (item) => DropdownItem<T>(
              value: item,
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                physics: const BouncingScrollPhysics(),
                child: Text(
                  itemLabelBuilder(item),
                  maxLines: 1,
                  softWrap: false,
                ),
              ),
            ),
          )
          .toList(),
    );
  }
}

class MastersTableShell extends StatelessWidget {
  const MastersTableShell({
    super.key,
    required this.columns,
    required this.rows,
    required this.emptyText,
    this.minWidth = 920,
    this.isLoading = false,
    this.dataRowHeight = 52,
  });

  final List<DataColumn2> columns;
  final List<DataRow> rows;
  final String emptyText;
  final double minWidth;
  final bool isLoading;
  final double dataRowHeight;

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (rows.isEmpty) {
      return Center(
        child: Text(emptyText, style: Theme.of(context).textTheme.bodyMedium),
      );
    }
    return ClipRRect(
      borderRadius: BorderRadius.circular(10),
      child: Container(
        decoration: BoxDecoration(
          border: Border.all(
            color: Theme.of(context).dividerColor.withValues(alpha: .55),
          ),
          borderRadius: BorderRadius.circular(10),
        ),
        child: DataTable2(
          headingRowColor: WidgetStatePropertyAll(
            Theme.of(
              context,
            ).colorScheme.surfaceContainerHighest.withValues(alpha: .45),
          ),
          headingRowHeight: 42,
          dataRowHeight: dataRowHeight,
          horizontalMargin: 12,
          columnSpacing: 12,
          minWidth: minWidth,
          showCheckboxColumn: false,
          columns: columns,
          rows: rows,
        ),
      ),
    );
  }
}

class MasterActionButton extends StatelessWidget {
  const MasterActionButton({
    super.key,
    required this.icon,
    required this.tooltip,
    required this.color,
    this.onTap,
  });

  final IconData icon;
  final String tooltip;
  final Color color;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: tooltip,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(999),
        child: Container(
          width: 34,
          height: 34,
          decoration: BoxDecoration(
            color: color.withValues(alpha: .10),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, size: 18, color: color),
        ),
      ),
    );
  }
}

class MasterFab extends StatelessWidget {
  const MasterFab({
    super.key,
    required this.label,
    required this.onPressed,
    this.icon = Icons.add_rounded,
  });

  final String label;
  final VoidCallback onPressed;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return FloatingActionButton.extended(
      onPressed: onPressed,
      icon: Icon(icon),
      label: Text(label),
    );
  }
}

class MasterBottomSheetHandle extends StatelessWidget {
  const MasterBottomSheetHandle({super.key});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Container(
        width: 42,
        height: 4,
        decoration: BoxDecoration(
          color: Theme.of(context).dividerColor,
          borderRadius: BorderRadius.circular(999),
        ),
      ),
    );
  }
}

DataColumn2 masterColumn(
  BuildContext context,
  String title, {
  ColumnSize size = ColumnSize.M,
  double? fixedWidth,
}) {
  return DataColumn2(
    size: size,
    fixedWidth: fixedWidth,
    label: Center(
      child: Text(
        title,
        textAlign: TextAlign.center,
        style: Theme.of(context).textTheme.labelMedium?.copyWith(
          fontWeight: FontWeight.w800,
          color: Theme.of(context).colorScheme.onSurface,
        ),
      ),
    ),
  );
}

DataCell masterTextCell(
  String text, {
  FontWeight fontWeight = FontWeight.w600,
  double fontSize = 13,
}) {
  return DataCell(
    Center(
      child: Text(
        text,
        textAlign: TextAlign.center,
        style: TextStyle(fontWeight: fontWeight, fontSize: fontSize),
      ),
    ),
  );
}

class MasterSectionPadding extends StatelessWidget {
  const MasterSectionPadding({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Padding(padding: const EdgeInsets.all(AppSpacing.md), child: child);
  }
}
