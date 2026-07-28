import 'package:flutter/material.dart';

enum AppMessagePosition { top, bottom }

class AppMessagePositionConfig {
  AppMessagePositionConfig._();

  static const AppMessagePosition position = AppMessagePosition.top;
  static const double horizontalInset = 15;
  static const double topSpacing = 12;
  static const double bottomSpacing = 15;

  static Positioned buildPositioned({
    required BuildContext context,
    required Widget child,
  }) {
    final topOffset = MediaQuery.of(context).padding.top + topSpacing;

    switch (position) {
      case AppMessagePosition.top:
        return Positioned(
          left: horizontalInset,
          right: horizontalInset,
          top: topOffset,
          child: child,
        );
      case AppMessagePosition.bottom:
        return Positioned(
          left: horizontalInset,
          right: horizontalInset,
          bottom: bottomSpacing,
          child: child,
        );
    }
  }
}
