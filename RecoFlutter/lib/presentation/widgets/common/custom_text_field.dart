import 'package:dropdown_button2/dropdown_button2.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class CustomTextField extends StatelessWidget {
  const CustomTextField({
    required this.label,
    this.icon,
    this.prefixIcon,
    this.requiredField = false,
    this.maxLines = 1,
    this.initialValue,
    this.controller,
    this.readOnly = false,
    this.onTap,
    this.hintText,
    this.suffixText,
    this.suffixIcon,
    this.onSuffixTap,
    this.obscureText = false,
    this.prefixText,
    this.validator,
    this.keyboardType,
    this.inputFormatters,
    this.onChanged,
    this.textInputAction,
    this.onFieldSubmitted,
    this.bottomPadding = 12,
    super.key,
  }) : assert(controller == null || initialValue == null);

  final String label;
  final IconData? icon;
  final IconData? prefixIcon;
  final bool requiredField;
  final int maxLines;
  final String? initialValue;
  final TextEditingController? controller;
  final bool readOnly;
  final VoidCallback? onTap;
  final String? hintText;
  final String? suffixText;
  final IconData? suffixIcon;
  final VoidCallback? onSuffixTap;
  final bool obscureText;
  final String? prefixText;
  final FormFieldValidator<String>? validator;
  final TextInputType? keyboardType;
  final List<TextInputFormatter>? inputFormatters;
  final ValueChanged<String>? onChanged;
  final TextInputAction? textInputAction;
  final ValueChanged<String>? onFieldSubmitted;
  final double bottomPadding;

  @override
  Widget build(BuildContext context) {
    final effectivePrefixIcon = prefixIcon ?? icon;
    return Padding(
      padding: EdgeInsets.only(bottom: bottomPadding),
      child: TextFormField(
        controller: controller,
        initialValue: initialValue,
        maxLines: maxLines,
        obscureText: obscureText,
        readOnly: readOnly,
        onTap: onTap,
        validator: validator,
        keyboardType: keyboardType,
        inputFormatters: inputFormatters,
        onChanged: onChanged,
        textInputAction: textInputAction,
        onFieldSubmitted: onFieldSubmitted,
        decoration: InputDecoration(
          labelText: requiredField ? '$label *' : label,
          hintText: hintText,
          prefixIcon: effectivePrefixIcon == null
              ? null
              : Icon(effectivePrefixIcon, size: 18),
          suffixIcon: suffixIcon == null
              ? null
              : IconButton(
                  onPressed: onSuffixTap,
                  icon: Icon(suffixIcon, size: 18),
                ),
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
    this.isLoading = false,
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
  final bool isLoading;
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
          widget.isLoading
              ? 'Loading ${widget.label}...'
              : widget.hint ?? 'Select ${widget.label}',
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
        onChanged: (widget.enabled && !widget.isLoading)
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
          icon: widget.isLoading
              ? SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: scheme.primary,
                  ),
                )
              : Icon(
                  Icons.keyboard_arrow_down_rounded,
                  color: scheme.onSurfaceVariant,
                ),
          iconSize: widget.isLoading ? 18 : 22,
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
