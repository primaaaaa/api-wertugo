<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Comment; // Kita panggil model Comment milikmu
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    /**
     * POST: /api/places/{id}/rating
     * Mengirim skor rating numerik (1-5) untuk destinasi terkait.
     */
    public function storeRating(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'score' => 'required|integer|min:1|max:5'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Kita gunakan updateOrCreate agar 1 user hanya bisa memberi 1 rating (bisa di-update jika berubah pikiran)
        $rating = Rating::updateOrCreate(
            ['user_id' => $request->user()->id, 'place_id' => $id],
            ['score' => $request->score]
        );

        return response()->json([
            'success' => true,
            'message' => 'Rating berhasil disimpan.',
            'data'    => $rating
        ], 200);
    }

    /**
     * GET: /api/places/{id}/reviews
     * Melihat seluruh daftar rating dan komentar ulasan dari publik.
     */
    public function getReviews($id)
    {
        // 1. Tarik semua data rating untuk tempat ini
        $ratings = Rating::where('place_id', $id)->get();
        
        // 2. Hitung statistik rating secara otomatis
        $average = $ratings->avg('score');
        $totalRatings = $ratings->count();

        // 3. Tarik data komentar tekstual dari tabel komentar (Asumsi field-nya place_id tapi di DB diset ke umkm_id)
        $comments = Comment::with('user')
            ->where('umkm_id', $id)
            ->where('status', 'active')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'summary' => [
                    'average_rating' => round($average, 1),
                    'total_ratings'  => $totalRatings,
                    'total_comments' => $comments->count()
                ],
                'ratings'  => $ratings,
                'comments' => $comments
            ]
        ], 200);
    }
}