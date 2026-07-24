import 'package:dropdown_button2/dropdown_button2.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../theme/app_colors.dart';

class ErpCard extends StatelessWidget {
  const ErpCard({
    required this.child,
    this.padding = const EdgeInsets.all(16),
    this.onTap,
    super.key,
  });

  final Widget child;
  final EdgeInsetsGeometry padding;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Material(
      color: isDark ? const Color(0xFF172033) : AppColors.card,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: padding,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: isDark ? const Color(0xFF263244) : AppColors.border,
            ),
          ),
          child: child,
        ),
      ),
    );
  }
}

class SectionTitle extends StatelessWidget {
  const SectionTitle(this.title, {this.action, super.key});

  final String title;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        children: [
          Expanded(
            child: Text(title, style: Theme.of(context).textTheme.titleMedium),
          ),
          action ?? const SizedBox.shrink(),
        ],
      ),
    );
  }
}

class MoneyText extends StatelessWidget {
  const MoneyText(
    this.value, {
    this.color,
    this.size = 18,
    this.fontWeight = FontWeight.w800,
    super.key,
  });

  final String value;
  final Color? color;
  final double size;
  final FontWeight fontWeight;

  @override
  Widget build(BuildContext context) {
    return Text(
      value,
      style: TextStyle(
        color: color ?? Theme.of(context).colorScheme.onSurface,
        fontSize: size,
        fontWeight: fontWeight,
      ),
    );
  }
}

class MetricTile extends StatelessWidget {
  const MetricTile({
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
    super.key,
  });

  final String title;
  final String value;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return ErpCard(
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 28,
            height: 28,
            decoration: BoxDecoration(
              color: color.withValues(alpha: .12),
              borderRadius: BorderRadius.circular(6),
            ),
            child: Icon(icon, size: 16, color: color),
          ),
          const SizedBox(height: 10),
          Text(title, style: Theme.of(context).textTheme.bodySmall),
          const SizedBox(height: 5),
          MoneyText(value, color: color),
        ],
      ),
    );
  }
}

class CustomTextField extends StatelessWidget {
  const CustomTextField({
    required this.label,
    this.icon,
    this.requiredField = false,
    this.maxLines = 1,
    this.initialValue,
    this.controller,
    this.readOnly = false,
    this.onTap,
    this.hintText,
    this.suffixText,
    this.suffixIcon,
    this.prefixText,
    this.validator,
    this.keyboardType,
    this.inputFormatters,
    this.onChanged,
    super.key,
  }) : assert(controller == null || initialValue == null);

  final String label;
  final IconData? icon;
  final bool requiredField;
  final int maxLines;
  final String? initialValue;
  final TextEditingController? controller;
  final bool readOnly;
  final VoidCallback? onTap;
  final String? hintText;
  final String? suffixText;
  final IconData? suffixIcon;
  final String? prefixText;
  final FormFieldValidator<String>? validator;
  final TextInputType? keyboardType;
  final List<TextInputFormatter>? inputFormatters;
  final ValueChanged<String>? onChanged;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextFormField(
        controller: controller,
        initialValue: initialValue,
        maxLines: maxLines,
        readOnly: readOnly,
        onTap: onTap,
        validator: validator,
        keyboardType: keyboardType,
        inputFormatters: inputFormatters,
        onChanged: onChanged,
        decoration: InputDecoration(
          labelText: requiredField ? '$label *' : label,
          hintText: hintText,
          prefixIcon: icon == null ? null : Icon(icon, size: 18),
          suffixIcon: suffixIcon == null ? null : Icon(suffixIcon, size: 18),
          suffixText: suffixText,
          prefixText: prefixText,
        ),
      ),
    );
  }
}

class CustomDropdown<T> extends StatefulWidget {
  const CustomDropdown({
    required this.label,
    required this.value,
    required this.items,
    required this.itemLabelBuilder,
    required this.onChanged,
    this.hint,
    this.validator,
    this.requiredField = false,
    this.enableSearch = true,
    this.enabled = true,
    this.height = 48,
    this.bottomPadding = 12,
    super.key,
  });

  final String label;
  final T? value;
  final List<T> items;
  final String Function(T item) itemLabelBuilder;
  final ValueChanged<T?> onChanged;
  final String? hint;
  final FormFieldValidator<T>? validator;
  final bool requiredField;
  final bool enableSearch;
  final bool enabled;
  final double height;
  final double bottomPadding;

