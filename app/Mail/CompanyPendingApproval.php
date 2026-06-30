<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Company;
use Illuminate\Mail\Mailable;

class CompanyPendingApproval extends Mailable
{
    public User $user;
    public ?Company $company;

    public function __construct(User $user, ?Company $company)
    {
        $this->user = $user;
        $this->company = $company;
    }

    public function build(): self
    {
        return $this->subject('New Company Registration - Pending Approval')
            ->view('emails.company-pending-approval')
            ->with([
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'companyName' => $this->company?->name,
                'companyEmail' => $this->company?->email,
            ]);
    }
}