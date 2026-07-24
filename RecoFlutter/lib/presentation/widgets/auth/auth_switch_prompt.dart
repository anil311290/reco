import 'package:flutter/material.dart';

class AuthSwitchPrompt extends StatelessWidget {
  const AuthSwitchPrompt({
    super.key,
    required this.message,
    required this.actionLabel,
    required this.onTap,
  });

  final String message;
  final String actionLabel;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: <Widget>[
        Text(message, style: Theme.of(context).textTheme.bodyMedium),
        TextButton(onPressed: onTap, child: Text(actionLabel)),
      ],
    );
  }
}