  @override
  State<CustomDropdown<T>> createState() => _CustomDropdownState<T>();
}

class _CustomDropdownState<T> extends State<CustomDropdown<T>> {
  late final ValueNotifier<T?> _valueNotifier;
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _valueNotifier = ValueNotifier<T?>(widget.value);
  }

  @override
  void didUpdateWidget(covariant CustomDropdown<T> oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.value != widget.value) {
      _valueNotifier.value = widget.value;
    }
  }

  @override
  void dispose() {
    _valueNotifier.dispose();
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final dropdownItems = widget.items
        .map(
          (item) => DropdownItem<T>(
            value: item,
            height: 44,
            child: Padding(
              padding: EdgeInsets.only(left: 0.0),
              child: Text(
                widget.itemLabelBuilder(item),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: scheme.onSurface,
                  fontSize: 14,
                  fontWeight: FontWeight.normal,
                ),
              ),
            ),
          ),
        )
        .toList();

    return Padding(
      padding: EdgeInsets.only(bottom: widget.bottomPadding),
      child: DropdownButtonFormField2<T>(
        valueListenable: _valueNotifier,
        isExpanded: true,
        decoration: InputDecoration(
          labelText: widget.requiredField ? '${widget.label} *' : widget.label,
          contentPadding: const EdgeInsets.only(left: 12, right: 10),
        ),
        hint: Text(
          widget.hint ?? 'Select ${widget.label}',
          style: TextStyle(
            color: scheme.onSurfaceVariant,
            fontSize: 14,
            fontWeight: FontWeight.w500,
          ),
        ),
        style: TextStyle(
          color: widget.enabled ? scheme.onSurface : scheme.onSurfaceVariant,
          fontSize: 14,
          fontWeight: FontWeight.normal,
        ),
        items: dropdownItems,
        onChanged: widget.enabled
            ? (value) {
                _valueNotifier.value = value;
                widget.onChanged(value);
              }
            : null,
        validator: widget.validator,
        buttonStyleData: FormFieldButtonStyleData(
          height: widget.height,
          padding: EdgeInsets.zero,
        ),

        iconStyleData: IconStyleData(
          icon: Icon(
            Icons.keyboard_arrow_down_rounded,
            color: scheme.onSurfaceVariant,
          ),
          iconSize: 22,
        ),
        dropdownStyleData: DropdownStyleData(
          maxHeight: MediaQuery.sizeOf(context).height * .4,
          padding: const EdgeInsets.symmetric(vertical: 6),
          offset: const Offset(0, -2),
          elevation: 8,
          decoration: BoxDecoration(
            color: Theme.of(context).cardColor,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: scheme.outlineVariant),
          ),
          dropdownBuilder: (context, child) {
            return Padding(padding: EdgeInsets.only(left: 16.0), child: child);
          },
        ),

        menuItemStyleData: MenuItemStyleData(
          padding: EdgeInsets.symmetric(horizontal: 0),
        ),
        dropdownSearchData: widget.enableSearch
            ? DropdownSearchData<T>(
                searchController: _searchController,
                searchBarWidgetHeight: 58,
                searchBarWidget: Padding(
                  padding: const EdgeInsets.all(8),
                  child: TextField(
                    controller: _searchController,
                    style: TextStyle(
                      color: scheme.onSurface,
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                    ),
                    decoration: InputDecoration(
                      hintText: 'Search...',
                      hintStyle: TextStyle(
                        color: scheme.onSurfaceVariant,
                        fontWeight: FontWeight.w500,
                      ),
                      prefixIcon: const Icon(Icons.search, size: 20),
                      isDense: true,
                      contentPadding: const EdgeInsets.symmetric(vertical: 10),
                    ),
                  ),
                ),
                searchMatchFn: (item, searchValue) => widget
                    .itemLabelBuilder(item.value as T)
                    .toLowerCase()
                    .contains(searchValue.toLowerCase()),
              )
            : null,
        onMenuStateChange: (isOpen) {
          if (!isOpen) _searchController.clear();
        },
      ),
    );
  }
}

class AppFilterDropdown<T> extends StatelessWidget {
  const AppFilterDropdown({
    required this.value,
    required this.items,
    required this.itemLabelBuilder,
    required this.onChanged,
    this.hint = 'Select',
    this.height = 40,
    this.enabled = true,
    super.key,
  });

  final T? value;
  final List<T> items;
  final String Function(T item) itemLabelBuilder;
  final ValueChanged<T?> onChanged;
  final String hint;
  final double height;
  final bool enabled;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final selectedText = value == null ? hint : itemLabelBuilder(value as T);

