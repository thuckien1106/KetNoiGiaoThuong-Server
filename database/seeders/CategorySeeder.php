<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Điện tử - Công nghệ',
                'slug' => 'dien-tu-cong-nghe',
                'description' => 'Thiết bị điện tử, máy tính, điện thoại',
                'is_active' => true,
                'sort_order' => 1,
                'children' => [
                    ['name' => 'Điện thoại', 'slug' => 'dien-thoai'],
                    ['name' => 'Máy tính', 'slug' => 'may-tinh'],
                    ['name' => 'Phụ kiện', 'slug' => 'phu-kien'],
                ]
            ],
            [
                'name' => 'Thời trang',
                'slug' => 'thoi-trang',
                'description' => 'Quần áo, giày dép, túi xách',
                'is_active' => true,
                'sort_order' => 2,
                'children' => [
                    ['name' => 'Quần áo nam', 'slug' => 'quan-ao-nam'],
                    ['name' => 'Quần áo nữ', 'slug' => 'quan-ao-nu'],
                    ['name' => 'Giày dép', 'slug' => 'giay-dep'],
                ]
            ],
            [
                'name' => 'Nhà cửa - Đời sống',
                'slug' => 'nha-cua-doi-song',
                'description' => 'Đồ gia dụng, nội thất',
                'is_active' => true,
                'sort_order' => 3,
                'children' => [
                    ['name' => 'Nội thất', 'slug' => 'noi-that'],
                    ['name' => 'Đồ gia dụng', 'slug' => 'do-gia-dung'],
                ]
            ],
            [
                'name' => 'Xe cộ',
                'slug' => 'xe-co',
                'description' => 'Ô tô, xe máy, phụ tùng',
                'is_active' => true,
                'sort_order' => 4,
                'children' => [
                    ['name' => 'Ô tô', 'slug' => 'o-to'],
                    ['name' => 'Xe máy', 'slug' => 'xe-may'],
                ]
            ],
            [
                'name' => 'Thực phẩm',
                'slug' => 'thuc-pham',
                'description' => 'Thực phẩm tươi sống, đồ uống',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Dịch vụ',
                'slug' => 'dich-vu',
                'description' => 'Các dịch vụ B2B',
                'is_active' => true,
                'sort_order' => 6,
                'children' => [
                    ['name' => 'Vận chuyển', 'slug' => 'van-chuyen'],
                    ['name' => 'Marketing', 'slug' => 'marketing'],
                    ['name' => 'IT', 'slug' => 'it'],
                ]
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $parent = Category::create($categoryData);

            foreach ($children as $childData) {
                $childData['parent_id'] = $parent->id;
                $childData['is_active'] = true;
                $childData['sort_order'] = 0;
                Category::create($childData);
            }
        }

        $this->command->info('✅ Created categories with subcategories');
    }
}
