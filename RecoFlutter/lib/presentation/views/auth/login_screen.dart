import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../core/utils/app_spacing.dart';
import '../../controllers/auth/login_controller.dart';
import '../../widgets/auth/auth_scaffold.dart';
import '../../widgets/auth/auth_switch_prompt.dart';
import '../../widgets/common/common_button.dart';
import '../../widgets/common/custom_text_field.dart';

class LoginScreen extends GetView<LoginController> {
  const LoginScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return AuthScaffold(
      maxWidth: 440,
      child: Form(
        key: controller.formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: <Widget>[
            // SizedBox(height: MediaQuery.sizeOf(context).height * .06),
            Image.asset('assets/icons/logo.png', height: 34),
            SizedBox(height: MediaQuery.sizeOf(context).height * .06),

            Text(
              'Welcome Back',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            const SizedBox(height: 8),
            Text(
              'Login with your email and password',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
            const SizedBox(height: 42),
            Text('Email', style: Theme.of(context).textTheme.bodyMedium),
            const SizedBox(height: 8),
            TextFormField(
              controller: controller.emailController,
              keyboardType: TextInputType.emailAddress,
              textInputAction: TextInputAction.next,
              decoration: const InputDecoration(
                hintText: 'admin@gmail.com',
                prefixIcon: Icon(Icons.email_outlined),
              ),
              validator: (value) {
                final email = (value ?? '').trim();
                if (email.isEmpty) return 'Please enter email';
                if (!GetUtils.isEmail(email)) {
                  return 'Please enter valid email';
                }
                return null;
              },
            ),
            const SizedBox(height: 18),
            Text(
              'Password',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            const SizedBox(height: 8),
            Obx(
                  () => TextFormField(
                controller: controller.passwordController,
                obscureText: controller.isPasswordHidden.value,
                textInputAction: TextInputAction.done,
                onFieldSubmitted: (_) => controller.login(),
                decoration: InputDecoration(
                  hintText: 'Enter password',
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
                  if ((value ?? '').isEmpty) {
                    return 'Please enter password';
                  }
                  return null;
                },
              ),
            ),

            const SizedBox(height: 28),
            Obx(
              () => CommonButton(
                text: controller.isLoading.value ? 'Logging in...' : 'Login',
                isLoading: controller.isLoading.value,
                onPressed: controller.login,
              ),
            ),
            const SizedBox(height: 16),
            Align(
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                onPressed: controller.openForgotPassword,
                icon: const Icon(Icons.lock_reset_rounded, size: 18),
                label: const Text('Forgot Password?'),
              ),
            ),
            const SizedBox(height: AppSpacing.lg),
            AuthSwitchPrompt(
              message: 'Don\'t have an account?',
              actionLabel: 'Sign Up',
              onTap: controller.openRegister,
            ),
          ],
        ),
      ),
    );
  }
}
