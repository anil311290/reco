import 'package:flutter/material.dart';
import '../../../widgets/common/custom_text_field.dart';

WidgetStateProperty<Color?> reportTotalRowColor(BuildContext context) {
  return const WidgetStatePropertyAll(Color(0xFF23263A));
}

TextStyle? reportTotalRowTextStyle(BuildContext context) {
  return Theme.of(context).textTheme.bodyMedium?.copyWith(
    fontWeight: FontWeight.w800,
    color: Colors.white,
  );
}

class ReportFeatureItem {
  const ReportFeatureItem({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
}

class ReportFeatureCard extends StatelessWidget {
  const ReportFeatureCard({required this.item, super.key});

  final ReportFeatureItem item;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Theme.of(context).cardColor,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: item.onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: Theme.of(context).dividerColor.withValues(alpha: .45),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: item.color.withValues(alpha: .12),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: item.color.withValues(alpha: .15),
                  ),
                ),
                child: Icon(item.icon, color: item.color, size: 17),
              ),
              const SizedBox(height: 8),
              Text(
                item.title,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w700,
                  fontSize: 13.5,
                ),
              ),
              const SizedBox(height: 3),
              Expanded(
                child: Text(
                  item.subtitle,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                    height: 1.25,
                    fontSize: 10.5,
                  ),
                ),
              ),
              const SizedBox(height: 3),
              Row(
                children: <Widget>[
                  Text(
                    'Open Report',
                    style: Theme.of(context).textTheme.labelLarge?.copyWith(
                      color: item.color,
                      fontWeight: FontWeight.w700,
                      fontSize: 11.5,
                    ),
                  ),
                  const Spacer(),
                  Icon(
                    Icons.arrow_outward_rounded,
                    size: 14,
                    color: item.color,
                  ),
                ],
              ),
              const SizedBox(height: 1),
              Container(
                height: 2.5,
                width: 44,
                decoration: BoxDecoration(
                  color: item.color.withValues(alpha: .22),
                  borderRadius: BorderRadius.circular(999),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class ReportStatCard extends StatelessWidget {
  const ReportStatCard({
    required this.label,
    required this.value,
    required this.note,
    required this.color,
    this.icon,
    super.key,
  });

  final String label;
  final String value;
  final String note;
  final Color color;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .08),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withValues(alpha: .18)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              if (icon != null) ...<Widget>[
                Icon(icon, size: 14, color: color),
                const SizedBox(width: 5),
              ],
              Expanded(
                child: Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.labelLarge?.copyWith(
                    color: color,
                    fontWeight: FontWeight.w700,
                    fontSize: 12.5,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.w800,
              fontSize: 18,
              height: 1.1,
            ),
          ),
          const SizedBox(height: 3),
          Text(
            note,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
              fontSize: 11,
              height: 1.2,
            ),
          ),
        ],
      ),
    );
  }
}

class ReportSectionCard extends StatelessWidget {
  const ReportSectionCard({
    required this.title,
    required this.child,
    this.trailing,
    this.icon,
    this.iconColor,
    super.key,
  });

  final String title;
  final Widget child;
  final Widget? trailing;
  final IconData? icon;
  final Color? iconColor;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Row(
          children: <Widget>[
            Expanded(
              child: Row(
                children: <Widget>[
                  if (icon != null) ...<Widget>[
                    Icon(
                      icon,
                      size: 16,
                      color: iconColor ?? Theme.of(context).colorScheme.primary,
                    ),
                    const SizedBox(width: 8),
                  ],
                  Expanded(
                    child: Text(
                      title,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w600,
                        fontSize: 15,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            trailing ?? const SizedBox.shrink(),
          ],
        ),
        const SizedBox(height: 10),
        child,
      ],
    );
  }
}

class ReportLoadingView extends StatelessWidget {
  const ReportLoadingView({super.key});

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: Padding(
        padding: EdgeInsets.all(32),
        child: CircularProgressIndicator(),
      ),
    );
  }
}

class ReportFilterPanel extends StatelessWidget {
  const ReportFilterPanel({
    required this.title,
    required this.child,
    this.subtitle,
    this.icon,
    this.iconColor,
    super.key,
  });

  final String title;
  final String? subtitle;
  final Widget child;
  final IconData? icon;
  final Color? iconColor;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final Color accent = iconColor ?? theme.colorScheme.primary;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        gradient: LinearGradient(
          colors: <Color>[
            accent.withValues(alpha: .06),
            theme.colorScheme.surface,
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        border: Border.all(
          color: accent.withValues(alpha: .10),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              if (icon != null) ...<Widget>[
                Icon(icon, size: 16, color: accent),
                const SizedBox(width: 8),
              ],
              Expanded(
                child: Text(
                  title,
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                  ),
                ),
              ),
            ],
          ),
          if (subtitle != null) ...<Widget>[
            const SizedBox(height: 4),
            Text(
              subtitle!,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
                fontSize: 11.5,
                height: 1.25,
              ),
            ),
          ],
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }
}

