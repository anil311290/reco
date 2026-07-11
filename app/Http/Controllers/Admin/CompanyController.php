<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\LoginHistory;
use App\Models\SubscriptionPayment;
use App\Models\UserDevice;
use App\Models\Voucher;
use App\Helpers\DateHelper;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class CompanyController extends Controller
{
    /**
     * Display a listing of companies (for Super Admin).
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Company::query()->orderBy('created_at', 'desc');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function (Company $company) {
                    $class = $company->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                    $label = $company->is_active ? 'Active' : 'Inactive';

                    return '<span class="badge rounded-pill ' . $class . '">' . $label . '</span>';
                })
                ->editColumn('created_at', function (Company $company) {
                    return DateHelper::formatDateTime($company->created_at);
                })
                ->addColumn('actions', function (Company $company) {
                    $showUrl = route('admin.companies.show', $company->id);
                    $editUrl = route('admin.companies.edit', $company->id);
                    $deleteUrl = route('admin.companies.destroy', $company->id);

                    return '<div class="d-flex justify-content-end gap-2">'
                        . '<a href="' . $showUrl . '" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i>View</a>'
                        . '<a href="' . $editUrl . '" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>'
                        . '<button type="button" class="btn btn-sm btn-outline-danger js-company-delete" data-url="' . $deleteUrl . '" data-name="' . e($company->name) . '"><i class="bi bi-trash me-1"></i>Delete</button>'
                        . '</div>';
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        return view('admin.companies.index');
    }

    /**
     * Display detailed company information.
     */
    public function show(int $id)
    {
        $company = Company::withTrashed()
            ->with(['users.roles', 'activeSubscription.plan', 'currentFinancialYear', 'theme'])
            ->find($id);

        if (!$company) {
            return redirect()
                ->route('admin.companies.index')
                ->with('error', 'Requested company was not found.');
        }

        $hasLoginHistory = Schema::hasTable('login_histories');
        $hasUserDevices = Schema::hasTable('user_devices');

        $owner = $company->users->sortBy('id')->first();

        $statistics = [
            'user_count' => $company->users()->count(),
            'active_user_count' => $company->users()->where('status', 'active')->count(),
            'transaction_count' => Voucher::where('company_id', $company->id)->count(),
            'posted_transaction_count' => Voucher::where('company_id', $company->id)->where('status', 'posted')->count(),
            'device_count' => $hasUserDevices ? UserDevice::where('company_id', $company->id)->count() : 0,
            'active_device_count' => $hasUserDevices ? UserDevice::where('company_id', $company->id)->where('is_active', true)->count() : 0,
            'login_count_30d' => $hasLoginHistory ? LoginHistory::where('company_id', $company->id)->where('status', 'success')->where('created_at', '>=', now()->subDays(30))->count() : 0,
            'failed_logins_30d' => $hasLoginHistory ? LoginHistory::where('company_id', $company->id)->where('status', 'failed')->where('created_at', '>=', now()->subDays(30))->count() : 0,
            'sales_total' => (float) Voucher::where('company_id', $company->id)->where('voucher_type', 'income')->where('status', 'posted')->sum('total_debit'),
            'purchase_total' => (float) Voucher::where('company_id', $company->id)->where('voucher_type', 'expense')->where('status', 'posted')->sum('total_debit'),
            'receipt_total' => (float) Voucher::where('company_id', $company->id)->where('voucher_type', 'receipt')->where('status', 'posted')->sum('total_debit'),
            'payment_total' => (float) Voucher::where('company_id', $company->id)->where('voucher_type', 'payment')->where('status', 'posted')->sum('total_debit'),
            'subscription_paid_total' => (float) SubscriptionPayment::where('company_id', $company->id)->where('status', 'completed')->sum('amount'),
        ];

        $recentUsers = $company->users()->latest()->limit(6)->get();
        $recentLogins = $hasLoginHistory
            ? LoginHistory::with('user')->where('company_id', $company->id)->latest('created_at')->limit(8)->get()
            : collect();
        $recentDevices = $hasUserDevices
            ? UserDevice::with('user')->where('company_id', $company->id)->latest('last_active_at')->limit(8)->get()
            : collect();
        $recentPayments = SubscriptionPayment::where('company_id', $company->id)->latest('created_at')->limit(5)->get();

        return view('admin.companies.show', compact('company', 'owner', 'statistics', 'recentUsers', 'recentLogins', 'recentDevices', 'recentPayments'));
    }

    /**
     * Show the form for editing the specified company.
     */
    public function edit(int $id)
    {
        $company = Company::findOrFail($id);
        return view('admin.companies.edit', compact('company'));
    }

    /**
     * Update the specified company.
     */
    public function update(Request $request, int $id)
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'boolean',
        ]);

        $company->update($validated);

        return ResponseHelper::success($company, 'Company updated successfully');
    }

    /**
     * Remove the specified company.
     */
    public function destroy(int $id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return ResponseHelper::success(null, 'Company deleted');
    }
}
