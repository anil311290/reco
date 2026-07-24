import 'package:flutter/material.dart';

class AppTextStyles {
  const AppTextStyles._();

  static TextStyle pageTitle(BuildContext context) => Theme.of(
    context,
  ).textTheme.titleLarge!.copyWith(fontWeight: FontWeight.w800);

  static TextStyle sectionTitle(BuildContext context) => Theme.of(
    context,
  ).textTheme.titleMedium!.copyWith(fontSize: 14, fontWeight: FontWeight.w600);

  static TextStyle metricLabel(BuildContext context) =>
      Theme.of(context).textTheme.labelMedium!.copyWith(
        color: Theme.of(context).colorScheme.onSurfaceVariant,
        fontWeight: FontWeight.w700,
        letterSpacing: .35,
      );

  static TextStyle metricValue(BuildContext context) => Theme.of(context)
      .textTheme
      .headlineSmall!
      .copyWith(fontSize: 20, fontWeight: FontWeight.w500);

  static TextStyle caption(BuildContext context) =>
      Theme.of(context).textTheme.bodySmall!.copyWith(
        color: Theme.of(
          context,
        ).colorScheme.onSurfaceVariant.withValues(alpha: .6),
      );
}
