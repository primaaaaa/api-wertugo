<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PlaceController extends Controller
{
    // GET /api/places - Menampilkan daftar seluruh tempat wisata
    public function index()
    {
        return response()->json([
            'success' => true,
        ], 200);
    }

    // GET /api/places/{id} - Menampilkan detail tempat wisata
    public function show($id)
    {
        return response()->json([
            'success' => true,
        ], 200);
    }

    // GET /api/places/search - Mencari tempat berdasarkan keyword
    public function search(Request $request)
    {
        $keyword = $request->query('q');
        
        return response()->json([
            'success' => true,
        ], 200);
    }

    // GET /api/places/category/{category} - Filter berdasarkan kategori
    public function filterByCategory($category)
    {
        return response()->json([
            'success' => true,
        ], 200);
    }
}