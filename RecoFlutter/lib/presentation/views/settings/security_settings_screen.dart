import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../controllers/settings/security_settings_controller.dart';
import '../../widgets/common/common_button.dart';
import '../../widgets/common/custom_text_field.dart';

class SecuritySettingsScreen extends GetView<SecuritySettingsController> {
  const SecuritySettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Security Settings',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      body: Obx(
        () => controller.isLoading.value
            ? const Center(child: CircularProgressIndicator())
            : ListView(
                padding: const EdgeInsets.all(16),
                children: <Widget>[
                  Obx(
                    () => SwitchListTile(
                      value: controller.appLockEnabled.value,
                      title: const Text('App Lock'),
                      subtitle: const Text('Enable or disable app lock'),
                      onChanged: (value) =>
                          controller.appLockEnabled.value = value,
                    ),
                  ),
                  Obx(
                    () => SwitchListTile(
                      value: controller.biometricEnabled.value,
                      title: const Text('Biometric'),
                      subtitle: const Text('Allow biometric unlock'),
                      onChanged: (value) =>
                          controller.biometricEnabled.value = value,
                    ),
                  ),
                  Obx(
                    () => ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: const Text('Auto Lock Timeout'),
                      subtitle: Slider(
                        value: controller.autoLockTimeout.value.toDouble(),
                        min: 1,
                        max: 60,
                        divisions: 59,
                        label: '${controller.autoLockTimeout.value} min',
                        onChanged: (value) => controller.autoLockTimeout.value =
                            value.round(),
                      ),
                    ),
                  ),
                  CustomTextField(
                    label: controller.hasPin.value ? 'Update PIN' : 'Set PIN',
                    controller: controller.pinController,
                    keyboardType: TextInputType.number,
                  ),
                  CustomTextField(
                    label: 'Confirm PIN',
                    controller: controller.confirmPinController,
                    keyboardType: TextInputType.number,
                  ),
                  Obx(
                    () => CommonButton(
                      text: 'Save Security Settings',
                      isLoading: controller.isSaving.value,
                      onPressed: controller.saveSecurity,
                    ),
                  ),
                ],
              ),
      ),
    );
  }
}
