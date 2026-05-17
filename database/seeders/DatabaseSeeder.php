<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\BreakRecord;
use App\Models\Organization;
use App\Models\PunchEvent;
use App\Models\PunchSession;
use App\Models\SavedReport;
use App\Models\Shift;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake();

        $org = Organization::create([
            'name'     => $faker->company(),
            'timezone' => $faker->randomElement(['Asia/Jakarta', 'Asia/Singapore', 'UTC']),
        ]);

        $sites = [];
        for ($i = 0; $i < 3; $i++) {
            $sites[] = Site::create([
                'org_id'            => $org->id,
                'name'              => $faker->company() . ' Site ' . ($i + 1),
                'address'           => $faker->address(),
                'lat'               => $faker->latitude(-7.6, -6.9),
                'lng'               => $faker->longitude(112.5, 113.1),
                'geofence_radius_m' => $faker->numberBetween(100, 300),
            ]);
        }

        $manager = User::create([
            'org_id'        => $org->id,
            'email'         => 'manager@demo.co',
            'password_hash' => Hash::make('password'),
            'full_name'     => 'Sam Manager',
            'role'          => 'manager',
            'pin_hash'      => Hash::make('1234'),
            'is_active'     => true,
        ]);

        $employees = [];
        $employees[] = User::create([
            'org_id'        => $org->id,
            'email'         => 'employee@demo.co',
            'password_hash' => Hash::make('password'),
            'full_name'     => 'Alex Employee',
            'role'          => 'employee',
            'pin_hash'      => Hash::make('1234'),
            'is_active'     => true,
        ]);

        for ($i = 0; $i < 14; $i++) {
            $employees[] = User::create([
                'org_id'        => $org->id,
                'email'         => $faker->unique()->safeEmail(),
                'password_hash' => Hash::make('password'),
                'full_name'     => $faker->name(),
                'role'          => 'employee',
                'pin_hash'      => Hash::make('1234'),
                'is_active'     => $faker->boolean(95),
                'avatar_url'    => $faker->boolean(30) ? $faker->imageUrl(256, 256, 'people') : null,
                'last_login_at' => $faker->boolean(80) ? now()->subDays($faker->numberBetween(0, 14)) : null,
            ]);
        }

        foreach ($employees as $employee) {
            for ($day = 0; $day < 5; $day++) {
                $date = now()->startOfWeek()->addDays($day);
                $start = $date->copy()->setHour(9)->setMinute(0)->setSecond(0)->addMinutes($faker->numberBetween(-15, 15));
                $end = $start->copy()->addHours(8)->addMinutes($faker->numberBetween(-10, 25));

                Shift::create([
                    'org_id'          => $org->id,
                    'user_id'         => $employee->id,
                    'site_id'         => $faker->randomElement($sites)->id,
                    'scheduled_start' => $start,
                    'scheduled_end'   => $end,
                    'label'           => $faker->randomElement(['Morning', 'Mid', 'Day']) . ' shift',
                    'created_by'      => $manager->id,
                ]);
            }
        }

        $clockMethods = ['qr', 'gps', 'selfie', 'manual'];
        $notificationTypes = ['late', 'shift_reminder', 'punch.clock_in', 'punch.clock_out', 'punch.break_start', 'punch.break_end'];

        foreach ($employees as $employee) {
            $site = $faker->randomElement($sites);
            $sessionsToCreate = $faker->numberBetween(3, 7);

            for ($i = 0; $i < $sessionsToCreate; $i++) {
                $dayOffset = $faker->numberBetween(1, 10);
                $clockIn = now()->subDays($dayOffset)->setTime(8, 0)->addMinutes($faker->numberBetween(0, 45));
                $workMinutes = $faker->numberBetween(360, 540);
                $breakMinutes = $faker->boolean(60) ? $faker->numberBetween(15, 60) : 0;
                $clockOut = $clockIn->copy()->addMinutes($workMinutes + $breakMinutes);

                $session = PunchSession::create([
                    'user_id'          => $employee->id,
                    'site_id'          => $site->id,
                    'shift_id'         => Shift::where('user_id', $employee->id)->inRandomOrder()->value('id'),
                    'state'            => 'clocked_out',
                    'clocked_in_at'    => $clockIn,
                    'clocked_out_at'   => $clockOut,
                    'clock_in_method'  => $faker->randomElement($clockMethods),
                    'clock_out_method' => $faker->randomElement($clockMethods),
                    'clock_in_lat'     => $site->lat + $faker->randomFloat(5, -0.001, 0.001),
                    'clock_in_lng'     => $site->lng + $faker->randomFloat(5, -0.001, 0.001),
                    'work_seconds'     => $workMinutes * 60,
                    'break_seconds'    => $breakMinutes * 60,
                    'overtime_seconds' => max(0, $workMinutes - 480) * 60,
                    'is_flagged'       => $faker->boolean(5),
                ]);

                PunchEvent::create([
                    'session_id'  => $session->id,
                    'user_id'     => $employee->id,
                    'event_type'  => 'clock_in',
                    'occurred_at' => $clockIn,
                    'method'      => $session->clock_in_method,
                    'lat'         => $session->clock_in_lat,
                    'lng'         => $session->clock_in_lng,
                    'is_flagged'  => $session->is_flagged,
                ]);

                if ($breakMinutes > 0) {
                    $breakStart = $clockIn->copy()->addMinutes($faker->numberBetween(120, 240));
                    $breakEnd = $breakStart->copy()->addMinutes($breakMinutes);

                    BreakRecord::create([
                        'session_id'       => $session->id,
                        'started_at'       => $breakStart,
                        'ended_at'         => $breakEnd,
                        'duration_seconds' => $breakMinutes * 60,
                    ]);

                    PunchEvent::create([
                        'session_id'  => $session->id,
                        'user_id'     => $employee->id,
                        'event_type'  => 'break_start',
                        'occurred_at' => $breakStart,
                        'method'      => $session->clock_in_method,
                        'is_flagged'  => false,
                    ]);

                    PunchEvent::create([
                        'session_id'  => $session->id,
                        'user_id'     => $employee->id,
                        'event_type'  => 'break_end',
                        'occurred_at' => $breakEnd,
                        'method'      => $session->clock_in_method,
                        'is_flagged'  => false,
                    ]);
                }

                PunchEvent::create([
                    'session_id'  => $session->id,
                    'user_id'     => $employee->id,
                    'event_type'  => 'clock_out',
                    'occurred_at' => $clockOut,
                    'method'      => $session->clock_out_method,
                    'is_flagged'  => false,
                ]);
            }

            if ($faker->boolean(40)) {
                AppNotification::create([
                    'user_id'  => $employee->id,
                    'type'     => $faker->randomElement($notificationTypes),
                    'title'    => $faker->sentence(4),
                    'body'     => $faker->sentence(12),
                    'payload'  => ['demo' => true],
                    'is_read'  => $faker->boolean(50),
                    'sent_at'  => now()->subDays($faker->numberBetween(0, 7)),
                ]);
            }
        }

        for ($i = 0; $i < 3; $i++) {
            $start = now()->subWeeks($i + 1)->startOfWeek();
            $end = $start->copy()->addDays(6);

            SavedReport::create([
                'org_id'           => $org->id,
                'created_by'       => $manager->id,
                'title'            => $faker->randomElement(['Payroll Summary', 'Attendance Rollup', 'Overtime Snapshot']),
                'report_type'      => $faker->randomElement(['payroll', 'attendance', 'overtime']),
                'date_range_start' => $start,
                'date_range_end'   => $end,
                'last_run_at'      => now()->subDays($faker->numberBetween(1, 10)),
            ]);
        }

        $this->command->info('Seeded demo data with faker.');
        $this->command->info('Manager login: manager@demo.co / password / PIN: 1234');
        $this->command->info('Employee login: employee@demo.co / password / PIN: 1234');
    }
}
