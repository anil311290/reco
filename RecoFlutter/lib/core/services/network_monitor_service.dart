import 'dart:async';

import 'package:get/get.dart';
import 'package:internet_connection_checker_plus/internet_connection_checker_plus.dart';

class NetworkMonitorService extends GetxService {
  final InternetConnection _internetConnection = InternetConnection();
  final RxBool isOnline = false.obs;
  final StreamController<bool> _statusController =
      StreamController<bool>.broadcast();

  StreamSubscription<InternetStatus>? _subscription;

  Stream<bool> get statusStream => _statusController.stream;

  Future<NetworkMonitorService> init() async {
    final online = await _internetConnection.hasInternetAccess;
    isOnline.value = online;
    _statusController.add(online);

    _subscription = _internetConnection.onStatusChange.listen((status) {
      final connected = status == InternetStatus.connected;
      isOnline.value = connected;
      _statusController.add(connected);
    });

    return this;
  }

  Future<bool> hasInternetNow() async {
    final online = await _internetConnection.hasInternetAccess;
    isOnline.value = online;
    return online;
  }

  @override
  void onClose() {
    _subscription?.cancel();
    _statusController.close();
    super.onClose();
  }
}
