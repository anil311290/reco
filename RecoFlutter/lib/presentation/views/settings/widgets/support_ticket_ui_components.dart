import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';

import '../../../../core/utils/app_date_formatter.dart';

class SupportHeroCard extends StatelessWidget {
  const SupportHeroCard({
    required this.onCreateTicket,
    super.key,
  });

  final VoidCallback onCreateTicket;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final primary = theme.colorScheme.primary;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: primary.withValues(alpha: .14)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            children: <Widget>[
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: primary.withValues(alpha: .08),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(
                  Icons.support_agent_rounded,
                  color: primary,
                  size: 18,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Customer Support',
                  style: theme.textTheme.titleMedium?.copyWith(
                    color: primary,
                    fontWeight: FontWeight.w700,
                    fontSize: 14,
                  ),
                ),
              ),
              FilledButton.icon(
                onPressed: onCreateTicket,
                icon: const Icon(Icons.add_rounded, size: 16),
                label: const Text('Create'),
                style: FilledButton.styleFrom(
                  visualDensity: VisualDensity.compact,
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            'Raise a ticket and continue the same thread.',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
              fontSize: 14,
              height: 1.15,
            ),
          ),
          // const SizedBox(height: 4),
          // Text(
          //   'Create a ticket here for billing, technical issues, feature requests, or onboarding help.',
          //   style: theme.textTheme.bodySmall?.copyWith(
          //     color: theme.colorScheme.onSurfaceVariant,
          //     height: 1.3,
          //   ),
          // ),
        ],
      ),
    );
  }
}

class SupportStatItem {
  const SupportStatItem({
    required this.label,
    required this.value,
    required this.icon,
    required this.onTap,
    this.color,
    this.isActive = false,
  });

  final String label;
  final int value;
  final IconData icon;
  final VoidCallback onTap;
  final Color? color;
  final bool isActive;
}

class SupportStatsRow extends StatelessWidget {
  const SupportStatsRow({
    required this.items,
    super.key,
  });

  final List<SupportStatItem> items;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 40,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: items.length,
        separatorBuilder: (_, _) => const SizedBox(width: 8),
        itemBuilder: (_, index) => _SupportStatCard(item: items[index]),
      ),
    );
  }
}

class _SupportStatCard extends StatelessWidget {
  const _SupportStatCard({required this.item});

  final SupportStatItem item;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final color = item.color ?? theme.colorScheme.primary;

    return InkWell(
      onTap: item.onTap,
      borderRadius: BorderRadius.circular(10),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        width: 120,
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
        decoration: BoxDecoration(
          color: item.isActive
              ? color.withValues(alpha: .1)
              : theme.cardColor,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: item.isActive
                ? color.withValues(alpha: .28)
                : theme.dividerColor.withValues(alpha: .42),
          ),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Row(
              children: <Widget>[
                Icon(item.icon, size: 12, color: color),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    item.label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: item.isActive
                          ? color
                          : theme.colorScheme.onSurfaceVariant,
                      fontWeight: item.isActive ? FontWeight.w700 : FontWeight.w600,
                      fontSize: 10.5,
                    ),
                  ),
                ),

                Text(
                  item.value.toString(),
                  style: theme.textTheme.labelLarge?.copyWith(
                    fontWeight: FontWeight.w800,
                    fontSize: 16.5,
                    color: item.isActive ? color : theme.colorScheme.onSurface,
                  ),
                ),
                const SizedBox(width: 6),
              ],
            ),


          ],
        ),
      ),
    );
  }
}

class SupportTicketCard extends StatelessWidget {
  const SupportTicketCard({
    required this.ticket,
    required this.onTap,
    super.key,
  });

