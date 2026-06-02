<?php

namespace App\Http\Controllers;

use App\Models\Bucketlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BucketlistController extends Controller
{
    /**
     * POST: /api/bucketlist
     * Membuat dokumen wadah bucket list baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $bucketlist = Bucketlist::create([
            'user_id'     => $request->user()->id,
            'title'       => $request->title,
            'description' => $request->description ?? ''
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bucket list berhasil dibuat.',
            'data'    => $bucketlist
        ], 201);
    }

    /**
     * GET: /api/bucketlist
     * Mendapatkan daftar seluruh bucket list milik pengguna
     */
    public function index(Request $request)
    {
        // Ambil bucket list yang dibuat sendiri
        $myBucketlists = Bucketlist::where('user_id', $request->user()->id)->get();

        // Nanti kita bisa tambahkan logika untuk mengambil bucket list dari grup kolaborasi di sini

        return response()->json([
            'success' => true,
            'data'    => $myBucketlists
        ], 200);
    }

    /**
     * GET: /api/bucketlist/{id}
     * Melihat informasi detail dan daftar item tempat di dalam suatu bucket list.
     */
    public function show(Request $request, $id)
    {
        $bucketlist = Bucketlist::find($id);

        if (!$bucketlist) {
            return response()->json(['success' => false, 'message' => 'Bucket list tidak ditemukan.'], 404);
        }

        // Cek akses: Apakah ini milik dia atau dia adalah member?
        $isOwner = $bucketlist->user_id === $request->user()->id;
        $isMember = \App\Models\BucketlistMember::where('bucketlist_id', $id)
                        ->where('user_id', $request->user()->id)
                        ->exists();

        if (!$isOwner && !$isMember) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        // Ambil daftar tempat wisata yang ada di list ini
        $places = \App\Models\BucketlistPlace::where('bucketlist_id', $id)->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'info'   => $bucketlist,
                'places' => $places
            ]
        ], 200);
    }

    /**
     * POST: /api/bucketlist/{id}/places
     * Menyisipkan destinasi wisata/kuliner baru ke dalam bucket list.
     */
    public function addPlace(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'place_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $bucketlist = Bucketlist::find($id);
        if (!$bucketlist) {
            return response()->json(['success' => false, 'message' => 'Bucket list tidak ditemukan.'], 404);
        }

        // Cek akses (Sama seperti fungsi show)
        $isOwner = $bucketlist->user_id === $request->user()->id;
        $isMember = \App\Models\BucketlistMember::where('bucketlist_id', $id)
                        ->where('user_id', $request->user()->id)
                        ->exists();

        if (!$isOwner && !$isMember) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $place = \App\Models\BucketlistPlace::create([
            'bucketlist_id'   => $id,
            'place_id'        => $request->place_id,
            'personal_rating' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Destinasi berhasil ditambahkan ke bucket list.',
            'data'    => $place
        ], 201);
    }

    /**
     * DELETE: /api/bucketlist/{id}/places/{place_id}
     * Mengeluarkan item destinasi dari daftar bucket list.
     */
    public function removePlace(Request $request, $id, $place_id)
    {
        $bucketlist = Bucketlist::find($id);
        
        // Cek akses
        $isOwner = $bucketlist && $bucketlist->user_id === $request->user()->id;
        $isMember = \App\Models\BucketlistMember::where('bucketlist_id', $id)
                        ->where('user_id', $request->user()->id)
                        ->exists();

        if (!$isOwner && !$isMember) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $item = \App\Models\BucketlistPlace::where('bucketlist_id', $id)
                    ->where('place_id', $place_id)
                    ->first();

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Destinasi tidak ditemukan di bucket list ini.'], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Destinasi berhasil dihapus dari bucket list.'
        ], 200);
    }

    /**
     * POST: /api/bucketlist/{id}/rating
     * Memberikan penilaian personal internal tanpa mengubah skor rating publik.
     */
    public function personalRating(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'place_id' => 'required|string',
            'rating'   => 'required|integer|min:1|max:5'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Cari item tempat di dalam bucketlist
        $item = \App\Models\BucketlistPlace::where('bucketlist_id', $id)
                    ->where('place_id', $request->place_id)
                    ->first();

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Destinasi tidak ditemukan di bucket list ini.'], 404);
        }

        $item->personal_rating = $request->rating;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Rating personal berhasil disimpan.',
            'data'    => $item
        ], 200);
    }

    /**
     * POST: /api/bucketlist/{id}/invite
     * Mengundang teman via email untuk berkolaborasi dalam satu list.
     */
    public function inviteUser(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $bucketlist = Bucketlist::find($id);
        
        // Cek apakah list ada dan apakah user adalah pemiliknya
        if (!$bucketlist || $bucketlist->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Bucket list tidak ditemukan atau Anda tidak memiliki akses untuk mengundang.'], 403);
        }

        // Cari target user berdasarkan email
        $invitee = \App\Models\User::where('email', $request->email)->first();
        if (!$invitee) {
            return response()->json(['success' => false, 'message' => 'Pengguna dengan email tersebut tidak terdaftar.'], 404);
        }

        // Cek apakah dia mengundang dirinya sendiri
        if ($invitee->id === $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Anda tidak dapat mengundang diri sendiri.'], 400);
        }

        // Cek apakah target sudah menjadi anggota di list ini
        $isMember = \App\Models\BucketlistMember::where('bucketlist_id', $id)
                        ->where('user_id', $invitee->id)->exists();
        if ($isMember) {
            return response()->json(['success' => false, 'message' => 'Pengguna ini sudah menjadi anggota di bucket list Anda.'], 400);
        }

        // Cek apakah undangan sudah pernah dikirim dan masih pending
        $pendingInvite = \App\Models\BucketlistInvitation::where('bucketlist_id', $id)
                            ->where('invitee_id', $invitee->id)
                            ->where('status', 'pending')->exists();
        if ($pendingInvite) {
            return response()->json(['success' => false, 'message' => 'Undangan sudah dikirim sebelumnya dan masih menunggu persetujuan.'], 400);
        }

        // Buat undangan baru
        $invitation = \App\Models\BucketlistInvitation::create([
            'bucketlist_id' => $id,
            'inviter_id'    => $request->user()->id,
            'invitee_id'    => $invitee->id,
            'status'        => 'pending' // Status awal
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Undangan kolaborasi berhasil dikirim.',
            'data'    => $invitation
        ], 201);
    }

    /**
     * GET: /api/bucketlist/invitations
     * Melihat daftar list undangan kolaborasi masuk yang belum ditanggapi oleh user yang login.
     */
    public function getInvitations(Request $request)
    {
        $invitations = \App\Models\BucketlistInvitation::where('invitee_id', $request->user()->id)
                            ->where('status', 'pending')
                            ->get();

        return response()->json([
            'success' => true,
            'data'    => $invitations
        ], 200);
    }

    /**
     * POST: /api/bucketlist/{id}/join
     * Menerima atau menolak undangan untuk bergabung ke dalam akses shared bucket list.
     */
    public function joinSharedList(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'accept' => 'required|boolean' // Kirim true untuk Join, false untuk Tolak
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Cari undangan yang ditujukan untuk user ini dan masih pending
        $invitation = \App\Models\BucketlistInvitation::where('bucketlist_id', $id)
                            ->where('invitee_id', $request->user()->id)
                            ->where('status', 'pending')
                            ->first();

        if (!$invitation) {
            return response()->json(['success' => false, 'message' => 'Undangan tidak valid atau sudah ditanggapi sebelumnya.'], 404);
        }

        if ($request->accept) {
            // Update status undangan
            $invitation->status = 'accepted';
            $invitation->save();

            // Tambahkan user sebagai member/kontributor
            $member = \App\Models\BucketlistMember::create([
                'bucketlist_id' => $id,
                'user_id'       => $request->user()->id,
                'role'          => 'contributor' // Role default teman
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Anda telah resmi bergabung ke bucket list ini.',
                'data'    => $member
            ], 200);
        } else {
            // Jika ditolak
            $invitation->status = 'rejected';
            $invitation->save();

            return response()->json([
                'success' => true,
                'message' => 'Undangan kolaborasi berhasil ditolak.'
            ], 200);
        }
    }

    /**
     * GET: /api/bucketlist/{id}/members
     * Menampilkan daftar seluruh kontributor/anggota di dalam shared bucket list.
     */
    public function getMembers(Request $request, $id)
    {
        $bucketlist = Bucketlist::find($id);

        if (!$bucketlist) {
            return response()->json(['success' => false, 'message' => 'Bucket list tidak ditemukan.'], 404);
        }

        // Cek akses: hanya owner atau member yang bisa melihat daftar anggotanya
        $isOwner = $bucketlist->user_id === $request->user()->id;
        $isMember = \App\Models\BucketlistMember::where('bucketlist_id', $id)
                        ->where('user_id', $request->user()->id)
                        ->exists();

        if (!$isOwner && !$isMember) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        // Tarik data seluruh anggota
        $members = \App\Models\BucketlistMember::where('bucketlist_id', $id)->get();

        return response()->json([
            'success' => true,
            'owner_id' => $bucketlist->user_id,
            'members'  => $members
        ], 200);
    }
}