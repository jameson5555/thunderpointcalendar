<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\LivingArea;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccessibilityTestSeeder extends Seeder
{
    public function run(): void
    {
        $member = User::query()->updateOrCreate(
            ['email' => 'member@example.test'],
            [
                'name' => 'Standard Member',
                'password' => Hash::make('Accessibility-Test-Password'),
                'site_role' => 'standard',
                'approved_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        $area = LivingArea::query()->where('slug', 'boathouse')->firstOrFail();
        Booking::query()->updateOrCreate(
            ['booking_group' => 'a11y-unavailable-range', 'living_area_id' => $area->id],
            [
                'created_by' => $member->id,
                'approved_by' => User::query()->where('site_role', 'admin')->value('id'),
                'guest_name' => 'Existing Guest',
                'start_date' => '2030-06-15',
                'end_date' => '2030-06-17',
                'status' => Booking::STATUS_ACTIVE,
                'amount_cents' => 6000,
                'payment_status' => Booking::PAYMENT_UNPAID,
                'payment_method' => 'pay_later',
                'approved_at' => now(),
            ]
        );
    }
}
