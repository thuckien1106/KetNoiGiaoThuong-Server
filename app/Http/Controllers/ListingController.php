<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Http\Requests\ListingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListingController extends Controller
{
    /**
     * GET - Lấy danh sách bài đăng
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Listing::with(['category', 'store'])->active();

            // Tìm kiếm theo tiêu đề
            if ($request->has('search') && $request->search) {
                $query->search($request->search);
            }

            // Lọc theo category_id
            if ($request->has('category_id') && $request->category_id) {
                $query->byCategory($request->category_id);
            }

            // Lọc theo store_id
            if ($request->has('store_id') && $request->store_id) {
                $query->byStore($request->store_id);
            }

            // Phân trang
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 15);
            $listings = $query->orderBy('created_at', 'desc')
                            ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'data' => $listings->items(),
                'pagination' => [
                    'current_page' => $listings->currentPage(),
                    'per_page' => $listings->perPage(),
                    'total' => $listings->total(),
                    'last_page' => $listings->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * POST - Thêm bài đăng mới
     */
    public function store(ListingRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Kiểm tra quyền: chỉ seller và admin mới được đăng tin
            if (!$user->canCreateListing()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Chỉ người bán (seller) mới có quyền đăng tin. Vui lòng nâng cấp tài khoản.'
                ], 403);
            }

            DB::beginTransaction();

            $data = $request->validated();
            $data['user_id'] = $user->id;

            $listing = Listing::create($data);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bài đăng đã được tạo thành công',
                'data' => $listing
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT - Cập nhật bài đăng
     */
    public function update(ListingRequest $request, Listing $listing): JsonResponse
    {
        try {
            $user = $request->user();

            // Kiểm tra quyền: chỉ chủ listing hoặc admin mới được cập nhật
            if ($listing->user_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền cập nhật bài đăng này'
                ], 403);
            }

            DB::beginTransaction();

            $listing->update($request->validated());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bài đăng đã được cập nhật thành công',
                'data' => $listing->fresh(['category', 'store'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE - Xóa bài đăng
     */
    public function destroy(Request $request, Listing $listing): JsonResponse
    {
        try {
            $user = $request->user();

            // Kiểm tra quyền: chỉ chủ listing hoặc admin mới được xóa
            if ($listing->user_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xóa bài đăng này'
                ], 403);
            }

            DB::beginTransaction();

            // Kiểm tra xem bài đăng có đang trong chiến dịch quảng cáo không
            if ($listing->hasActivePromotions()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không thể xóa vì bài đăng đang trong chiến dịch quảng cáo'
                ], 409);
            }

            $listing->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bài đăng đã được xóa thành công'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi máy chủ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT - Admin duyệt bài đăng
     */
    public function approve(Request $request, Listing $listing): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chỉ admin mới có quyền duyệt bài đăng'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:published,rejected',
            'reason' => 'nullable|string|max:500',
        ]);

        $listing->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $validated['status'] === 'published' 
                ? 'Bài đăng đã được duyệt' 
                : 'Bài đăng đã bị từ chối',
            'data' => $listing
        ]);
    }

    /**
     * GET - Hiển thị thông tin chi tiết bài đăng
     */
    public function show(Listing $listing): JsonResponse
    {
        $listing->load(['category', 'store']);

        return response()->json([
            'status' => 'success',
            'data' => $listing
        ]);
    }
}