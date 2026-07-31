# Backend Report Export Updates

## Purpose
Mobile app ke reports me PDF aur Excel export ko web/admin ke same flow par sync karne ke liye backend me missing API export endpoints add kiye gaye hain.

## Updated files
- `routes/api.php`
- `app/Http/Controllers/Api/ExportApiController.php`
- `app/Services/ExportService.php`
- `resources/views/exports/receipt-payment.blade.php`

## Added API endpoints

### PDF
- `/api/v1/export/receipt-payment/pdf`

### Excel
- `/api/v1/export/day-book/excel`
- `/api/v1/export/receipt-payment/excel`
- `/api/v1/export/ledger/excel`
- `/api/v1/export/trial-balance/excel`
- `/api/v1/export/profit-loss/excel`
- `/api/v1/export/balance-sheet/excel`
- `/api/v1/export/debtors-outstanding/excel`
- `/api/v1/export/creditors-outstanding/excel`

## Backend logic added
- Receipt & Payment PDF export generation
- Receipt & Payment Excel dataset mapping
- Shared Excel response helper for report exports

## Notes
- Admin web flow ko disturb nahi kiya gaya.
- Existing export response format same rakha gaya:
  - `filename`
  - `content_type`
  - `content_base64`
  - `path`
- App ab Excel ke liye local CSV fallback rakh raha hai, lekin primary flow backend XLSX endpoint se hoga.

## Deploy checklist
1. Backend code deploy karein.
2. Ensure storage/public export path accessible ho.
3. Agar production me cached routes hain to run:
   - `php artisan route:clear`
   - `php artisan config:clear`
   - `php artisan cache:clear`
4. PDF/Excel buttons ko app se verify karein for:
   - Day Book
   - Ledger
   - Trial Balance
   - Profit & Loss
   - Receipt & Payment
   - Balance Sheet
   - Receivables
   - Payables
