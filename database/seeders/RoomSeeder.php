<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks to allow delete
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing rooms
        Room::query()->delete();
        
        // Reset auto-increment
        DB::statement('ALTER TABLE rooms AUTO_INCREMENT=1;');
        
        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $rooms = [
            // Laboratories (301-304)
            ['name' => 'Room 301', 'type' => 'laboratory', 'capacity' => 30],
            ['name' => 'Room 302', 'type' => 'laboratory', 'capacity' => 30],
            ['name' => 'Room 303', 'type' => 'laboratory', 'capacity' => 30],
            ['name' => 'Room 304', 'type' => 'laboratory', 'capacity' => 30],
            
            // Lecture rooms (201-203)
            ['name' => 'Room 201', 'type' => 'lecture', 'capacity' => 40],
            ['name' => 'Room 202', 'type' => 'lecture', 'capacity' => 40],
            ['name' => 'Room 203', 'type' => 'lecture', 'capacity' => 40],
        ];

        foreach ($rooms as $room) {
            Room::create($room);
        }
    }
}