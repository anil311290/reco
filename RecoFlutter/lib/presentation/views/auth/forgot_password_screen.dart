import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_spacing.dart';
import '../../controllers/auth/forgot_password_controller.dart';
import '../../widgets/auth/auth_scaffold.dart';
import '../../widgets/common/common_button.dart';

class ForgotPasswordScreen extends GetView<ForgotPasswordController> {
  const ForgotPasswordScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return AuthScaffold(
      maxWidth: 440,
      child: Obx(() {
        if (controller.resetDone.value) {
          return _buildSuccess(context);
        }

        return Form(
          key: controller.formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: <Widget>[
              SizedBox(height: MediaQuery.sizeOf(context).height * .04),
              Icon(
                controller.emailSent.value
                    ? Icons.lock_reset_rounded
                    : Icons.email_outlined,
                size: 56,
                color: theme.colorScheme.primary,
              ),
              const SizedBox(height: 16),
              Text(
                controller.emailSent.value ? 'Reset Password' : 'Forgot Password',
                textAlign: TextAlign.center,
                style: theme.textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                controller.emailSent.value
                    ? 'Enter the reset token sent to your email and set a new password.'
                    : 'Enter your email to receive a password reset link.',
                textAlign: TextAlign.center,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 32),

              // ── Step 1: Email ──
              Text('Email', style: theme.textTheme.bodyMedium),
              const SizedBox(height: 8),
              TextFormField(
                controller: controller.emailController,
                keyboardType: TextInputType.emailAddress,
                textInputAction: controller.emailSent.value
                    ? TextInputAction.next
                    : TextInputAction.done,
                enabled: !controller.emailSent.value,
                decoration: const InputDecoration(
                  hintText: 'you@example.com',
                  prefixIcon: Icon(Icons.email_outlined),
                ),
                validator: (value) {
                  final email = (value ?? '').trim();
                  if (email.isEmpty) return 'Please enter email';
                  if (!GetUtils.isEmail(email)) return 'Please enter valid email';
                  return null;
                },
              ),

              // ── Step 2: Token + New Password (shown after email sent) ──
              if (controller.emailSent.value) ...[
                const SizedBox(height: 18),
                Text('Reset Token', style: theme.textTheme.bodyMedium),
                const SizedBox(height: 8),
                TextFormField(
                  controller: controller.tokenController,
                  textInputAction: TextInputAction.next,
                  decoration: const InputDecoration(
                    hintText: 'Paste reset token from email',
                    prefixIcon: Icon(Icons.vpn_key_outlined),
                  ),
                  validator: (value) {
                    if ((value ?? '').trim().isEmpty) {
                      return 'Please enter the reset token';
                    }
                    return null;
                  },
                ),

                const SizedBox(height: 18),
                Text('New Password', style: theme.textTheme.bodyMedium),
                const SizedBox(height: 8),
                Obx(
                  () => TextFormField(
                    controller: controller.passwordController,
                    obscureText: controller.isPasswordHidden.value,
                    textInputAction: TextInputAction.next,
                    decoration: InputDecoration(
                      hintText: 'Min 8 characters',
                      prefixIcon: const Icon(Icons.lock_outline_rounded),
                      suffixIcon: IconButton(
                        onPressed: controller.togglePasswordVisibility,
                        icon: Icon(
                          controller.isPasswordHidden.value
                              ? Icons.visibility_off_outlined
                              : Icons.visibility_outlined,
                        ),
                      ),
                    ),
                    validator: (value) {
                      if ((value ?? '').isEmpty) return 'Please enter new password';
                      if ((value ?? '').length < 8) return 'Min 8 characters';
                      return null;
                    },
                  ),
                ),

                const SizedBox(height: 18),
                Text('Confirm Password', style: theme.textTheme.bodyMedium),
                const SizedBox(height: 8),
                Obx(
                  () => TextFormField(
                    controller: controller.confirmPasswordController,
                    obscureText: controller.isConfirmHidden.value,
                    textInputAction: TextInputAction.done,
                    onFieldSubmitted: (_) => controller.resetPassword(),
                    decoration: InputDecoration(
                      hintText: 'Re-enter new password',
                      prefixIcon: const Icon(Icons.lock_outline_rounded),
                      suffixIcon: IconButton(
                        onPressed: controller.toggleConfirmVisibility,
                        icon: Icon(
                          controller.isConfirmHidden.value
                              ? Icons.visibility_off_outlined
                              : Icons.visibility_outlined,
                        ),
                      ),
                    ),
                    validator: (value) {
                      if ((value ?? '').isEmpty) return 'Please confirm password';
                      if (value != controller.passwordController.text) {
                        return 'Passwords do not match';
                      }
                      return null;
                    },
                  ),
                ),
              ],

              const SizedBox(height: 28),

              // ── Action Button ──
              Obx(
                () => CommonButton(
                  text: controller.emailSent.value
                      ? 'Reset Password'
                      : 'Send Reset Link',
                  isLoading: controller.isLoading.value,
                  onPressed: controller.emailSent.value
                      ? controller.resetPassword
                      : controller.sendResetLink,
                ),
              ),

              if (controller.emailSent.value) ...[
                const SizedBox(height: 12),
                TextButton.icon(
                  onPressed: () {
                    controller.emailSent.value = false;
                    controller.tokenController.clear();
                    controller.passwordController.clear();
                    controller.confirmPasswordController.clear();
                  },
                  icon: const Icon(Icons.arrow_back_rounded, size: 18),
                  label: const Text('Change Email'),
                ),
              ],

              const SizedBox(height: AppSpacing.lg),
              TextButton.icon(
                onPressed: controller.goBackToLogin,
                icon: const Icon(Icons.arrow_back_rounded, size: 18),
                label: const Text('Back to Login'),
              ),
            ],
          ),
        );
      }),
    );
  }

  Widget _buildSuccess(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: <Widget>[
        const SizedBox(height: 48),
        Icon(
          Icons.check_circle_rounded,
          size: 72,
          color: Colors.green.shade400,
        ),
        const SizedBox(height: 20),
        Text(
          'Password Reset!',
          textAlign: TextAlign.center,
          style: theme.textTheme.headlineSmall?.copyWith(
            fontWeight: FontWeight.w700,
          ),
        ),
        const SizedBox(height: 8),
        Text(
          'Your password has been reset successfully.\nYou can now login with your new password.',
          textAlign: TextAlign.center,
          style: theme.textTheme.bodyMedium?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
        const SizedBox(height: 32),
        CommonButton(
          text: 'Go to Login',
          onPressed: controller.goBackToLogin,
        ),
      ],
    );
  }
}
