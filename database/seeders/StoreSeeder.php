<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;
use App\Models\User;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = User::where('role', 'seller')->get();

        $stores = [
            [
                'name' => 'Cửa hàng Điện tử ABC',
                'slug' => 'cua-hang-dien-tu-abc',
                'description' => 'Chuyên cung cấp thiết bị điện tử chính hãng',
                'business_type' => 'retail',
                'rating_average' => 4.5,
            ],
            [
                'name' => 'Thời trang XYZ',
                'slug' => 'thoi-trang-xyz',
                'description' => 'Thời trang nam nữ cao cấp',
                'business_type' => 'retail',
                'rating_average' => 4.8,
            ],
            [
                'name' => 'Nội thất 123',
                'slug' => 'noi-that-123',
                'description' => 'Nội thất văn phòng và gia đình',
                'business_type' => 'wholesale',
                'rating_average' => 4.3,
            ],
        ];

        foreach ($stores as $index => $storeData) {
            if (isset($sellers[$index])) {
                $storeData['owner_id'] = $sellers[$index]->id;
                $storeData['is_active'] = true;
                Store::create($storeData);
            }
        }

        $this->command->info('✅ Created ' . count($stores) . ' stores');
    }
}
