<?php

namespace Database\Seeders;

use App\Models\BusinessConfig;
use App\Models\LessonType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultLessonTypesSeeder extends Seeder
{
    public function run(): void
    {
        $lessons = [
            ['private-30', '30 Minute Private', 'Perfect for first-time riders. Learn basics of saddle, posture, and horse handling.', 30, 45, 'beginner', 1],
            ['private-45', '45 Minute Private', 'Build on fundamentals with canter and basic jumping techniques.', 45, 55, 'intermediate', 1],
            ['private-60', '60 Minute Private', 'Advanced techniques and competition preparation for experienced riders.', 60, 65, 'advanced', 1],
            ['group-60', 'Group Lesson (60 min)', 'Two riders learning together on separate horses. Perfect for siblings or friends.', 60, 100, 'beginner', 2],
            ['barrel-60', 'Barrel Racing (60 min)', 'Learn proper barrel racing techniques and speed control.', 60, 75, 'intermediate', 1],
            ['barrel-90', 'Barrel Racing (90 min)', 'Extended session for deeper practice and refinement.', 90, 100, 'intermediate', 1],
        ];

        DB::transaction(function () use ($lessons): void {
            BusinessConfig::query()->whereKey('business_timezone')->lockForUpdate()->firstOrFail();
            foreach ($lessons as [$key, $name, $description, $minutes, $price, $level, $count]) {
                if (LessonType::query()->whereKey('default-'.$key)
                    ->orWhereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->exists()) {
                    continue;
                }
                LessonType::create([
                    'id' => 'default-'.$key, 'name' => $name, 'description' => $description,
                    'duration_minutes' => $minutes, 'price' => $price, 'difficulty_level' => $level,
                    'required_rider_count' => $count, 'required_horse_count' => $count,
                    'max_group_size' => $count, 'active' => true,
                ]);
            }
        });
    }
}
