import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_spacing.dart';
import '../../bindings/login_binding.dart';
import '../../controllers/auth/register_controller.dart';
import '../../widgets/auth/auth_scaffold.dart';
import '../../widgets/auth/auth_switch_prompt.dart';
import '../../widgets/common/common_button.dart';
import '../../widgets/common/custom_text_field.dart';
import 'login_screen.dart';

class RegisterScreen extends GetView<RegisterController> {
  const RegisterScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return AuthScaffold(
      maxWidth: 520,
      child: Form(
        key: controller.formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: <Widget>[
            const SizedBox(height: AppSpacing.lg),
            Text(
              'Create Account',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            const SizedBox(height: 8),
            Text(
              'Register your company owner account to start using Reco ERP',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
            const SizedBox(height: 34),
            Text(
              'Owner Details',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: AppSpacing.md),
            CustomTextField(
              controller: controller.nameController,
              hintText: 'Enter full name',
              label: 'Full Name',
              textInputAction: TextInputAction.next,
              prefixIcon: Icons.person_outline_rounded,
              validator: (value) {
                if ((value ?? '').trim().isEmpty) {
                  return 'Please enter full name';
                }
                return null;
              },
            ),
            const SizedBox(height: AppSpacing.md),
            CustomTextField(
              controller: controller.emailController,
              hintText: 'Enter email address',
              label: 'Email',
              keyboardType: TextInputType.emailAddress,
              textInputAction: TextInputAction.next,
              prefixIcon: Icons.email_outlined,
              validator: (value) {
                final email = (value ?? '').trim();
                if (email.isEmpty) {
                  return 'Please enter email';
                }
                if (!GetUtils.isEmail(email)) {
                  return 'Please enter valid email';
                }
                return null;
              },
            ),
            const SizedBox(height: AppSpacing.md),
            CustomTextField(
              controller: controller.phoneController,
              hintText: 'Optional phone number',
              label: 'Phone',
              keyboardType: TextInputType.phone,
              textInputAction: TextInputAction.next,
              prefixIcon: Icons.phone_outlined,
            ),
            const SizedBox(height: AppSpacing.xl),
            Text(
              'Company Details',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: AppSpacing.md),
            CustomTextField(
              controller: controller.companyNameController,
              hintText: 'Enter company name',
              label: 'Company Name',
              textInputAction: TextInputAction.next,
              prefixIcon: Icons.business_outlined,
              validator: (value) {
                if ((value ?? '').trim().isEmpty) {
                  return 'Please enter company name';
                }
                return null;
              },
            ),
            const SizedBox(height: AppSpacing.md),
            CustomTextField(
              controller: controller.companyEmailController,
              hintText: 'Optional company email',
              label: 'Company Email',
              keyboardType: TextInputType.emailAddress,
              textInputAction: TextInputAction.next,
              prefixIcon: Icons.alternate_email_rounded,
              validator: (value) {
                final email = (value ?? '').trim();
                if (email.isNotEmpty && !GetUtils.isEmail(email)) {
                  return 'Please enter valid company email';
                }
                return null;
              },
            ),
            const SizedBox(height: AppSpacing.xl),
            Text(
              'Security',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: AppSpacing.md),
            Obx(
              () => CustomTextField(
                controller: controller.passwordController,
                hintText: 'Enter password',
                label: 'Password',
                obscureText: controller.obscurePassword.value,
                textInputAction: TextInputAction.next,
                prefixIcon: Icons.lock_outline_rounded,
                suffixIcon: controller.obscurePassword.value
                    ? Icons.visibility_off_outlined
                    : Icons.visibility_outlined,
                onSuffixTap: controller.togglePasswordVisibility,
                validator: (value) {
                  if ((value ?? '').isEmpty) {
                    return 'Please enter password';
                  }
                  if ((value ?? '').length < 8) {
                    return 'Password must be at least 8 characters';
                  }
                  return null;
                },
              ),
            ),
            const SizedBox(height: AppSpacing.md),
            Obx(
              () => CustomTextField(
                controller: controller.confirmPasswordController,
                hintText: 'Confirm password',
                label: 'Confirm Password',
                obscureText: controller.obscureConfirmPassword.value,
                textInputAction: TextInputAction.done,
                prefixIcon: Icons.lock_reset_rounded,
                suffixIcon: controller.obscureConfirmPassword.value
                    ? Icons.visibility_off_outlined
                    : Icons.visibility_outlined,
                onSuffixTap: controller.toggleConfirmPasswordVisibility,
                onFieldSubmitted: (_) => controller.register(),
                validator: (value) {
                  if ((value ?? '').isEmpty) {
                    return 'Please confirm password';
                  }
                  if (value != controller.passwordController.text) {
                    return 'Password confirmation does not match';
                  }
                  return null;
                },
              ),
            ),
            const SizedBox(height: 28),
            Obx(
              () => CommonButton(
                text: controller.isLoading.value
                    ? 'Creating account...'
                    : 'Create Account',
                isLoading: controller.isLoading.value,
                onPressed: controller.register,
              ),
            ),
            const SizedBox(height: AppSpacing.lg),
            AuthSwitchPrompt(
              message: 'Already have an account?',
              actionLabel: 'Login',
              onTap: () {
                Get.off(() => const LoginScreen(), binding: LoginBinding());
              },
            ),
          ],
        ),
      ),
    );
  }
}
