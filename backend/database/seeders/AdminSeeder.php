<?php

namespace Database\Seeders;

use App\Models\Flag;
use App\Models\Item;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the administrator account exists with the correct identity.
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'student_id' => 'STAFF-0001',
                'college_program' => 'Student Affairs Office',
                'contact_number' => '09170000000',
                'role' => 'admin',
                'password' => 'admin123',
            ],
        );
        $admin->update(['first_name' => 'Admin', 'last_name' => 'User', 'role' => 'admin']);

        // A couple of extra sample users so User Management has data to manage.
        $kevin = User::firstOrCreate(
            ['email' => 'kevin.tan@university.edu'],
            [
                'first_name' => 'Kevin',
                'last_name' => 'Tan',
                'student_id' => '2022-00321',
                'college_program' => 'BS Mechanical Engineering',
                'contact_number' => '09181112233',
                'role' => 'student',
                'password' => 'password',
            ],
        );

        $bea = User::firstOrCreate(
            ['email' => 'bea.lim@university.edu'],
            [
                'first_name' => 'Bea',
                'last_name' => 'Lim',
                'student_id' => '2023-00555',
                'college_program' => 'BS Accountancy',
                'contact_number' => '09184445566',
                'role' => 'student',
                'password' => 'password',
            ],
        );

        // Default platform settings.
        $settings = [
            'platform_name' => 'CampusFind',
            'contact_email' => 'admin@gmail.com',
            'allow_public_claims' => '1',
            'auto_approve_items' => '1',
            'max_photo_size_mb' => '5',
            'maintenance_mode' => '0',
        ];
        foreach ($settings as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        // Sample system flags on existing items.
        $iphone = Item::where('name', 'Silver iPhone 13')->where('type', 'found')->first();
        $keys = Item::where('name', 'Set of Keys with Red Keychain')->where('type', 'found')->first();
        $nalgene = Item::where('name', 'Nalgene Water Bottle')->where('type', 'lost')->first();
        $tote = Item::where('name', 'Green Tote Bag')->where('type', 'lost')->first();

        if ($iphone) {
            Flag::firstOrCreate(
                ['item_id' => $iphone->id, 'reason' => 'Duplicate report'],
                [
                    'details' => 'Possible duplicate of another found iPhone report submitted last week. Please verify serial references before release.',
                    'status' => 'open',
                ],
            );
        }

        if ($keys) {
            Flag::firstOrCreate(
                ['item_id' => $keys->id, 'reason' => 'Ownership dispute'],
                [
                    'details' => 'Two students submitted conflicting ownership claims for these keys. Both cite the red carabiner and flashlight.',
                    'status' => 'open',
                ],
            );
        }

        if ($nalgene) {
            $flag = Flag::firstOrCreate(
                ['item_id' => $nalgene->id, 'reason' => 'Suspicious claim activity'],
                [
                    'details' => 'Reporter could not be reached for verification after two attempts.',
                    'status' => 'dismissed',
                    'admin_note' => 'Reached the reporter by phone on the third attempt. Report verified as legitimate.',
                    'resolved_by' => $admin->id,
                    'resolved_at' => now(),
                ],
            );
        }

        if ($tote) {
            $flag = Flag::firstOrCreate(
                ['item_id' => $tote->id, 'reason' => 'Spam / inappropriate content'],
                [
                    'details' => 'Description previously contained promotional links. Edited by the reporter after warning.',
                    'status' => 'resolved',
                    'admin_note' => 'Content cleaned up and reporter reminded of the community guidelines.',
                    'resolved_by' => $admin->id,
                    'resolved_at' => now(),
                ],
            );
        }
    }
}
