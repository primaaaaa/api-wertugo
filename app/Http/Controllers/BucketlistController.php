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
    /**
     * GET: /api/bucketlist
     * Mendapatkan daftar seluruh bucket list milik pengguna
     */
    public function index(Request $request)
    {
        $userId = (string) $request->user()->id;
        $myBucketlists = Bucketlist::where('user_id', $userId)->get();

        // Ambil ID bucket list dimana user ini sebagai member
        $memberOf = \App\Models\BucketlistMember::where('user_id', $userId)->pluck('bucketlist_id')->toArray();
        $sharedBucketlists = Bucketlist::whereIn('_id', $memberOf)->get();

        // Gabungkan keduanya
        $allBucketlists = $myBucketlists->merge($sharedBucketlists);

        // Loop untuk menghitung jumlah tempat dan member secara dinamis
        foreach ($allBucketlists as $list) {
            // Hitung jumlah tempat yang ada di bucket list ini
            $list->tempat_count = \App\Models\BucketlistPlace::where('bucketlist_id', $list->id)->count();
            
            // Hitung jumlah anggota (1 Owner + jumlah kontributor yang join)
            $memberCount = \App\Models\BucketlistMember::where('bucketlist_id', $list->id)->count();
            $list->member_count = $memberCount + 1; 
        }

        return response()->json([
            'success' => true,
            'data'    => $allBucketlists
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
    /**
     * POST: /api/bucketlist/{id}/places
     * Menyisipkan destinasi wisata/kuliner baru ke dalam bucket list.
     */
    public function addPlace(Request $request, $id)
    {
        // 1. Sesuaikan validasi untuk menerima input manual dari Android
        $validator = Validator::make($request->all(), [
            'place_id'    => 'nullable|string', // Opsional, jaga-jaga kalau pilih dari database global
            'nama_tempat' => 'required|string|max:255',
            'lokasi'      => 'nullable|string|max:255',
            'deskripsi'   => 'nullable|string',
            'kategori'    => 'nullable|string|max:100',
            'gambar'      => 'nullable|image|mimes:jpeg,png,jpg|max:2048' // Validasi untuk file gambar
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $bucketlist = Bucketlist::find($id);
        if (!$bucketlist) {
            return response()->json(['success' => false, 'message' => 'Bucket list tidak ditemukan.'], 404);
        }

        // 2. Cek akses (Sama seperti fungsi show)
        $isOwner = $bucketlist->user_id === $request->user()->id;
        $isMember = \App\Models\BucketlistMember::where('bucketlist_id', $id)
                        ->where('user_id', $request->user()->id)
                        ->exists();

        if (!$isOwner && !$isMember) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        // 3. Handle upload gambar jika user menyertakan foto
        $gambarPath = null;
        if ($request->hasFile('gambar')) {
            // Akan otomatis disimpan ke folder storage/app/public/bucketlist_places
            $gambarPath = $request->file('gambar')->store('bucketlist_places', 'public');
        }

        // 4. Simpan ke database dengan field yang baru
        $place = \App\Models\BucketlistPlace::create([
            'bucketlist_id'   => $id,
            'place_id'        => $request->place_id,
            'nama_tempat'     => $request->nama_tempat,
            'lokasi'          => $request->lokasi,
            'deskripsi'       => $request->deskripsi,
            'kategori'        => $request->kategori,
            'gambar'          => $gambarPath,
            'personal_rating' => null,
            'comments' => []
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
                    ->where('_id', $place_id) // ✅ UBAH JUGA DI SINI JADI '_id' atau 'id'
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
            'rating'   => 'required|integer|min:1|max:5',
            'comment'  => 'nullable|string' // Tambahkan validasi komentar
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Cari item tempat di dalam bucketlist
        $item = \App\Models\BucketlistPlace::where('bucketlist_id', $id)
                    ->where('_id', $request->place_id) 
                    ->first();

        if (!$item) return response()->json(['success' => false, 'message' => 'Destinasi tidak ditemukan.'], 404);

        // Ambil komentar lama (jika ada)
        $comments = $item->comments ?? [];

        // 1. Ambil data user yang login
        $user = $request->user();

        // 2. JARING PENGAMAN: Cari nama user dari berbagai kemungkinan kolom
        $namaAnggota = $user->name ?? $user->username ?? $user->nama ?? $user->nama_lengkap ?? 'Anggota';

        // Tambahkan komentar baru dari user
        $comments[] = [
            'user_name' => $namaAnggota, // ✅ UBAH 'username' JADI 'user_name' BIAR COCOK DENGAN ANDROID
            'rating'    => (float) $request->rating,
            'comment'   => $request->comment,
            'created_at'=> now()
        ];

        $item->comments = $comments;
        $item->personal_rating = $request->rating;
        $item->save();

        return response()->json(['success' => true, 'message' => 'Penilaian tersimpan.', 'data' => $item]);
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
        $invitee = \App\Models\Account::where('email', $request->email)->first();
        if (!$invitee) {
            return response()->json(['success' => false, 'message' => 'Pengguna dengan email tersebut tidak terdaftar.'], 404);
        }

        // Cek apakah dia mengundang dirinya sendiri
        if ($invitee->id === $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Anda tidak dapat mengundang diri sendiri.'], 400);
        }

        // Cek apakah target sudah menjadi anggota di list ini
        $isMember = \App\Models\BucketlistMember::where('bucketlist_id', (string)$id)
                        ->where('user_id', (string)$invitee->id)->exists();
        if ($isMember) {
            return response()->json(['success' => false, 'message' => 'Pengguna ini sudah menjadi anggota di bucket list Anda.'], 400);
        }

        // Cek apakah undangan sudah pernah dikirim dan masih pending
        $pendingInvite = \App\Models\BucketlistInvitation::where('bucketlist_id', (string)$id)
                            ->where('invitee_id', (string)$invitee->id)
                            ->where('status', 'pending')->exists();
        if ($pendingInvite) {
            return response()->json(['success' => false, 'message' => 'Undangan sudah dikirim sebelumnya dan masih menunggu persetujuan.'], 400);
        }

        // Buat undangan baru
        $invitation = \App\Models\BucketlistInvitation::create([
            'bucketlist_id' => (string)$id,
            'inviter_id'    => (string)$request->user()->id,
            'invitee_id'    => (string)$invitee->id,
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
        $invitations = \App\Models\BucketlistInvitation::where('invitee_id', (string)$request->user()->id)
                            ->where('status', 'pending')
                            ->get();

        // Tambahkan nama pengundang dan judul bucketlist untuk detail notifikasi
        foreach ($invitations as $inv) {
            $inviter = \App\Models\Account::find($inv->inviter_id);
            $bucket = Bucketlist::find($inv->bucketlist_id);
            $inv->inviter_name = $inviter ? $inviter->username : 'Seseorang';
            $inv->bucket_title = $bucket ? $bucket->title : 'Bucket List';
        }

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
        $invitation = \App\Models\BucketlistInvitation::where('bucketlist_id', (string)$id)
                            ->where('invitee_id', (string)$request->user()->id)
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
                'bucketlist_id' => (string)$id,
                'user_id'       => (string)$request->user()->id,
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

    /**
     * PUT: /api/bucketlist/{id}/places/{place_id}
     * Mengubah data destinasi wisata/kuliner di dalam bucket list.
     */
    public function updatePlace(Request $request, $id, $place_id)
    {
        $validator = Validator::make($request->all(), [
            'nama_tempat' => 'required|string|max:255',
            'lokasi'      => 'nullable|string|max:255',
            'deskripsi'   => 'nullable|string',
            'kategori'    => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Cari item menggunakan id atau _id (aman untuk MongoDB)
        $item = \App\Models\BucketlistPlace::where('bucketlist_id', $id)
                    ->where(function($q) use ($place_id) {
                        $q->where('_id', $place_id)->orWhere('id', $place_id);
                    })->first();

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Destinasi tidak ditemukan.'], 404);
        }

        // Update data
        $item->nama_tempat = $request->nama_tempat;
        $item->lokasi = $request->lokasi;
        $item->deskripsi = $request->deskripsi;
        $item->kategori = $request->kategori;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Destinasi berhasil diperbarui.',
            'data'    => $item
        ], 200);
    }
}