class ReportDateRangeRow extends StatelessWidget {
  const ReportDateRangeRow({
    required this.fromController,
    required this.toController,
    required this.onFromTap,
    required this.onToTap,
    this.fromLabel = 'From Date',
    this.toLabel = 'To Date',
    super.key,
  });

  final TextEditingController fromController;
  final TextEditingController toController;
  final VoidCallback onFromTap;
  final VoidCallback onToTap;
  final String fromLabel;
  final String toLabel;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: <Widget>[
        Expanded(
          child: CustomTextField(
            label: fromLabel,
            controller: fromController,
            readOnly: true,
            suffixIcon: Icons.calendar_today_outlined,
            onTap: onFromTap,
            bottomPadding: 0,
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: CustomTextField(
            label: toLabel,
            controller: toController,
            readOnly: true,
            suffixIcon: Icons.calendar_today_outlined,
            onTap: onToTap,
            bottomPadding: 0,
          ),
        ),
      ],
    );
  }
}

class ReportPageTitle extends StatelessWidget {
  const ReportPageTitle({
    required this.title,
    required this.icon,
    this.color,
    super.key,
  });

  final String title;
  final IconData icon;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final Color accent = color ?? Theme.of(context).colorScheme.primary;
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: <Widget>[
        Icon(icon, size: 18, color: accent),
        const SizedBox(width: 8),
        Flexible(
          child: Text(
            title,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
              fontSize: 15,
            ),
          ),
        ),
      ],
    );
  }
}

class ReportPrimaryButton extends StatelessWidget {
  const ReportPrimaryButton({
    required this.label,
    required this.onTap,
    required this.icon,
    super.key,
  });

  final String label;
  final VoidCallback onTap;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 38,
      child: FilledButton.icon(
        onPressed: onTap,
        style: FilledButton.styleFrom(
          backgroundColor: const Color(0xFF2563EB),
          foregroundColor: Colors.white,
          elevation: 0,
          minimumSize: const Size(0, 38),
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          textStyle: Theme.of(context).textTheme.labelLarge?.copyWith(
            fontWeight: FontWeight.w700,
            fontSize: 12,
          ),
        ),
        icon: Icon(icon, size: 15),
        label: Text(label, maxLines: 1, overflow: TextOverflow.ellipsis),
      ),
    );
  }
}

class ReportSecondaryButton extends StatelessWidget {
  const ReportSecondaryButton({
    required this.label,
    required this.onTap,
    required this.icon,
    super.key,
  });

  final String label;
  final VoidCallback onTap;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    final bool isPdf = label.toLowerCase() == 'pdf';
    final Color accent = isPdf
        ? const Color(0xFFDC2626)
        : const Color(0xFF16A34A);
    return SizedBox(
      height: 38,
      child: OutlinedButton.icon(
        onPressed: onTap,
        style: OutlinedButton.styleFrom(
          foregroundColor: accent,
          backgroundColor: accent.withValues(alpha: .08),
          side: BorderSide(color: accent.withValues(alpha: .22)),
          elevation: 0,
          minimumSize: const Size(0, 38),
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          textStyle: Theme.of(context).textTheme.labelLarge?.copyWith(
            fontWeight: FontWeight.w700,
            fontSize: 12,
          ),
        ),
        icon: Icon(icon, size: 15),
        label: Text(label, maxLines: 1, overflow: TextOverflow.ellipsis),
      ),
    );
  }
}

class ReportActionBar extends StatelessWidget {
  const ReportActionBar({
    required this.children,
    super.key,
  });

  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: List<Widget>.generate(children.length, (index) {
        return Expanded(
          child: Padding(
            padding: EdgeInsets.only(
              right: index == children.length - 1 ? 0 : 8,
            ),
            child: children[index],
          ),
        );
      }),
    );
  }
}

class ReportLinkText extends StatelessWidget {
  const ReportLinkText(
    this.label, {
    this.onTap,
    this.textAlign = TextAlign.center,
    this.maxLines = 1,
    super.key,
  });

  final String label;
  final VoidCallback? onTap;
  final TextAlign textAlign;
  final int maxLines;

  @override
  Widget build(BuildContext context) {
    final style = Theme.of(context).textTheme.bodyMedium?.copyWith(
      color: onTap == null ? null : Theme.of(context).colorScheme.primary,
      fontWeight: onTap == null ? FontWeight.w500 : FontWeight.w700,
      decoration: onTap == null ? null : TextDecoration.underline,
      decorationColor: onTap == null
          ? null
          : Theme.of(context).colorScheme.primary.withValues(alpha: .55),
    );

    if (onTap == null) {
      return Text(
        label,
        maxLines: maxLines,
        overflow: TextOverflow.ellipsis,
        textAlign: textAlign,
        style: style,
      );
    }

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(6),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 2),
        child: Text(
          label,
          maxLines: maxLines,
          overflow: TextOverflow.ellipsis,
          textAlign: textAlign,
          style: style,
        ),
      ),
    );
  }
}
