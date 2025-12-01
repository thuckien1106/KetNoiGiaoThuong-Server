<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;

/**
 * Statistics & Reports Controller
 * 
 * Thống kê và báo cáo cho người dùng
 */
class ReportController extends BaseApiController
{
    /**
     * GET /api/stats/overview
     * Thống kê tổng quan
     */
    public function overview(Request $request)
    {
        $user = $request->user();

        // Chỉ seller và admin mới có thống kê
        if (!$user->canCreateListing()) {
            return $this->fail(['message' => 'Chỉ người bán mới có quyền xem thống kê'], 403);
        }

        $stats = [
            'total_listings' => Listing::where('user_id', $user->id)->count(),
            'active_listings' => Listing::where('user_id', $user->id)
                ->where('is_active', true)
                ->where('status', 'published')
                ->count(),
            'total_views' => Listing::where('user_id', $user->id)
                ->sum('views_count') ?? 0,
            'total_orders' => Order::where('seller_id', $user->id)->count(),
            'total_revenue' => Order::where('seller_id', $user->id)
                ->where('status', 'completed')
                ->sum('total_amount') ?? 0,
            'pending_orders' => Order::where('seller_id', $user->id)
                ->where('status', 'pending')
                ->count(),
            'rating_average' => $user->stores()->avg('rating_average') ?? 0,
        ];

        return $this->ok($stats);
    }

    /**
     * GET /api/stats/views
     * Thống kê lượt xem
     */
    public function views(Request $request)
    {
        $user = $request->user();

        if (!$user->canCreateListing()) {
            return $this->fail(['message' => 'Chỉ người bán mới có quyền xem thống kê'], 403);
        }

        $v = $request->validate([
            'period' => 'nullable|in:7days,30days,90days,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'listing_id' => 'nullable|integer|exists:listings,id',
        ]);

        $period = $v['period'] ?? '7days';
        
        // Xác định khoảng thời gian
        switch ($period) {
            case '7days':
                $startDate = now()->subDays(7);
                break;
            case '30days':
                $startDate = now()->subDays(30);
                break;
            case '90days':
                $startDate = now()->subDays(90);
                break;
            case 'custom':
                $startDate = $v['start_date'] ?? now()->subDays(30);
                break;
            default:
                $startDate = now()->subDays(7);
        }

        $endDate = $v['end_date'] ?? now();

        $query = Listing::where('user_id', $user->id);

        if (!empty($v['listing_id'])) {
            $query->where('id', $v['listing_id']);
        }

        $listings = $query->get(['id', 'title', 'views_count']);

        return $this->ok([
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'listings' => $listings,
            'total_views' => $listings->sum('views_count'),
        ]);
    }

    /**
     * GET /api/stats/revenue
     * Thống kê doanh thu
     */
    public function revenue(Request $request)
    {
        $user = $request->user();

        if (!$user->canCreateListing()) {
            return $this->fail(['message' => 'Chỉ người bán mới có quyền xem thống kê'], 403);
        }

        $v = $request->validate([
            'period' => 'nullable|in:7days,30days,90days,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $period = $v['period'] ?? '30days';
        
        switch ($period) {
            case '7days':
                $startDate = now()->subDays(7);
                break;
            case '30days':
                $startDate = now()->subDays(30);
                break;
            case '90days':
                $startDate = now()->subDays(90);
                break;
            case 'custom':
                $startDate = $v['start_date'] ?? now()->subDays(30);
                break;
            default:
                $startDate = now()->subDays(30);
        }

        $endDate = $v['end_date'] ?? now();

        $orders = Order::where('seller_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN total_amount ELSE 0 END) as completed_revenue')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $summary = [
            'total_orders' => Order::where('seller_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'total_revenue' => Order::where('seller_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total_amount'),
            'completed_revenue' => Order::where('seller_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->sum('total_amount'),
        ];

        return $this->ok([
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'summary' => $summary,
            'daily_data' => $orders,
        ]);
    }

    /**
     * GET /api/stats/promotions
     * Báo cáo hiệu quả quảng cáo
     */
    public function promotions(Request $request)
    {
        $user = $request->user();

        if (!$user->canCreateListing()) {
            return $this->fail(['message' => 'Chỉ người bán mới có quyền xem thống kê'], 403);
        }

        $v = $request->validate([
            'promotion_id' => 'nullable|integer|exists:promotions,id',
        ]);

        $query = Promotion::query();

        // Lọc theo user's listings hoặc stores
        $query->where(function ($q) use ($user) {
            $q->whereHas('listing', function ($q2) use ($user) {
                $q2->where('user_id', $user->id);
            })->orWhereHas('store', function ($q2) use ($user) {
                $q2->where('owner_id', $user->id);
            });
        });

        if (!empty($v['promotion_id'])) {
            $query->where('id', $v['promotion_id']);
        }

        $promotions = $query->with(['listing', 'store'])
            ->get()
            ->map(function ($promo) {
                return [
                    'id' => $promo->id,
                    'type' => $promo->type,
                    'budget' => $promo->budget,
                    'duration_days' => $promo->duration_days,
                    'starts_at' => $promo->starts_at,
                    'ends_at' => $promo->ends_at,
                    'status' => $promo->status,
                    'impressions' => $promo->impressions ?? 0,
                    'clicks' => $promo->clicks ?? 0,
                    'ctr' => $promo->impressions > 0 
                        ? round(($promo->clicks / $promo->impressions) * 100, 2) 
                        : 0,
                    'listing' => $promo->listing ? [
                        'id' => $promo->listing->id,
                        'title' => $promo->listing->title,
                    ] : null,
                ];
            });

        return $this->ok([
            'promotions' => $promotions,
            'summary' => [
                'total_promotions' => $promotions->count(),
                'total_budget' => $promotions->sum('budget'),
                'total_impressions' => $promotions->sum('impressions'),
                'total_clicks' => $promotions->sum('clicks'),
                'average_ctr' => $promotions->avg('ctr'),
            ],
        ]);
    }
}
