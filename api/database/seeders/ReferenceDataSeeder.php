<?php

namespace Database\Seeders;

use App\Models\BusinessConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Real offerings (approved pricing) and the waiver. Safe to run in production. */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $lesson = fn ($id, $name, $description, $minutes, $price, $level, $group, $horses = 1, $riders = 1) => [
            'id' => $id, 'name' => $name, 'description' => $description, 'duration_minutes' => $minutes,
            'price' => $price, 'difficulty_level' => $level, 'max_group_size' => $group,
            'required_horse_count' => $horses, 'required_rider_count' => $riders,
            'active' => true, 'created_at' => $now, 'updated_at' => $now,
        ];

        DB::table('lesson_types')->upsert([
            $lesson('lesson-1', 'Walk & Trot', 'Perfect for first-time riders. Learn basics of saddle, posture, and horse handling.', 30, 45.00, 'beginner', 1),
            $lesson('lesson-2', 'Intermediate Riding', 'Build on fundamentals with canter and basic jumping.', 45, 55.00, 'intermediate', 1),
            $lesson('lesson-3', 'Advanced Horsemanship', 'Advanced techniques and competition preparation.', 60, 65.00, 'advanced', 1),
            // Sibling/Joined: two riders on separate horses.
            $lesson('lesson-4', 'Sibling/Joined Lesson', 'Two riders learning together on separate horses.', 60, 100.00, 'beginner', 2, 2, 2),
            $lesson('lesson-5', 'Extended Group Lesson', 'Extended group lesson with more time for instruction and practice.', 90, 120.00, 'beginner', 2),
            $lesson('lesson-6', 'Barrel Racing', 'Specialized training for barrel racing techniques.', 60, 75.00, 'intermediate', 1),
            $lesson('lesson-7', 'Pole Bending', 'Learn precise weaving patterns and speed control.', 60, 75.00, 'intermediate', 1),
            $lesson('lesson-8', 'Weekly Barrel Training', 'Two 60-minute barrel racing lessons per week for committed riders.', 120, 130.00, 'intermediate', 1),
        ], ['id']);

        DB::table('waivers')->upsert([[
            'id' => 'waiver-1',
            'title' => 'Riding Waiver and Release of Liability',
            'version' => 1,
            'content' => <<<'TXT'
                RELEASE OF LIABILITY

                I acknowledge that horseback riding involves inherent risks including but not limited to falls, injuries, and property damage. I accept full responsibility for these risks.

                I confirm that I am in good physical health and able to participate in this activity. I have disclosed any medical conditions that may affect my ability to ride.

                I release and hold harmless RJ Performance Horses, its owners, instructors, employees, and agents from any liability for injuries or damages sustained during lessons or activities.

                I understand that helmets are required and agree to follow all safety instructions.
                TXT,
            'effective_date' => $now->copy()->timezone(BusinessConfig::timezone())->toDateString(),
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['id']);
    }
}
