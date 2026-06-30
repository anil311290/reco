<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Mail\Mailable;

class CompanyApproved extends Mailable
{
    public Company $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function build(): self
    {
        return $this->subject('Company Registration Approved - Welcome!')
            ->view('emails.company-approved')
            ->with([
                'companyName' => $this->company->name,
                'loginUrl' => url('/admin/login'),
            ]);
    }
}