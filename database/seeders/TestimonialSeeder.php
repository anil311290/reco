<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'client_name'  => 'Rajesh Kumar',
                'company_name' => 'Kumar Textiles',
                'designation'  => 'Owner',
                'testimonial'  => 'Reco has completely transformed how we manage our accounts. The offline feature is a lifesaver in our area where internet connectivity is unreliable. Highly recommended!',
                'rating'       => 5,
                'is_featured'  => true,
                'is_active'    => true,
                'sort_order'   => 1,
            ],
            [
                'client_name'  => 'Priya Sharma',
                'company_name' => 'Sharma Electronics',
                'designation'  => 'Accountant',
                'testimonial'  => 'The reporting features are incredible. I can generate Balance Sheet and P&L statements in seconds. The AR aging report helps us track outstanding payments efficiently.',
                'rating'       => 5,
                'is_featured'  => true,
                'is_active'    => true,
                'sort_order'   => 2,
            ],
            [
                'client_name'  => 'Amit Patel',
                'company_name' => 'Patel Traders',
                'designation'  => 'Managing Director',
                'testimonial'  => 'We switched from manual bookkeeping to Reco and it has saved us hours every week. The voucher system is intuitive and the ledger engine is very accurate.',
                'rating'       => 4,
                'is_featured'  => true,
                'is_active'    => true,
                'sort_order'   => 3,
            ],
            [
                'client_name'  => 'Sneha Reddy',
                'company_name' => 'Reddy Foods',
                'designation'  => 'Finance Manager',
                'testimonial'  => 'The multi-user support with role-based permissions is exactly what we needed. Our team can work simultaneously without any data conflicts.',
                'rating'       => 5,
                'is_featured'  => false,
                'is_active'    => true,
                'sort_order'   => 4,
            ],
            [
                'client_name'  => 'Vikram Singh',
                'company_name' => 'Singh Constructions',
                'designation'  => 'Proprietor',
                'testimonial'  => 'Being in the construction business, I need to track payments from multiple clients. Reco\'s party management and receivables tracking has made this so much easier.',
                'rating'       => 4,
                'is_featured'  => false,
                'is_active'    => true,
                'sort_order'   => 5,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::firstOrCreate(
                ['client_name' => $testimonial['client_name']],
                $testimonial
            );
        }
    }
}
