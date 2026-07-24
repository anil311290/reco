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
        child: Form(
          key: controller.formKey,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
            children: <Widget>[
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: theme.colorScheme.primary.withValues(alpha: .08),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                    color: theme.colorScheme.primary.withValues(alpha: .16),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      'Describe your issue clearly',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Ticket create hone ke baad same thread me support reply karega. Aap yahi se message continue kar paenge.',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 18),
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
                    child: Obx(
                      () => CustomDropdown<String>(
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
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Obx(
                      () => CustomDropdown<String>(
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
              const SizedBox(height: 8),
              Obx(
                () => CommonButton(
                  text: 'Submit Ticket',
                  isLoading: controller.isSubmitting.value,
                  onPressed: controller.submit,
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
