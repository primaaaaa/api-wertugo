<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Hash;
use Illuminate\Http\Request;
use Str;

class RoomController extends Controller
{

    public function getAllRoom(){
        $rooms = Room::all();
        return response()->json($rooms);
    }

    public function getRoom($id){
        $room = Room::find($id);
        return response()->json($room);
    }

    // public function addPlace(Request $request){
    //     $uuid 
    // }

    public function createRoom(Request $request){
        $uuid = Str::uuid();
        $room_id = Str::substr($uuid, 26, 5);

        $validated = $request->validate([
            'room_name' => 'required|string|max:255',
            'room_creator' => 'required|string|max:255',
            // 'room_id' => 'required|string|max:6|unique:mongodb.rooms,room_id'
        ]);

        $rooms = Room::create([
          'room_name'  => $validated['room_name'],
          'room_creator' => $validated['room_creator'],
          'room_id' => $room_id
        ]);

        return response()->json([
            'message' => 'Berhasil menambahkan room',
            'data' => $rooms
        ]);
    }
}
