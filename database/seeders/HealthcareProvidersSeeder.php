<?php

namespace Database\Seeders;

use App\Models\HealthcareProvider;
use Illuminate\Database\Seeder;

class HealthcareProvidersSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Lagos University Teaching Hospital (LUTH)',
                'address' => 'Idi-Araba, Surulere, Lagos State',
                'contact' => '+234-1-774-0000',
                'services' => [
                    'General Medicine',
                    'Surgery',
                    'Maternity',
                    'Emergency Care',
                    'Laboratory Services',
                    'Radiology',
                    'Pharmacy',
                ],
                'status' => 'active',
            ],
            [
                'name' => 'National Hospital Abuja',
                'address' => 'Central Business District, Abuja',
                'contact' => '+234-9-234-0000',
                'services' => [
                    'General Medicine',
                    'Surgery',
                    'Maternity',
                    'Emergency Care',
                    'Laboratory Services',
                    'Radiology',
                    'Pharmacy',
                    'Dental Care',
                ],
                'status' => 'active',
            ],
            [
                'name' => 'Ahmadu Bello University Teaching Hospital',
                'address' => 'Shika, Zaria, Kaduna State',
                'contact' => '+234-69-550-000',
                'services' => [
                    'General Medicine',
                    'Surgery',
                    'Maternity',
                    'Emergency Care',
                    'Laboratory Services',
                    'Radiology',
                    'Pharmacy',
                    'Eye Care',
                ],
                'status' => 'active',
            ],
            [
                'name' => 'University of Nigeria Teaching Hospital',
                'address' => 'Ituku-Ozalla, Enugu State',
                'contact' => '+234-42-770-000',
                'services' => [
                    'General Medicine',
                    'Surgery',
                    'Maternity',
                    'Emergency Care',
                    'Laboratory Services',
                    'Radiology',
                    'Pharmacy',
                ],
                'status' => 'active',
            ],
            [
                'name' => 'University College Hospital Ibadan',
                'address' => 'Queen Elizabeth Road, Ibadan, Oyo State',
                'contact' => '+234-2-241-0000',
                'services' => [
                    'General Medicine',
                    'Surgery',
                    'Maternity',
                    'Emergency Care',
                    'Laboratory Services',
                    'Radiology',
                    'Pharmacy',
                    'Dental Care',
                    'Eye Care',
                ],
                'status' => 'active',
            ],
            [
                'name' => 'Federal Medical Centre Owerri',
                'address' => 'Owerri, Imo State',
                'contact' => '+234-83-230-000',
                'services' => [
                    'General Medicine',
                    'Surgery',
                    'Maternity',
                    'Emergency Care',
                    'Laboratory Services',
                    'Pharmacy',
                ],
                'status' => 'active',
            ],
            [
                'name' => 'Aminu Kano Teaching Hospital',
                'address' => 'Zaria Road, Kano State',
                'contact' => '+234-64-650-000',
                'services' => [
                    'General Medicine',
                    'Surgery',
                    'Maternity',
                    'Emergency Care',
                    'Laboratory Services',
                    'Radiology',
                    'Pharmacy',
                ],
                'status' => 'active',
            ],
            [
                'name' => 'University of Port Harcourt Teaching Hospital',
                'address' => 'East-West Road, Port Harcourt, Rivers State',
                'contact' => '+234-84-230-000',
                'services' => [
                    'General Medicine',
                    'Surgery',
                    'Maternity',
                    'Emergency Care',
                    'Laboratory Services',
                    'Radiology',
                    'Pharmacy',
                    'Dental Care',
                ],
                'status' => 'active',
            ],
        ];

        foreach ($providers as $provider) {
            HealthcareProvider::updateOrCreate(
                ['name' => $provider['name']],
                $provider
            );
        }
    }
}
