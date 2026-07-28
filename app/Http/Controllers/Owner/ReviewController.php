<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\Owner\OwnerAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $q            = trim($request->input('q', ''));
        $filterRating = $request->input('rating', '');
        $filterState  = $request->input('state', '');

        $query = Review::query()
            ->with(['branch.company', 'customer'])
            ->latest('id');

        if ($q !== '') {
            $query->where('comment', 'like', "%{$q}%");
        }

        if ($filterRating !== '') {
            $query->where('rating', (int) $filterRating);
        }

        if ($filterState === 'hidden') {
            $query->where('is_hidden', true);
        } elseif ($filterState === 'visible') {
            $query->where('is_hidden', false);
        }

        $reviews = $query->paginate(20)->withQueryString();

        $stats = [
            'total'   => Review::query()->count(),
            'average' => round((float) Review::query()->where('is_hidden', false)->avg('rating'), 1),
            'hidden'  => Review::query()->where('is_hidden', true)->count(),
        ];

        return view('owner.reviews.index', compact('reviews', 'stats', 'q', 'filterRating', 'filterState'));
    }

    public function toggleHidden(Request $request, Review $review): RedirectResponse
    {
        if ($review->is_hidden) {
            $review->fill(['is_hidden' => false, 'hidden_reason' => null]);
            OwnerAudit::recordChanges('review.show', $review, label: __('Review #:id', ['id' => $review->id]));
            $review->save();

            return redirect()->back()->with('success', __('Review is visible again.'));
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $review->fill(['is_hidden' => true, 'hidden_reason' => $validated['reason']]);
        OwnerAudit::recordChanges('review.hide', $review, $validated['reason'], __('Review #:id', ['id' => $review->id]));
        $review->save();

        return redirect()->back()->with('success', __('Review hidden from the public site.'));
    }
}
