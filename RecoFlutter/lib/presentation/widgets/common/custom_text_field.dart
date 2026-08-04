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
    final bool useCompactSuffixIcon =
        suffixIcon != null && readOnly && onTap != null && onSuffixTap == null;

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
          contentPadding: EdgeInsets.symmetric(horizontal: 10,vertical: 10),
          labelText: requiredField ? '$label *' : label,
          hintText: hintText,
          prefixIcon: effectivePrefixIcon == null
              ? null
              : Icon(effectivePrefixIcon, size: 18),
          suffixIcon: suffixIcon == null
              ? null
              : InkWell(

                  onTap: onSuffixTap,

                  child: Padding(
                    padding:EdgeInsets.symmetric(
                      horizontal: useCompactSuffixIcon ? 6 : 8,
                    ),
                    child: Icon(suffixIcon, size: 18),
                  ),
                ),
          suffixIconConstraints:  BoxConstraints(

            minWidth: useCompactSuffixIcon ? 26 : 40,
            minHeight: useCompactSuffixIcon ? 30 : 40,
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
    this.menuItemBuilder,
    this.searchTextBuilder,
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
  final Widget Function(BuildContext context, T item)? menuItemBuilder;
  final String Function(T item)? searchTextBuilder;
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

  Widget _buildScrollableLabel(
    BuildContext context,
    String text,
  ) {
    final scheme = Theme.of(context).colorScheme;
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      physics: const BouncingScrollPhysics(),
      child: Text(
        text,
        maxLines: 1,
        softWrap: false,
        style: TextStyle(
          color: scheme.onSurface,
          fontSize: 14,
          fontWeight: FontWeight.normal,
        ),
      ),
    );
  }

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
    final selectedCount = widget.value == null
        ? 0
        : widget.items.where((item) => item == widget.value).length;
    final safeValue = selectedCount == 1 ? widget.value : null;
    if (_valueNotifier.value != safeValue) {
      _valueNotifier.value = safeValue;
    }
    final dropdownItems = widget.items
        .map(
          (item) => DropdownItem<T>(
        value: item,
        // height: widget.menuItemBuilder != null ? null : 44,
        child: widget.menuItemBuilder != null
            ? widget.menuItemBuilder!(context, item)
            : Padding(
                padding: EdgeInsets.only(left: 0.0),
                child: _buildScrollableLabel(
                  context,
                  widget.itemLabelBuilder(item),
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
                  Icons.expand_more_rounded,
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
              ._searchableText(item.value as T)
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

extension on CustomDropdown {
  String _searchableText<T>(T item) {
    return (searchTextBuilder as String Function(T item)?)?.call(item) ??
        (itemLabelBuilder as String Function(T item)).call(item);
  }
}
