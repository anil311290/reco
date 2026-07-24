import 'package:flutter/material.dart';

import '../../../controllers/transactions/create/base_voucher_form_controller.dart';
import 'voucher_form_screen.dart';

class ReceiptVoucherScreen extends StatelessWidget {
  const ReceiptVoucherScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const VoucherFormScreen<ReceiptVoucherFormController>();
  }
}
