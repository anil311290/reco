import 'package:flutter_test/flutter_test.dart';
import 'package:reco_flutter/core/config/app_config.dart';

void main() {
  test('app name remains stable', () {
    expect(AppConfig.appName, 'Reco ERP');
  });
}
