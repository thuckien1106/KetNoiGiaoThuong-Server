<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Gói Miễn phí',
                'slug' => 'free',
                'description' => 'Gói cơ bản cho người mới bắt đầu',
                'price_cents' => 0,
                'duration_months' => 1,
                'features' => json_encode([
                    'Đăng tối đa 5 tin/tháng',
                    'Hiển thị thông thường',
                    'Hỗ trợ cơ bản',
                ]),
                'max_listings' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Gói Cơ bản',
                'slug' => 'basic',
                'description' => 'Phù hợp cho cửa hàng nhỏ',
                'price_cents' => 29900000, // 299,000 VND/tháng
                'duration_months' => 1,
                'features' => json_encode([
                    'Đăng không giới hạn tin',
                    'Hiển thị ưu tiên',
                    '2 tin nổi bật/tháng',
                    'Thống kê cơ bản',
                    'Hỗ trợ ưu tiên',
                ]),
                'max_listings' => 999,
                'is_active' => true,
            ],
            [
                'name' => 'Gói Chuyên nghiệp',
                'slug' => 'professional',
                'description' => 'Dành cho doanh nghiệp vừa',
                'price_cents' => 59900000, // 599,000 VND/tháng
                'duration_months' => 1,
                'features' => json_encode([
                    'Đăng không giới hạn tin',
                    'Hiển thị ưu tiên cao',
                    '10 tin nổi bật/tháng',
                    'Thống kê chi tiết',
                    'Quảng cáo banner',
                    'Hỗ trợ 24/7',
                    'Tạo phiên đấu giá',
                ]),
                'max_listings' => 9999,
                'is_active' => true,
            ],
            [
                'name' => 'Gói Doanh nghiệp',
                'slug' => 'enterprise',
                'description' => 'Giải pháp toàn diện cho doanh nghiệp lớn',
                'price_cents' => 149900000, // 1,499,000 VND/tháng
                'duration_months' => 1,
                'features' => json_encode([
                    'Tất cả tính năng gói Chuyên nghiệp',
                    'Tin nổi bật không giới hạn',
                    'Quảng cáo popup',
                    'API tích hợp',
                    'Tài khoản quản lý riêng',
                    'Báo cáo tùy chỉnh',
                    'Đào tạo sử dụng',
                ]),
                'max_listings' => 99999,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create($plan);
        }

        $this->command->info('✅ Created ' . count($plans) . ' subscription plans');
    }
}
