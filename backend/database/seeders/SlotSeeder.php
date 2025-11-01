<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('slots')->insert([
            [
                'name' => 'Morning Slot',
                'capacity' => 10,
                'remaining' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Afternoon Slot',
                'capacity' => 5,
                'remaining' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Evening Slot',
                'capacity' => 15,
                'remaining' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

