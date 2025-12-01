<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Listing;
use App\Models\User;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Support\Str;

class ListingSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = User::where('role', 'seller')->get();
        $categories = Category::whereNull('parent_id')->get();
        $stores = Store::all();

        $listings = [
            [
                'title' => 'iPhone 15 Pro Max 256GB - Chính hãng VN/A',
                'description' => 'iPhone 15 Pro Max mới 100%, nguyên seal, chính hãng Apple Việt Nam. Bảo hành 12 tháng.',
                'price_cents' => 3490000000, // 34,900,000 VND
                'category' => 'Điện thoại',
                'location_text' => 'Hà Nội',
                'status' => 'published',
            ],
            [
                'title' => 'Laptop Dell XPS 13 - Core i7 Gen 13',
                'description' => 'Laptop Dell XPS 13 inch, chip Intel Core i7 thế hệ 13, RAM 16GB, SSD 512GB. Mỏng nhẹ, hiệu năng cao.',
                'price_cents' => 2890000000, // 28,900,000 VND
                'category' => 'Máy tính',
                'location_text' => 'TP.HCM',
                'status' => 'published',
            ],
            [
                'title' => 'Áo sơ mi nam công sở cao cấp',
                'description' => 'Áo sơ mi nam chất liệu cotton 100%, form dáng chuẩn, phù hợp đi làm và dự tiệc.',
                'price_cents' => 35000000, // 350,000 VND
                'category' => 'Thời trang',
                'location_text' => 'Đà Nẵng',
                'status' => 'published',
            ],
            [
                'title' => 'Bàn làm việc gỗ công nghiệp cao cấp',
                'description' => 'Bàn làm việc gỗ MDF phủ melamine, kích thước 120x60cm, chân sắt sơn tĩnh điện.',
                'price_cents' => 189000000, // 1,890,000 VND
                'category' => 'Nội thất',
                'location_text' => 'Hà Nội',
                'status' => 'published',
            ],
            [
                'title' => 'Xe máy Honda SH 350i 2024',
                'description' => 'Honda SH 350i phiên bản 2024, màu đen, mới 100%, chưa đăng ký.',
                'price_cents' => 14500000000, // 145,000,000 VND
                'category' => 'Xe máy',
                'location_text' => 'TP.HCM',
                'status' => 'published',
            ],
            [
                'title' => 'Dịch vụ thiết kế website chuyên nghiệp',
                'description' => 'Thiết kế website theo yêu cầu, responsive, SEO chuẩn. Bảo hành 12 tháng.',
                'price_cents' => 500000000, // 5,000,000 VND
                'category' => 'Dịch vụ',
                'location_text' => 'Toàn quốc',
                'status' => 'published',
            ],
            [
                'title' => 'Tai nghe AirPods Pro 2 - Chính hãng Apple',
                'description' => 'AirPods Pro thế hệ 2, chống ồn chủ động, sạc MagSafe, bảo hành 12 tháng.',
                'price_cents' => 649000000, // 6,490,000 VND
                'category' => 'Phụ kiện',
                'location_text' => 'Hà Nội',
                'status' => 'published',
            ],
            [
                'title' => 'Giày thể thao Nike Air Max 2024',
                'description' => 'Giày Nike Air Max 2024, chính hãng, đủ size, nhiều màu sắc.',
                'price_cents' => 329000000, // 3,290,000 VND
                'category' => 'Giày dép',
                'location_text' => 'TP.HCM',
                'status' => 'published',
            ],
            [
                'title' => 'Tủ lạnh Samsung Inverter 360L',
                'description' => 'Tủ lạnh Samsung 360 lít, công nghệ Inverter tiết kiệm điện, bảo hành 12 tháng.',
                'price_cents' => 890000000, // 8,900,000 VND
                'category' => 'Đồ gia dụng',
                'location_text' => 'Đà Nẵng',
                'status' => 'published',
            ],
            [
                'title' => 'Dịch vụ vận chuyển hàng hóa toàn quốc',
                'description' => 'Vận chuyển hàng hóa nhanh chóng, an toàn. Giá cả cạnh tranh.',
                'price_cents' => 0, // Liên hệ
                'category' => 'Vận chuyển',
                'location_text' => 'Toàn quốc',
                'status' => 'published',
            ],
        ];

        foreach ($listings as $index => $listingData) {
            $seller = $sellers[$index % $sellers->count()];
            $store = $stores[$index % $stores->count()] ?? null;

            $listingData['user_id'] = $seller->id;
            $listingData['store_id'] = $store?->id;
            $listingData['slug'] = Str::slug($listingData['title']) . '-' . time() . '-' . $index;
            $listingData['is_active'] = true;
            $listingData['is_public'] = true;
            $listingData['currency'] = 'VND';

            Listing::create($listingData);
        }

        $this->command->info('✅ Created ' . count($listings) . ' listings');
    }
}
