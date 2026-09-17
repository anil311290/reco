import 'package:flutter/material.dart';

class FilterSheetActions extends StatelessWidget {
  const FilterSheetActions({
    super.key,
    required this.onClear,
    required this.onApply,
    this.gap = 10,
  });

  final Future<void> Function() onClear;
  final Future<void> Function() onApply;
  final double gap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final primary = theme.colorScheme.primary;
    return Row(
      children: <Widget>[
        Expanded(
          child: OutlinedButton(
            onPressed: () async {
              await onClear();
              if (context.mounted) Navigator.of(context).pop();
            },
            style: OutlinedButton.styleFrom(
              minimumSize: const Size.fromHeight(45),
              side: BorderSide(color: primary, width: 1.2),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
            child: const Text(
              'Clear',
              style: TextStyle(fontWeight: FontWeight.w700),
            ),
          ),
        ),
        SizedBox(width: gap),
        Expanded(
          child: FilledButton(
            onPressed: () async {
              await onApply();
              if (context.mounted) Navigator.of(context).pop();
            },
            style: FilledButton.styleFrom(
              minimumSize: const Size.fromHeight(45),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
            child: const Text(
              'Apply',
              style: TextStyle(fontWeight: FontWeight.w800),
            ),
          ),
        ),
      ],
    );
  }
}
