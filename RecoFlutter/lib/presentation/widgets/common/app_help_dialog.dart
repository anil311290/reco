import 'package:flutter/material.dart';

class AppHelpDialogSection {
  const AppHelpDialogSection({
    required this.title,
    required this.message,
  });

  final String title;
  final String message;
}

class AppHelpDialogButton extends StatelessWidget {
  const AppHelpDialogButton({
    super.key,
    required this.title,
    required this.sections,
    this.label,
    this.tooltip,
    this.icon = Icons.help_outline_rounded,
  });

  final String title;
  final List<AppHelpDialogSection> sections;
  final String? label;
  final String? tooltip;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final child = label == null
        ? Icon(
            icon,
            size: 20,
            color: theme.colorScheme.primary,
          )
        : Row(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              Icon(
                icon,
                size: 18,
                color: theme.colorScheme.primary,
              ),
              const SizedBox(width: 6),
              Text(
                label!,
                style: theme.textTheme.labelLarge?.copyWith(
                  color: theme.colorScheme.primary,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          );

    return Tooltip(
      message: tooltip ?? title,
      child: InkWell(
        onTap: () => _show(context),
        borderRadius: BorderRadius.circular(999),
        child: Padding(
          padding: EdgeInsets.symmetric(
            horizontal: label == null ? 4 : 6,
            vertical: 4,
          ),
          child: child,
        ),
      ),
    );
  }

  Future<void> _show(BuildContext context) {
    final theme = Theme.of(context);
    return showDialog<void>(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          insetPadding: const EdgeInsets.all(14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          titlePadding: const EdgeInsets.fromLTRB(16, 16, 8, 8),
          contentPadding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
          title: Row(
            children: <Widget>[
              Expanded(
                child: Text(
                  title,
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              IconButton(
                onPressed: () => Navigator.of(dialogContext).pop(),
                icon: const Icon(Icons.close_rounded),
                visualDensity: VisualDensity.compact,
              ),
            ],
          ),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: sections
                  .map(
                    (section) => Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(10),
                          color: theme.colorScheme.surfaceContainerHighest
                              .withValues(alpha: .35),
                          border: Border.all(
                            color: theme.colorScheme.outlineVariant
                                .withValues(alpha: .55),
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: <Widget>[
                            Text(
                              section.title,
                              style: theme.textTheme.titleSmall?.copyWith(
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              section.message,
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: theme.colorScheme.onSurfaceVariant,
                                height: 1.4,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  )
                  .toList(),
            ),
          ),
        );
      },
    );
  }
}
