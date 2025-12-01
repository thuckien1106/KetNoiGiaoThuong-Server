<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use App\Models\Auction;
use App\Models\AuctionBid;

/**
 * BA 3.6 — API Auctions
 *
 * - GET    /api/auctions?status=active|paused|closed
 * - GET    /api/auctions/{id}
 * - POST   /api/auctions
 * - PUT    /api/auctions/{id}
 * - DELETE /api/auctions/{id}
 * - POST   /api/auctions/{id}/bids
 * - GET    /api/auctions/{id}/bids
 * - GET    /api/auctions/my-bids
 */
class AuctionController extends BaseApiController
{
    /**
     * GET /api/auctions
     */
    public function index(Request $request)
    {
        $v = $request->validate([
            'status'   => 'nullable|in:active,paused,closed',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $q = Auction::query()->with(['listing', 'createdBy']);

        if (!empty($v['status'])) {
            $q->where('status', $v['status']);
        }

        $items = $q->orderBy('starts_at', 'desc')
            ->paginate($v['per_page'] ?? 20);

        return $this->paginate($items);
    }

    /**
     * GET /api/auctions/{auction}
     */
    public function show(Auction $auction)
    {
        $auction->load(['listing', 'bids.user', 'createdBy']);
        
        // Thêm thông tin người đấu giá cao nhất
        $highestBid = $auction->bids()->orderBy('amount_cents', 'desc')->first();
        $auction->highest_bidder = $highestBid ? $highestBid->user : null;
        $auction->total_bids = $auction->bids()->count();

        return $this->ok($auction);
    }

    /**
     * POST /api/auctions
     * Tạo phiên đấu giá từ listing
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Kiểm tra quyền: chỉ seller và admin mới được tạo đấu giá
        if (!$user->canCreateListing()) {
            return $this->fail(['message' => 'Chỉ người bán (seller) mới có quyền tạo đấu giá'], 403);
        }
        
        $v = $request->validate([
            'listing_id' => 'required|integer|exists:listings,id',
            'starting_price' => 'required|numeric|min:0',
            'starts_at' => 'required|date|after:now',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        // Kiểm tra listing có thuộc về user không
        $listing = \App\Models\Listing::findOrFail($v['listing_id']);
        if ($listing->user_id !== $user->id && !$user->isAdmin()) {
            return $this->fail(['message' => 'Bạn không có quyền tạo đấu giá cho tin đăng này'], 403);
        }

        // Kiểm tra listing đã có auction chưa
        if ($listing->auction()->exists()) {
            return $this->fail(['message' => 'Tin đăng này đã có phiên đấu giá'], 422);
        }

        $auction = Auction::create([
            'listing_id' => $v['listing_id'],
            'starting_price_cents' => $v['starting_price'] * 100,
            'current_price_cents' => $v['starting_price'] * 100,
            'starts_at' => $v['starts_at'],
            'ends_at' => $v['ends_at'],
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        return $this->created($auction->load('listing'));
    }

    /**
     * PUT /api/auctions/{auction}
     */
    public function update(Request $request, Auction $auction)
    {
        $user = $request->user();
        
        // Chỉ người tạo mới được cập nhật
        if ($auction->created_by !== $user->id) {
            return $this->fail(['message' => 'Bạn không có quyền cập nhật phiên đấu giá này'], 403);
        }

        $v = $request->validate([
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'status' => 'nullable|in:active,paused,closed',
        ]);

        $auction->update($v);

        return $this->ok($auction->load('listing'));
    }

    /**
     * DELETE /api/auctions/{auction}
     */
    public function destroy(Auction $auction)
    {
        $user = request()->user();
        
        if ($auction->created_by !== $user->id) {
            return $this->fail(['message' => 'Bạn không có quyền xóa phiên đấu giá này'], 403);
        }

        // Chỉ xóa được nếu chưa có bid
        if ($auction->bids()->count() > 0) {
            return $this->fail(['message' => 'Không thể xóa phiên đấu giá đã có người đặt giá'], 422);
        }

        $auction->delete();

        return $this->noContent();
    }

    /**
     * POST /api/auctions/{auction}/bids
     */
    public function placeBid(Request $request, Auction $auction)
    {
        $user = $request->user();

        $v = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $amountCents = $v['amount'] * 100;

        // Kiểm tra trạng thái
        if ($auction->status !== 'active') {
            return $this->fail(['message' => 'Phiên đấu giá không hoạt động'], 422);
        }

        // Kiểm tra thời gian
        $now = now();
        if ($now < $auction->starts_at || $now > $auction->ends_at) {
            return $this->fail(['message' => 'Phiên đấu giá đã kết thúc hoặc chưa bắt đầu'], 422);
        }

        // Kiểm tra giá
        if ($amountCents <= $auction->current_price_cents) {
            return $this->fail(['message' => 'Giá đặt phải lớn hơn giá hiện tại'], 422);
        }

        $bid = AuctionBid::create([
            'auction_id'   => $auction->id,
            'user_id'      => $user->id,
            'amount_cents' => $amountCents,
        ]);

        $auction->current_price_cents = $amountCents;
        $auction->save();

        return $this->created($bid->load('user'));
    }

    /**
     * GET /api/auctions/{auction}/bids
     */
    public function getBids(Auction $auction)
    {
        $bids = $auction->bids()
            ->with('user')
            ->orderBy('amount_cents', 'desc')
            ->paginate(20);

        return $this->paginate($bids);
    }

    /**
     * GET /api/auctions/my-bids
     */
    public function myBids(Request $request)
    {
        $user = $request->user();
        
        $bids = AuctionBid::where('user_id', $user->id)
            ->with(['auction.listing'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->paginate($bids);
    }
}