    return PopupMenuButton<T>(
      enabled: enabled,
      tooltip: hint,
      initialValue: value,
      onSelected: onChanged,
      itemBuilder: (context) => items
          .map(
            (item) => PopupMenuItem<T>(
              value: item,
              height: 38,
              child: Text(
                itemLabelBuilder(item),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: scheme.onSurface,
                  fontSize: 13,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
          )
          .toList(),
      child: Container(
        height: height,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(
          color: Theme.of(context).cardColor,
          borderRadius: BorderRadius.circular(9),
          border: Border.all(color: scheme.outlineVariant),
        ),
        child: Row(
          children: [
            Expanded(
              child: Text(
                selectedText,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: enabled ? scheme.onSurface : scheme.onSurfaceVariant,
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
            const SizedBox(width: 6),
            Icon(
              Icons.keyboard_arrow_down_rounded,
              size: 18,
              color: scheme.onSurfaceVariant,
            ),
          ],
        ),
      ),
    );
  }
}

class PrimaryButton extends StatelessWidget {
  const PrimaryButton({
    required this.label,
    this.icon,
    this.color,
    this.outlined = false,
    this.onPressed,
    super.key,
  });

  final String label;
  final IconData? icon;
  final Color? color;
  final bool outlined;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    final buttonColor = color ?? Theme.of(context).colorScheme.primary;
    final child = Row(
      mainAxisAlignment: MainAxisAlignment.center,
      mainAxisSize: MainAxisSize.min,
      children: [
        if (icon != null) ...[Icon(icon, size: 18), const SizedBox(width: 8)],
        Flexible(child: Text(label, overflow: TextOverflow.ellipsis)),
      ],
    );

    if (outlined) {
      return OutlinedButton(
        onPressed: onPressed,
        style: OutlinedButton.styleFrom(
          foregroundColor: buttonColor,
          side: BorderSide(color: buttonColor),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
          minimumSize: const Size.fromHeight(48),
        ),
        child: child,
      );
    }

    return FilledButton(
      onPressed: onPressed,
      style: FilledButton.styleFrom(
        backgroundColor: buttonColor,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        minimumSize: const Size.fromHeight(48),
      ),
      child: child,
    );
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

class CompactSearchFilterBar extends StatelessWidget {
  const CompactSearchFilterBar({
    required this.hint,
    required this.onFilterTap,
    this.controller,
    this.onChanged,
    this.filterTooltip = 'Filters',
    super.key,
  });

  final String hint;
  final TextEditingController? controller;
  final ValueChanged<String>? onChanged;
  final VoidCallback onFilterTap;
  final String filterTooltip;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Row(
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
    );
  }
}

class FilterSheetActions extends StatelessWidget {
  const FilterSheetActions({
    required this.onClear,
    required this.onApply,
    this.applyLabel = 'Apply',
    this.clearLabel = 'Clear',
    super.key,
  });

  final VoidCallback onClear;
  final VoidCallback onApply;
  final String applyLabel;
  final String clearLabel;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: PrimaryButton(
            label: clearLabel,
            outlined: true,
            onPressed: onClear,
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: PrimaryButton(label: applyLabel, onPressed: onApply),
        ),
      ],
    );
  }
}

class AppResponsiveRow extends StatelessWidget {
  const AppResponsiveRow({
    required this.children,
    this.flexes,
    this.spacing = 12,
    this.runSpacing = 0,
    this.breakpoint = 720,
    super.key,
  });

  final List<Widget> children;
  final List<int>? flexes;
  final double spacing;
  final double runSpacing;
  final double breakpoint;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        if (constraints.maxWidth < breakpoint) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: List.generate(children.length, (index) {
              return Padding(
                padding: EdgeInsets.only(
                  bottom: index == children.length - 1 ? 0 : runSpacing,
                ),
                child: children[index],
              );
            }),
          );
        }

        return Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: List.generate(children.length, (index) {
            return Expanded(
              flex: flexes == null || index >= flexes!.length
                  ? 1
                  : flexes![index],
              child: Padding(
                padding: EdgeInsets.only(
                  right: index == children.length - 1 ? 0 : spacing,
                ),
                child: children[index],
              ),
            );
          }),
        );
      },
    );
  }
}

class StatusChip extends StatelessWidget {
  const StatusChip({required this.label, required this.color, super.key});

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontSize: 12,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }
}
