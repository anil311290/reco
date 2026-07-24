import 'package:flutter/material.dart';

import '../../../core/utils/app_spacing.dart';
import '../../widgets/feedback/section_card.dart';
import '../../widgets/layout/app_scaffold.dart';

class PlaceholderShellScreen extends StatelessWidget {
  const PlaceholderShellScreen({
    super.key,
    required this.title,
    required this.subtitle,
    required this.icon,
  });

  final String title;
  final String subtitle;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: title,
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 520),
          child: SectionCard(
            title: title,
            subtitle: subtitle,
            child: Column(
              children: <Widget>[
                Icon(
                  icon,
                  size: 54,
                  color: Theme.of(context).colorScheme.primary,
                ),
                const SizedBox(height: AppSpacing.md),
                Text(
                  'Bottom navigation shell old app jaisa wire ho gaya hai.',
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
