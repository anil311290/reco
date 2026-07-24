import 'package:flutter/material.dart';

import '../../../controllers/transactions/create/base_invoice_form_controller.dart';
import 'invoice_form_screen.dart';

class PurchaseInvoiceScreen extends StatelessWidget {
  const PurchaseInvoiceScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const InvoiceFormScreen<PurchaseInvoiceFormController>();
  }
}
