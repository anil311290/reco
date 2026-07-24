import 'package:flutter/material.dart';

import '../../../controllers/transactions/create/base_voucher_form_controller.dart';
import 'voucher_form_screen.dart';

class AdjustmentVoucherScreen extends StatelessWidget {
  const AdjustmentVoucherScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const VoucherFormScreen<AdjustmentVoucherFormController>();
  }
}
