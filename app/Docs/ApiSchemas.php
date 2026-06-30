<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="id", type="integer", example=1, description="User ID"),
 *     @OA\Property(property="name", type="string", example="John Doe", description="User full name"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address"),
 *     @OA\Property(property="phone", type="string", example="+91 9876543210", description="Phone number", nullable=true),
 *     @OA\Property(property="avatar", type="string", example="avatars/user1.jpg", description="Avatar URL", nullable=true),
 *     @OA\Property(property="role", type="string", enum={"admin", "manager", "accountant", "viewer"}, example="admin", description="User role"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active", description="Account status"),
 *     @OA\Property(property="company_id", type="integer", example=1, description="Company ID"),
 *     @OA\Property(property="last_login_at", type="string", format="datetime", example="2025-04-01T10:30:00.000000Z", description="Last login timestamp", nullable=true),
 *     @OA\Property(property="email_verified_at", type="string", format="datetime", example="2025-04-01T10:30:00.000000Z", description="Email verification timestamp", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="datetime", example="2025-04-01T10:30:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", example="2025-04-01T10:30:00.000000Z")
 * )
 *
 * @OA\Schema(
 *     schema="Account",
 *     title="Account",
 *     description="Account model (Chart of Accounts)",
 *     @OA\Property(property="id", type="integer", example=1, description="Account ID"),
 *     @OA\Property(property="account_code", type="string", example="AST0001", description="Unique account code"),
 *     @OA\Property(property="account_name", type="string", example="Cash", description="Account name"),
 *     @OA\Property(property="account_type", type="string", enum={"asset", "liability", "income", "expense", "equity"}, example="asset", description="Account type"),
 *     @OA\Property(property="type_label", type="string", example="Asset", description="Account type label"),
 *     @OA\Property(property="parent_id", type="integer", example=null, description="Parent account ID", nullable=true),
 *     @OA\Property(property="opening_balance", type="number", format="float", example=10000.00, description="Opening balance"),
 *     @OA\Property(property="opening_date", type="string", format="date", example="2025-04-01", description="Opening balance date", nullable=true),
 *     @OA\Property(property="remarks", type="string", example="Main cash account", description="Remarks", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Account status"),
 *     @OA\Property(property="full_path", type="string", example="Assets > Current Assets > Cash", description="Full account path"),
 *     @OA\Property(property="created_at", type="string", format="datetime"),
 *     @OA\Property(property="updated_at", type="string", format="datetime")
 * )
 *
 * @OA\Schema(
 *     schema="Party",
 *     title="Party",
 *     description="Party model (Debtors & Creditors)",
 *     @OA\Property(property="id", type="integer", example=1, description="Party ID"),
 *     @OA\Property(property="party_code", type="string", example="DEB0001", description="Unique party code"),
 *     @OA\Property(property="name", type="string", example="ABC Company", description="Party name"),
 *     @OA\Property(property="type", type="string", enum={"debtor", "creditor"}, example="debtor", description="Party type"),
 *     @OA\Property(property="type_label", type="string", example="Debtor", description="Party type label"),
 *     @OA\Property(property="mobile", type="string", example="+91 9876543210", description="Mobile number", nullable=true),
 *     @OA\Property(property="email", type="string", format="email", example="abc@example.com", description="Email address", nullable=true),
 *     @OA\Property(property="address", type="string", example="123 Business Street", description="Address", nullable=true),
 *     @OA\Property(property="city", type="string", example="Mumbai", description="City", nullable=true),
 *     @OA\Property(property="state", type="string", example="Maharashtra", description="State", nullable=true),
 *     @OA\Property(property="country", type="string", example="India", description="Country", nullable=true),
 *     @OA\Property(property="postal_code", type="string", example="400001", description="Postal code", nullable=true),
 *     @OA\Property(property="gst_number", type="string", example="27AAPFU0939F1ZV", description="GST number", nullable=true),
 *     @OA\Property(property="pan_number", type="string", example="AAPFU0939F", description="PAN number", nullable=true),
 *     @OA\Property(property="opening_balance", type="number", format="float", example=50000.00, description="Opening balance"),
 *     @OA\Property(property="opening_date", type="string", format="date", example="2025-04-01", description="Opening balance date", nullable=true),
 *     @OA\Property(property="remarks", type="string", description="Remarks", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Party status"),
 *     @OA\Property(property="created_at", type="string", format="datetime"),
 *     @OA\Property(property="updated_at", type="string", format="datetime")
 * )
 *
 * @OA\Schema(
 *     schema="Voucher",
 *     title="Voucher",
 *     description="Voucher model",
 *     @OA\Property(property="id", type="integer", example=1, description="Voucher ID"),
 *     @OA\Property(property="voucher_number", type="string", example="EXP000001", description="Unique voucher number"),
 *     @OA\Property(property="voucher_type", type="string", enum={"income", "expense", "receipt", "payment", "journal", "adjustment"}, example="expense", description="Voucher type"),
 *     @OA\Property(property="type_label", type="string", example="Expense", description="Voucher type label"),
 *     @OA\Property(property="voucher_date", type="string", format="date", example="2025-04-15", description="Voucher date"),
 *     @OA\Property(property="party_id", type="integer", example=1, description="Party ID", nullable=true),
 *     @OA\Property(property="party", ref="#/components/schemas/Party", description="Party details"),
 *     @OA\Property(property="narration", type="string", example="Office supplies purchase", description="Narration", nullable=true),
 *     @OA\Property(property="total_debit", type="number", format="float", example=5000.00, description="Total debit amount"),
 *     @OA\Property(property="total_credit", type="number", format="float", example=5000.00, description="Total credit amount"),
 *     @OA\Property(property="status", type="string", enum={"draft", "posted", "cancelled"}, example="draft", description="Voucher status"),
 *     @OA\Property(property="is_balanced", type="boolean", example=true, description="Whether debit equals credit"),
 *     @OA\Property(property="remarks", type="string", description="Remarks", nullable=true),
 *     @OA\Property(property="lines", type="array", @OA\Items(ref="#/components/schemas/VoucherLine"), description="Voucher line items"),
 *     @OA\Property(property="created_at", type="string", format="datetime"),
 *     @OA\Property(property="updated_at", type="string", format="datetime")
 * )
 *
 * @OA\Schema(
 *     schema="VoucherLine",
 *     title="VoucherLine",
 *     description="Voucher line item",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="account_id", type="integer", example=1, description="Account ID"),
 *     @OA\Property(property="account", ref="#/components/schemas/Account", description="Account details"),
 *     @OA\Property(property="debit", type="number", format="float", example=5000.00, description="Debit amount"),
 *     @OA\Property(property="credit", type="number", format="float", example=0.00, description="Credit amount"),
 *     @OA\Property(property="description", type="string", example="Office supplies", description="Line description", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     title="SuccessResponse",
 *     description="Standard success response",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operation successful"),
 *     @OA\Property(property="data", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     title="ErrorResponse",
 *     description="Standard error response",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="An error occurred"),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 */
class ApiSchemas
{
    //
}
