<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Helpers\DateHelper;
use App\Helpers\ResponseHelper;
use App\Services\CompanyRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Yajra\DataTables\Facades\DataTables;

class CompanyApprovalController extends Controller
{
    public function __construct(protected CompanyRoleService $companyRoleService)
    {
    }

    /**
     * Get pending companies for approval
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Company::query()
                ->where('is_active', false)
                ->with('users')
                ->orderBy('created_at', 'desc');

            return DataTables::eloquent($query)
                ->addColumn('owner_name', function (Company $company) {
                    return optional($company->users->first())->name ?? 'N/A';
                })
                ->addColumn('owner_email', function (Company $company) {
                    return optional($company->users->first())->email ?? 'N/A';
                })
                ->editColumn('created_at', function (Company $company) {
                    return DateHelper::formatDateTime($company->created_at);
                })
                ->addColumn('actions', function (Company $company) {
                    $approveUrl = route('admin.companies.approve', $company->id);
                    $rejectUrl = route('admin.companies.reject', $company->id);

                    return '<div class="d-flex flex-wrap justify-content-end gap-2">'
                        . '<button type="button" class="btn btn-sm btn-success js-company-approve" data-url="' . $approveUrl . '" data-name="' . e($company->name) . '"><i class="bi bi-check2-circle me-1"></i>Approve</button>'
                        . '<button type="button" class="btn btn-sm btn-outline-danger js-company-reject" data-url="' . $rejectUrl . '" data-name="' . e($company->name) . '"><i class="bi bi-x-circle me-1"></i>Reject</button>'
                        . '</div>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('admin.companies.approval');
    }

    /**
     * Approve a company
     */
    public function approve(int $id): JsonResponse
    {
        $company = Company::with('users')->findOrFail($id);
        
        DB::transaction(function () use ($company) {
            $this->companyRoleService->provisionDefaultRoles($company);

            // Activate company
            $company->update(['is_active' => true]);

            // Activate all users of this company
            User::where('company_id', $company->id)->update(['status' => 'active']);

            $owner = $company->users()->orderBy('id')->first();
            if ($owner) {
                $this->companyRoleService->assignCompanyOwner($owner);
            }
            
            // Send approval email
            Mail::to($company->users->pluck('email')->filter()->all())->queue(
                new \App\Mail\CompanyApproved($company)
            );
        });

        return ResponseHelper::success(null, 'Company approved successfully');
    }

    /**
     * Reject a company
     */
    public function reject(int $id): JsonResponse
    {
        $company = Company::with('users')->findOrFail($id);
        
        DB::transaction(function () use ($company) {
            // Mark subscription as cancelled
            $company->subscriptions()->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            
            // Delete users
            User::where('company_id', $company->id)->delete();
            
            // Delete company
            $company->delete();
        });

        return ResponseHelper::success(null, 'Company registration rejected');
    }
}