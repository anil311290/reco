import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../controllers/settings/support_ticket_chat_controller.dart';
import 'widgets/support_ticket_ui_components.dart';

class SupportTicketChatScreen extends StatefulWidget {
  const SupportTicketChatScreen({
    required this.initialTicket,
    super.key,
  });

  final Map<String, dynamic> initialTicket;

  @override
  State<SupportTicketChatScreen> createState() => _SupportTicketChatScreenState();
}

class _SupportTicketChatScreenState extends State<SupportTicketChatScreen> {
  late final SupportTicketChatController controller;

  @override
  void initState() {
    super.initState();
    controller = Get.find<SupportTicketChatController>();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      controller.loadTicket(initialTicket: widget.initialTicket);
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Scaffold(
      backgroundColor: Color.alphaBlend(
        scheme.primary.withValues(alpha: .025),
        theme.scaffoldBackgroundColor,
      ),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        surfaceTintColor: Colors.transparent,
        title: Obx(
          () => Text(
            controller.currentTicket.value?['ticket_number']?.toString() ??
                'Ticket Thread',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
        actions: <Widget>[
          IconButton(
            onPressed: controller.refreshTicket,
            icon: const Icon(Icons.refresh_rounded),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: Obx(() {
        final ticket = controller.currentTicket.value;
        if (ticket == null && controller.isLoading.value) {
          return const Center(child: CircularProgressIndicator());
        }
        if (ticket == null) {
          return const SizedBox.shrink();
        }

        final messages = ((ticket['messages'] as List?) ?? <dynamic>[])
            .map(
              (item) => item is Map<String, dynamic>
                  ? Map<String, dynamic>.from(item)
                  : Map<String, dynamic>.from(item as Map),
            )
            .toList();

        return Column(
          children: <Widget>[
            Expanded(
              child: RefreshIndicator(
                onRefresh: controller.refreshTicket,
                child: CustomScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  slivers: <Widget>[
                    SliverPadding(
                      padding: const EdgeInsets.fromLTRB(16, 14, 16, 10),
                      sliver: SliverToBoxAdapter(
                        child: SupportTicketHeader(ticket: ticket),
                      ),
                    ),
                    SliverPadding(
                      padding: const EdgeInsets.fromLTRB(16, 0, 16, 18),
                      sliver: SliverToBoxAdapter(
                        child: SupportConversationCard(messages: messages),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            SafeArea(
              top: false,
              child: SupportReplyComposer(
                controller: controller.replyController,
                canReply: controller.canReply,
                isSending: controller.isSending.value,
                onSend: controller.sendReply,
                status: ticket['status']?.toString() ?? '',
                isDraftTicket: (ticket['id']?.toString() ?? '').isEmpty,
              ),
            ),
          ],
        );
      }),
    );
  }
}
