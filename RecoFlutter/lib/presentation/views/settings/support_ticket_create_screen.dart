import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../controllers/settings/support_ticket_create_controller.dart';
import '../../widgets/common/common_button.dart';
import '../../widgets/common/custom_text_field.dart';

class SupportTicketCreateScreen extends GetView<SupportTicketCreateController> {
  const SupportTicketCreateScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          'New Support Ticket',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      body: SafeArea(
        child: Obx(
          () => Stack(
            children: <Widget>[
              Form(
                key: controller.formKey,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
                  children: <Widget>[
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.primary.withValues(alpha: .08),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: theme.colorScheme.primary.withValues(alpha: .16),
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: <Widget>[
                          Text(
                            'Describe your issue clearly',
                            style: theme.textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Once the ticket is created, support will reply in the same thread. You can continue the conversation here.',
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                              height: 1.25,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 14),
                    CustomTextField(
                      label: 'Subject',
                      controller: controller.subjectController,
                      hintText: 'Briefly describe the issue',
                      requiredField: true,
                      validator: (value) {
                        if ((value ?? '').trim().isEmpty) {
                          return 'Subject is required';
                        }
                        return null;
                      },
                    ),
                    Row(
                      children: <Widget>[
                        Expanded(
                          child: CustomDropdown<String>(
                            label: 'Category',
                            value: controller.selectedCategory.value,
                            items: const <String>[
                              'general',
                              'billing',
                              'technical',
                              'feature',
                              'other',
                            ],
                            enableSearch: false,
                            itemLabelBuilder: _capitalize,
                            onChanged: (value) {
                              if (value != null) {
                                controller.selectedCategory.value = value;
                              }
                            },
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: CustomDropdown<String>(
                            label: 'Priority',
                            value: controller.selectedPriority.value,
                            items: const <String>[
                              'low',
                              'normal',
                              'high',
                              'urgent',
                            ],
                            enableSearch: false,
                            itemLabelBuilder: _capitalize,
                            onChanged: (value) {
                              if (value != null) {
                                controller.selectedPriority.value = value;
                              }
                            },
                          ),
                        ),
                      ],
                    ),
                    CustomTextField(
                      label: 'Message',
                      controller: controller.messageController,
                      hintText: 'Explain the issue, expected result and current problem',
                      maxLines: 7,
                      requiredField: true,
                      validator: (value) {
                        if ((value ?? '').trim().isEmpty) {
                          return 'Message is required';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 4),
                    AnimatedSwitcher(
                      duration: const Duration(milliseconds: 180),
                      child: controller.isSubmitting.value
                          ? Container(
                              key: const ValueKey('submitting_hint'),
                              margin: const EdgeInsets.only(bottom: 10),
                              padding: const EdgeInsets.symmetric(
                                horizontal: 12,
                                vertical: 10,
                              ),
                              decoration: BoxDecoration(
                                color: theme.colorScheme.primary.withValues(alpha: .08),
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(
                                  color: theme.colorScheme.primary.withValues(alpha: .14),
                                ),
                              ),
                              child: Row(
                                children: <Widget>[
                                  SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: theme.colorScheme.primary,
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      'Creating ticket and opening chat...',
                                      style: theme.textTheme.bodySmall?.copyWith(
                                        color: theme.colorScheme.primary,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            )
                          : Container(
                              key: const ValueKey('idle_hint'),
                              margin: const EdgeInsets.only(bottom: 10),
                              padding: const EdgeInsets.symmetric(horizontal: 2),
                              child: Text(
                                'After save, the ticket thread will open automatically.',
                                style: theme.textTheme.bodySmall?.copyWith(
                                  color: theme.colorScheme.onSurfaceVariant,
                                ),
                              ),
                            ),
                    ),
                    CommonButton(
                      text: controller.isSubmitting.value
                          ? 'Creating Ticket...'
                          : 'Submit Ticket',
                      isLoading: controller.isSubmitting.value,
                      onPressed: controller.submit,
                    ),
                  ],
                ),
              ),
              if (controller.isSubmitting.value)
                Positioned.fill(
                  child: AbsorbPointer(
                    child: Container(
                      color: Colors.black.withValues(alpha: .05),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  static String _capitalize(String value) {
    if (value.isEmpty) {
      return value;
    }
    return '${value[0].toUpperCase()}${value.substring(1)}';
  }
}
