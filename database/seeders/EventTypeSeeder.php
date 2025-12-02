<?php

namespace Database\Seeders;

use App\Models\EventType;
use Illuminate\Database\Seeder;

class EventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultTypes = [
            'Birthday',
            'Christmas',
            'Anniversary',
            'Wedding',
            'Baby Shower',
            'Graduation',
            'Housewarming',
        ];

        foreach ($defaultTypes as $type) {
            EventType::firstOrCreate(
                ['name' => $type],
                ['is_custom' => false]
            );
        }
    }
}
