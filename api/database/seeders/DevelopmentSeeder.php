<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fictional development data (ported from supabase/seed.sql). Never run in
 * production; the schedules are samples, not Rylie's real hours.
 */
class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ReferenceDataSeeder::class);
        $now = now();
        $insert = fn (string $table, array $rows) => DB::table($table)->insert(array_map(
            fn ($row) => $row + ['created_at' => $now] + ($table === 'horse_lesson_types' ? [] : ['updated_at' => $now]),
            $rows,
        ));

        $insert('customers', [
            ['id' => 'customer-1', 'first_name' => 'Rebecca', 'last_name' => 'Jensen', 'email' => 'rebecca.jensen.dev@example.com', 'phone' => '555-111-0001'],
            ['id' => 'customer-2', 'first_name' => 'Michael', 'last_name' => 'Brown', 'email' => 'michael.brown.dev@example.com', 'phone' => '555-222-0002'],
            ['id' => 'customer-3', 'first_name' => 'Sarah', 'last_name' => 'Johnson', 'email' => 'sarah.johnson.dev@example.com', 'phone' => '555-333-0003'],
            ['id' => 'customer-4', 'first_name' => 'Lisa', 'last_name' => 'Smith', 'email' => 'lisa.smith.dev@example.com', 'phone' => '555-444-0004'],
        ]);

        $rider = fn ($id, $customer, $first, $last, $email, $dob, $level) => [
            'id' => $id, 'customer_id' => $customer, 'first_name' => $first, 'last_name' => $last,
            'email' => $email, 'date_of_birth' => $dob, 'riding_experience' => $level,
        ];
        $insert('riders', [
            $rider('rider-1', 'customer-1', 'Emma', 'Jensen', 'emma.jensen.dev@example.com', '2010-03-22', 'beginner'),
            $rider('rider-2', 'customer-1', 'Hailey', 'Jensen', 'hailey.jensen.dev@example.com', '2012-08-10', 'beginner'),
            $rider('rider-3', 'customer-2', 'Michael', 'Brown', 'michael.brown.dev@example.com', '1988-11-05', 'advanced'),
            $rider('rider-4', 'customer-3', 'Sarah', 'Johnson', 'sarah.johnson.dev@example.com', '1995-06-15', 'intermediate'),
            $rider('rider-5', 'customer-4', 'Ava', 'Smith', 'ava.smith.dev@example.com', '2008-01-18', 'intermediate'),
        ]);

        $horse = fn ($id, $name, $breed, $age, $color, $height, $temperament, $level) => [
            'id' => $id, 'name' => $name, 'breed' => $breed, 'age' => $age, 'color' => $color,
            'height' => $height, 'temperament' => $temperament, 'experience_level' => $level, 'active' => true,
        ];
        $insert('horses', [
            $horse('horse-1', 'DevSunset', 'Quarter Horse', 8, 'Bay', '15.2hh', 'Gentle and steady, suitable for beginners in development', 'beginner_friendly'),
            $horse('horse-2', 'DevRanger', 'Quarter Horse', 12, 'Palomino', '15.0hh', 'Calm and experienced, perfect for lesson use in development', 'beginner_friendly'),
            $horse('horse-3', 'DevDakota', 'Paint', 6, 'Tobiano', '14.3hh', 'Energetic and responsive for intermediate development training', 'intermediate'),
            $horse('horse-4', 'DevMoonlight', 'Appaloosa', 7, 'Spotted', '14.2hh', 'Sweet and spirited for intermediate development work', 'intermediate'),
            $horse('horse-5', 'DevThunder', 'Quarter Horse', 10, 'Chestnut', '15.1hh', 'Athletic and focused for advanced development training', 'advanced'),
            $horse('horse-6', 'DevSpirit', 'Mustang', 5, 'Bay', '15.0hh', 'Quick learner, barrel racing specialist for development', 'advanced'),
        ]);

        $eligibility = [
            'horse-1' => ['lesson-1', 'lesson-2', 'lesson-4', 'lesson-5'],
            'horse-2' => ['lesson-1', 'lesson-2', 'lesson-3', 'lesson-4', 'lesson-5'],
            'horse-3' => ['lesson-2', 'lesson-6', 'lesson-7'],
            'horse-4' => ['lesson-2', 'lesson-4'],
            'horse-5' => ['lesson-3', 'lesson-6', 'lesson-7', 'lesson-8'],
            'horse-6' => ['lesson-6', 'lesson-7', 'lesson-8'],
        ];
        $rows = [];
        foreach ($eligibility as $horseId => $lessons) {
            foreach ($lessons as $lessonId) {
                $rows[] = ['id' => 'hlt-'.(count($rows) + 1), 'horse_id' => $horseId, 'lesson_type_id' => $lessonId, 'eligible' => true];
            }
        }
        $insert('horse_lesson_types', $rows);

        // Instructor: Mon–Fri 10:00–12:00 and 14:00–18:00.
        $rows = [];
        foreach (range(1, 5) as $day) {
            foreach ([['10:00', '12:00'], ['14:00', '18:00']] as [$start, $end]) {
                $rows[] = ['id' => 'ia-'.(count($rows) + 1), 'day_of_week' => $day, 'start_time' => $start, 'end_time' => $end, 'active' => true];
            }
        }
        $insert('instructor_availability', $rows);

        $schedules = [
            'horse-1' => [[1, 2, 3, 4, 5], [['10:00', '12:00'], ['14:00', '18:00']]],
            'horse-2' => [[1, 2, 3, 4, 5], [['10:00', '12:00'], ['14:00', '18:00']]],
            'horse-3' => [[2, 3, 4], [['10:00', '12:00'], ['14:00', '17:00']]],
            'horse-4' => [[1, 3], [['10:00', '14:00']]],
            'horse-5' => [[2, 4, 5], [['15:00', '18:00']]],
            'horse-6' => [[1, 3, 5], [['10:00', '14:00']]],
        ];
        $rows = [];
        foreach ($schedules as $horseId => [$days, $windows]) {
            foreach ($days as $day) {
                foreach ($windows as [$start, $end]) {
                    $rows[] = ['id' => 'hra-'.(count($rows) + 1), 'horse_id' => $horseId, 'day_of_week' => $day, 'start_time' => $start, 'end_time' => $end, 'active' => true];
                }
            }
        }
        $insert('horse_recurring_availability', $rows);

        $insert('blocked_times', [
            ['id' => 'bt-1', 'horse_id' => 'horse-2', 'date' => $now->copy()->addDays(7)->toDateString(), 'start_time' => '14:00', 'end_time' => '16:00', 'reason' => 'Vet appointment'],
            ['id' => 'bt-2', 'horse_id' => 'horse-5', 'date' => $now->copy()->addDays(14)->toDateString(), 'start_time' => '00:00', 'end_time' => '23:59', 'reason' => 'Competition event'],
        ]);
    }
}