  final Map<String, dynamic> ticket;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final category = (ticket['category'] ?? 'general').toString();
    final priority = (ticket['priority'] ?? 'normal').toString();
    final status = (ticket['status'] ?? 'open').toString();

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Ink(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: theme.cardColor,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: theme.dividerColor.withValues(alpha: .42),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Row(
                children: <Widget>[
                  _CategoryAvatar(category: category),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Text(
                          ticket['ticket_number']?.toString() ?? 'Draft Ticket',
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: theme.colorScheme.primary,
                            fontWeight: FontWeight.w700,
                            fontSize: 11,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          ticket['subject']?.toString() ?? 'Support Ticket',
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w700,
                            height: 1.12,
                            fontSize: 14,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  Icon(
                    Icons.chevron_right_rounded,
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Wrap(
                spacing: 6,
                runSpacing: 6,
                children: <Widget>[
                  SupportChip(
                    label: _formatLabel(category),
                    icon: _categoryIcon(category),
                    color: theme.colorScheme.primary,
                  ),
                  SupportChip(
                    label: _formatLabel(priority),
                    icon: FontAwesomeIcons.bolt.data,
                    color: _priorityColor(priority),
                  ),
                  SupportChip(
                    label: _formatLabel(status),
                    icon: _statusIcon(status),
                    color: _statusColor(status),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                children: <Widget>[
                  Icon(
                    Icons.access_time_rounded,
                    size: 15,
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      _formatDate(ticket['last_message_at'] ?? ticket['created_at']),
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ),
                  Text(
                    'Open Chat',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.primary,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class SupportTicketHeader extends StatelessWidget {
  const SupportTicketHeader({
    required this.ticket,
    super.key,
  });

  final Map<String, dynamic> ticket;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final status = (ticket['status'] ?? 'open').toString();
    final priority = (ticket['priority'] ?? 'normal').toString();
    final category = (ticket['category'] ?? 'general').toString();
    final accent = _statusColor(status);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: accent.withValues(alpha: .16)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: scheme.surface.withValues(alpha: .78),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: <Widget>[
                          Icon(
                            _categoryIcon(category),
                            size: 12,
                            color: accent,
                          ),
                          const SizedBox(width: 6),
                          Text(
                            _formatLabel(category),
                            style: theme.textTheme.labelMedium?.copyWith(
                              color: accent,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      ticket['subject']?.toString() ?? 'Support Ticket',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                        height: 1.08,
                        fontSize: 15,
                      ),
                    ),
                    const SizedBox(height: 5),
                    Text(
                      ticket['ticket_number']?.toString() ?? 'Draft Ticket',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: accent,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 10),
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: accent.withValues(alpha: .12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  FontAwesomeIcons.headset.data,
                  color: accent,
                  size: 15,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: <Widget>[
              SupportChip(
                label: _formatLabel(status),
                icon: _statusIcon(status),
                color: _statusColor(status),
              ),
              SupportChip(
                label: _formatLabel(priority),
                icon: FontAwesomeIcons.bolt.data,
                color: _priorityColor(priority),
              ),
              SupportChip(
                label: _formatLabel(category),
                icon: _categoryIcon(category),
                color: scheme.primary,
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: <Widget>[
              Expanded(
                child: _SupportHeaderMeta(
                  label: 'Updated',
                  value: _formatDate(
                    ticket['last_message_at'] ?? ticket['created_at'],
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _SupportHeaderMeta(
                  label: 'Priority',
                  value: _formatLabel(priority),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class SupportConversationCard extends StatelessWidget {
  const SupportConversationCard({
    required this.messages,
    super.key,
  });

  final List<Map<String, dynamic>> messages;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: EdgeInsets.zero,
      decoration: const BoxDecoration(
        color: Colors.transparent,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: messages.isEmpty
            ? <Widget>[
                Text(
                  'Conversation',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 28),
                  child: Text(
                    'No messages yet.',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ),
              ]
            : <Widget>[
                Padding(
                  padding: const EdgeInsets.only(bottom: 10, left: 2, right: 2),
                  child: Row(
                    children: <Widget>[
                      Text(
                        'Conversation',
                        style: theme.textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w700,
                          fontSize: 14,
                        ),
                      ),
                      const Spacer(),
                      Text(
                        '${messages.length} message${messages.length == 1 ? '' : 's'}',
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
                ...List<Widget>.generate(messages.length, (index) {
                  final message = messages[index];
                  return Padding(
                    padding: EdgeInsets.only(
                      bottom: index == messages.length - 1 ? 0 : 10,
                    ),
                    child: _MessageBubble(
                      message: message,
                      showConnector: index != messages.length - 1,
                    ),
                  );
                }),
              ],
      ),
    );
  }
}

class _MessageBubble extends StatelessWidget {
  const _MessageBubble({
    required this.message,
    required this.showConnector,
  });

  final Map<String, dynamic> message;
  final bool showConnector;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isMine = _isMine(message);
    final accent = isMine
        ? theme.colorScheme.primary
        : theme.colorScheme.secondary;
    final bubbleColor = isMine
        ? Color.alphaBlend(
            theme.colorScheme.primary.withValues(alpha: .94),
            theme.colorScheme.primaryContainer,
          )
        : Color.alphaBlend(
            theme.colorScheme.surfaceContainerHighest.withValues(alpha: .82),
            theme.cardColor,
          );
    final textColor =
        isMine ? theme.colorScheme.onPrimary : theme.colorScheme.onSurface;

    return Row(
      mainAxisAlignment:
          isMine ? MainAxisAlignment.end : MainAxisAlignment.start,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        if (!isMine)
          Padding(
            padding: const EdgeInsets.only(top: 4, right: 10),
            child: _MessageAvatar(
              accent: accent,
              label: _senderName(message),
            ),
          ),
        ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 300),
          child: Column(
            crossAxisAlignment:
                isMine ? CrossAxisAlignment.end : CrossAxisAlignment.start,
            children: <Widget>[
              Container(
                padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
                decoration: BoxDecoration(
                  color: bubbleColor,
                  borderRadius: BorderRadius.only(
                    topLeft: const Radius.circular(14),
                    topRight: const Radius.circular(14),
                    bottomLeft: Radius.circular(isMine ? 14 : 6),
                    bottomRight: Radius.circular(isMine ? 6 : 14),
                  ),
                  border: isMine
                      ? null
                      : Border.all(
                          color: theme.dividerColor.withValues(alpha: .26),
                        ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: <Widget>[
                        Flexible(
                          child: Text(
                            _senderName(message),
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: textColor.withValues(alpha: .92),
                              fontWeight: FontWeight.w800,
                              letterSpacing: .1,
                            ),
                          ),
                        ),
                        if (message['is_internal'] == true) ...<Widget>[
                          const SizedBox(width: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 3,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.amber.withValues(alpha: .18),
                              borderRadius: BorderRadius.circular(999),
                            ),
                            child: Text(
                              'Internal',
                              style: theme.textTheme.labelSmall?.copyWith(
                                color: Colors.amber.shade900,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      message['message']?.toString() ?? '',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: textColor,
                        height: 1.35,
                        fontWeight: FontWeight.w400,
                        fontSize: 13,
                      ),
                    ),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.only(top: 7, left: 2, right: 2),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: <Widget>[
                    if (!isMine && showConnector)
                      Container(
                        width: 12,
                        height: 1.4,
                        margin: const EdgeInsets.only(right: 8),
                        color: accent.withValues(alpha: .28),
                      ),
                    Text(
                      _formatDate(message['created_at']),
                      style: theme.textTheme.labelSmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        if (isMine)
          Padding(
            padding: const EdgeInsets.only(top: 4, left: 10),
            child: _MessageAvatar(
              accent: accent,
              label: 'You',
              isMine: true,
            ),
          ),
      ],
    );
  }
}

class SupportReplyComposer extends StatelessWidget {
  const SupportReplyComposer({
    required this.controller,
    required this.canReply,
    required this.isSending,
    required this.onSend,
    required this.status,
    required this.isDraftTicket,
    super.key,
  });

  final TextEditingController controller;
  final bool canReply;
  final bool isSending;
  final VoidCallback onSend;
  final String status;
  final bool isDraftTicket;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Container(
      padding: const EdgeInsets.fromLTRB(14, 8, 14, 12),
      decoration: BoxDecoration(
        color: Color.alphaBlend(
          scheme.surface.withValues(alpha: .86),
          theme.scaffoldBackgroundColor,
        ),
        border: Border(
          top: BorderSide(color: theme.dividerColor.withValues(alpha: .25)),
        ),
        boxShadow: <BoxShadow>[
          BoxShadow(
            color: Colors.black.withValues(alpha: .025),
            blurRadius: 14,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          if (status == 'closed')
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _ComposerNotice(
                text: 'This ticket is closed. Open a new ticket if you need more help.',
                color: Colors.grey.shade700,
              ),
            ),
          if (isDraftTicket)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _ComposerNotice(
                text: 'Draft ticket sync hone ke baad chat reply fully available hoga.',
                color: scheme.primary,
              ),
            ),
          Container(
            padding: const EdgeInsets.fromLTRB(10, 8, 10, 8),
            decoration: BoxDecoration(
              color: theme.cardColor,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: scheme.outlineVariant.withValues(alpha: .5),
              ),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: <Widget>[
                Container(
                  width: 36,
                  height: 36,
                  margin: const EdgeInsets.only(right: 10, bottom: 2),
                  decoration: BoxDecoration(
                    color: scheme.primary.withValues(alpha: .1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(
                    Icons.chat_bubble_outline_rounded,
                    size: 18,
                    color: scheme.primary,
                  ),
                ),
                Expanded(
                  child: TextField(
                    controller: controller,
                    minLines: 1,
                    maxLines: 5,
                    enabled: canReply && !isSending,
                    style: theme.textTheme.bodyMedium?.copyWith(
                      height: 1.35,
                    ),
                    decoration: InputDecoration(
                      hintText: 'Reply to support…',
                      hintStyle: theme.textTheme.bodyMedium?.copyWith(
                        color: scheme.onSurfaceVariant,
                      ),
                      isDense: true,
                      border: InputBorder.none,
                      enabledBorder: InputBorder.none,
                      focusedBorder: InputBorder.none,
                      disabledBorder: InputBorder.none,
                      contentPadding: const EdgeInsets.only(
                        left: 0,
                        right: 6,
                        bottom: 8,
                        top: 8,
                      ),
                    ),
                  ),
                ),
                SizedBox(
                  height: 46,
                  width: 46,
                  child: FilledButton(
                    onPressed: canReply && !isSending ? onSend : null,
                    style: FilledButton.styleFrom(
                      backgroundColor: scheme.primary,
                      foregroundColor: scheme.onPrimary,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                      padding: EdgeInsets.zero,
                      elevation: 0,
                    ),
                    child: isSending
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.north_east_rounded),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class SupportChip extends StatelessWidget {
  const SupportChip({
    required this.label,
    required this.icon,
    required this.color,
    super.key,
  });

  final String label;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          Icon(icon, size: 12, color: color),
          const SizedBox(width: 6),
          Text(
            label,
            style: theme.textTheme.labelMedium?.copyWith(
              color: color,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class SupportEmptyState extends StatelessWidget {
  const SupportEmptyState({
    required this.onCreateTicket,
    super.key,
  });

  final VoidCallback onCreateTicket;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: <Widget>[
          Container(
            width: 70,
            height: 70,
            decoration: BoxDecoration(
              color: theme.colorScheme.primary.withValues(alpha: .12),
              borderRadius: BorderRadius.circular(22),
            ),
            child: Icon(
              Icons.support_agent_rounded,
              size: 30,
              color: theme.colorScheme.primary,
            ),
          ),
          const SizedBox(height: 16),
          Text(
            'No support tickets found',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'You have not created any tickets yet. Create a new ticket to continue the conversation in the same thread.',
            textAlign: TextAlign.center,
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(height: 18),
          FilledButton.icon(
            onPressed: onCreateTicket,
            icon: const Icon(Icons.add_rounded),
            label: const Text('Create Ticket'),
          ),
        ],
      ),
    );
  }
}

class _SupportHeaderMeta extends StatelessWidget {
  const _SupportHeaderMeta({
    required this.label,
    required this.value,
  });

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface.withValues(alpha: .72),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            label,
            style: theme.textTheme.labelSmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 5),
          Text(
            value,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: theme.textTheme.bodySmall?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _MessageAvatar extends StatelessWidget {
  const _MessageAvatar({
    required this.accent,
    required this.label,
    this.isMine = false,
  });

  final Color accent;
  final String label;
  final bool isMine;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final text = label.trim().isEmpty ? '?' : label.trim()[0].toUpperCase();
    return Container(
      width: 30,
      height: 30,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: <Color>[
            accent.withValues(alpha: isMine ? .92 : .88),
            accent.withValues(alpha: .58),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(11),
      ),
      alignment: Alignment.center,
      child: Text(
        text,
        style: theme.textTheme.labelMedium?.copyWith(
          color: Colors.white,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }
}

class _ComposerNotice extends StatelessWidget {
  const _ComposerNotice({
    required this.text,
    required this.color,
  });

  final String text;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .08),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Text(
        text,
        style: Theme.of(context).textTheme.bodySmall?.copyWith(
          color: color,
          fontWeight: FontWeight.w600,
          height: 1.35,
        ),
      ),
    );
  }
}

class _CategoryAvatar extends StatelessWidget {
  const _CategoryAvatar({required this.category});

  final String category;

  @override
  Widget build(BuildContext context) {
    final color = Theme.of(context).colorScheme.primary;

    return Container(
      width: 46,
      height: 46,
      decoration: BoxDecoration(
        color: color.withValues(alpha: .12),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Icon(_categoryIcon(category), size: 18, color: color),
    );
  }
}

bool _isMine(Map<String, dynamic> message) {
  if (message['is_mine'] == true) {
    return true;
  }
  final user = message['user'];
  if (user is Map && user['name']?.toString().toLowerCase() == 'you') {
    return true;
  }
  return false;
}

String _senderName(Map<String, dynamic> message) {
  final user = message['user'];
  if (user is Map && (user['name']?.toString().isNotEmpty ?? false)) {
    return user['name'].toString();
  }
  return _isMine(message) ? 'You' : 'Support';
}

String _formatLabel(String value) {
  return value
      .replaceAll('_', ' ')
      .split(' ')
      .map((part) {
        if (part.isEmpty) {
          return part;
        }
        return '${part[0].toUpperCase()}${part.substring(1)}';
      })
      .join(' ');
}

String _formatDate(dynamic value) {
  final raw = value?.toString();
  if (raw == null || raw.isEmpty) {
    return 'Recently updated';
  }
  return AppDateFormatter.formatDateTime(raw, fallback: raw);
}

IconData _categoryIcon(String category) {
  switch (category) {
    case 'billing':
      return FontAwesomeIcons.creditCard.data;
    case 'technical':
      return FontAwesomeIcons.screwdriverWrench.data;
    case 'feature':
      return FontAwesomeIcons.lightbulb.data;
    case 'other':
      return FontAwesomeIcons.comments.data;
    default:
      return FontAwesomeIcons.circleQuestion.data;
  }
}

IconData _statusIcon(String status) {
  switch (status) {
    case 'in_progress':
      return FontAwesomeIcons.arrowsRotate.data;
    case 'waiting_on_customer':
      return FontAwesomeIcons.hourglassHalf.data;
    case 'resolved':
    case 'closed':
      return FontAwesomeIcons.circleCheck.data;
    default:
      return FontAwesomeIcons.envelopeOpen.data;
  }
}

Color _priorityColor(String priority) {
  switch (priority) {
    case 'urgent':
      return Colors.red.shade700;
    case 'high':
      return Colors.orange.shade700;
    case 'low':
      return Colors.blueGrey.shade600;
    default:
      return Colors.indigo.shade600;
  }
}

Color _statusColor(String status) {
  switch (status) {
    case 'in_progress':
      return Colors.amber.shade800;
    case 'waiting_on_customer':
      return Colors.lightBlue.shade700;
    case 'resolved':
      return Colors.green.shade700;
    case 'closed':
      return Colors.grey.shade700;
    default:
      return Colors.blue.shade700;
  }
}
