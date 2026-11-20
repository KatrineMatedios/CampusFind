<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use App\Notifications\CampusNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Keep seeding idempotent so SEED_DB=true can stay enabled on Render.
        if (User::where('student_id', '2024-00123')->exists()) {
            return;
        }

        $categories = collect([
            ['name' => 'Electronics', 'slug' => 'electronics'],
            ['name' => 'Keys', 'slug' => 'keys'],
            ['name' => 'Wallet', 'slug' => 'wallet'],
            ['name' => 'ID', 'slug' => 'id'],
            ['name' => 'Bottle', 'slug' => 'bottle'],
            ['name' => 'Bags', 'slug' => 'bags'],
            ['name' => 'Others', 'slug' => 'others'],
        ])->mapWithKeys(fn (array $category) => [
            $category['slug'] => Category::create($category),
        ]);

        $maria = User::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'student_id' => '2024-00123',
            'email' => 'maria.santos@university.edu',
            'college_program' => 'BS Computer Science',
            'contact_number' => '09171234567',
            'role' => 'student',
            'password' => 'password',
        ]);

        $john = User::create([
            'first_name' => 'John',
            'last_name' => 'Cruz',
            'student_id' => '2024-00456',
            'email' => 'john.cruz@university.edu',
            'college_program' => 'BS Business Administration',
            'contact_number' => '09181234567',
            'role' => 'student',
            'password' => 'password',
        ]);

        $alyssa = User::create([
            'first_name' => 'Alyssa',
            'last_name' => 'Reyes',
            'student_id' => '2023-00789',
            'email' => 'alyssa.reyes@university.edu',
            'college_program' => 'BS Nursing',
            'contact_number' => '09191234567',
            'role' => 'student',
            'password' => 'password',
        ]);

        $staff = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'student_id' => 'STAFF-0001',
            'email' => 'admin@gmail.com',
            'college_program' => 'Student Affairs Office',
            'contact_number' => '09170000000',
            'role' => 'admin',
            'password' => 'admin123',
        ]);

        $items = [
            [
                'user_id' => $maria->id,
                'type' => 'lost',
                'name' => 'Black JBL Wireless Headphones',
                'category_id' => $categories['electronics']->id,
                'date_reported' => Carbon::now()->subDays(2),
                'location' => 'Library, 2nd Floor',
                'description' => 'Black over-ear JBL headphones in a soft black pouch. Has a small white sticker on the left ear cup.',
                'status' => 'open',
            ],
            [
                'user_id' => $maria->id,
                'type' => 'lost',
                'name' => 'Dorm Key with Blue Tag',
                'category_id' => $categories['keys']->id,
                'date_reported' => Carbon::now()->subDays(5),
                'location' => 'Residence Hall B Lobby',
                'description' => 'Single silver key with a blue plastic tag and a small smiley keychain.',
                'status' => 'open',
            ],
            [
                'user_id' => $maria->id,
                'type' => 'lost',
                'name' => 'Brown Leather Wallet',
                'category_id' => $categories['wallet']->id,
                'date_reported' => Carbon::now()->subDays(12),
                'location' => 'Student Center Canteen',
                'description' => 'Brown fold-over wallet with a student ID and a few receipts inside.',
                'status' => 'resolved',
            ],
            [
                'user_id' => $maria->id,
                'type' => 'lost',
                'name' => 'Nalgene Water Bottle',
                'category_id' => $categories['bottle']->id,
                'date_reported' => Carbon::now()->subDay(),
                'location' => 'Science Hall, Room 301',
                'description' => 'Clear blue 32oz Nalgene bottle with a university sticker.',
                'status' => 'open',
            ],
            [
                'user_id' => $john->id,
                'type' => 'found',
                'name' => 'Student ID Lace with Blue Strap',
                'category_id' => $categories['id']->id,
                'date_reported' => Carbon::now()->subDay(),
                'location' => 'Gymnasium Bleachers',
                'description' => 'Found a student ID lace with a blue strap near the bleachers after the basketball game.',
                'status' => 'open',
            ],
            [
                'user_id' => $john->id,
                'type' => 'found',
                'name' => 'Silver iPhone 13',
                'category_id' => $categories['electronics']->id,
                'date_reported' => Carbon::now()->subDays(3),
                'location' => 'Parking Lot near Gate 2',
                'description' => 'Silver iPhone 13 with a cracked screen protector and a plain black case.',
                'status' => 'open',
            ],
            [
                'user_id' => $john->id,
                'type' => 'found',
                'name' => 'Set of Keys with Red Keychain',
                'category_id' => $categories['keys']->id,
                'date_reported' => Carbon::now()->subDays(2),
                'location' => 'Engineering Building, Room 204',
                'description' => 'Three keys on a red carabiner with a small flashlight attached.',
                'status' => 'claim_pending',
            ],
            [
                'user_id' => $john->id,
                'type' => 'found',
                'name' => 'Brown Leather Wallet',
                'category_id' => $categories['wallet']->id,
                'date_reported' => Carbon::now()->subDays(10),
                'location' => 'Student Center Canteen',
                'description' => 'Brown fold-over wallet found on a table. Kept safely at the Lost & Found Desk.',
                'status' => 'resolved',
            ],
            [
                'user_id' => $alyssa->id,
                'type' => 'found',
                'name' => 'Pink Hydro Flask',
                'category_id' => $categories['bottle']->id,
                'date_reported' => Carbon::now()->subDays(4),
                'location' => 'Science Hall Canteen',
                'description' => 'Pink 24oz Hydro Flask with a dent on the bottom and a cat sticker.',
                'status' => 'open',
            ],
            [
                'user_id' => $alyssa->id,
                'type' => 'found',
                'name' => 'Scientific Calculator',
                'category_id' => $categories['electronics']->id,
                'date_reported' => Carbon::now()->subDays(6),
                'location' => 'Library Reading Area',
                'description' => 'Casio scientific calculator with the name "J. Cruz" written in marker on the back.',
                'status' => 'open',
            ],
            [
                'user_id' => $alyssa->id,
                'type' => 'lost',
                'name' => 'Green Tote Bag',
                'category_id' => $categories['bags']->id,
                'date_reported' => Carbon::now()->subDays(2),
                'location' => 'Administration Building',
                'description' => 'Forest green canvas tote with a notebook and a pencil case inside.',
                'status' => 'open',
            ],
        ];

        $createdItems = collect($items)->mapWithKeys(fn (array $item) => [
            $item['name'].'|'.$item['type'].'|'.$item['user_id'] => Item::create($item),
        ]);

        $wallet = Item::where('type', 'found')->where('name', 'Brown Leather Wallet')->first();
        $keys = Item::where('type', 'found')->where('name', 'Set of Keys with Red Keychain')->first();

        $collectedClaim = Claim::create([
            'item_id' => $wallet->id,
            'user_id' => $maria->id,
            'identifiers' => 'Brown wallet with my student ID and two movie ticket stubs inside. My initials M.S. are written on the inner flap.',
            'pickup_location' => 'Campus Lost & Found Desk',
            'pickup_time' => Carbon::now()->subDays(9)->setHour(14),
            'status' => 'collected',
        ]);

        Claim::create([
            'item_id' => $keys->id,
            'user_id' => $maria->id,
            'identifiers' => 'Three keys on a red carabiner. The smallest key opens my dorm mailbox and there is a tiny blue flashlight attached.',
            'pickup_location' => 'Campus Lost & Found Desk',
            'pickup_time' => Carbon::now()->addDay()->setHour(10),
            'status' => 'pending',
        ]);

        $activity = [
            [$maria->id, $createdItems['Black JBL Wireless Headphones|lost|'.$maria->id]->id, 'reported', 'Reported a lost item: Black JBL Wireless Headphones', Carbon::now()->subDays(2)],
            [$maria->id, $createdItems['Dorm Key with Blue Tag|lost|'.$maria->id]->id, 'reported', 'Reported a lost item: Dorm Key with Blue Tag', Carbon::now()->subDays(5)],
            [$maria->id, $createdItems['Brown Leather Wallet|lost|'.$maria->id]->id, 'reported', 'Reported a lost item: Brown Leather Wallet', Carbon::now()->subDays(12)],
            [$maria->id, $wallet->id, 'claimed', 'Submitted an ownership claim for Brown Leather Wallet', Carbon::now()->subDays(10)],
            [$maria->id, $wallet->id, 'resolved', 'Claim for Brown Leather Wallet was collected', Carbon::now()->subDays(9)],
            [$john->id, $keys->id, 'reported', 'Reported a found item: Set of Keys with Red Keychain', Carbon::now()->subDays(2)],
        ];

        foreach ($activity as [$userId, $itemId, $action, $description, $createdAt]) {
            $log = new ActivityLog([
                'user_id' => $userId,
                'item_id' => $itemId,
                'action' => $action,
                'description' => $description,
            ]);
            $log->created_at = $createdAt;
            $log->updated_at = $createdAt;
            $log->save();
        }

        $maria->notify(new CampusNotification(
            'Claim collected',
            'Your claim for Brown Leather Wallet was marked as collected. Thank you!',
            '/items/'.$wallet->id,
            'claim',
        ));

        $maria->notify(new CampusNotification(
            'Welcome to CampusFind',
            'Report lost items and browse found items to see if yours has been turned in.',
            '/browse',
            'general',
        ));

        $maria->notifications()->oldest()->first()?->markAsRead();

        $john->notify(new CampusNotification(
            'New ownership claim',
            'Maria Santos submitted a claim for Set of Keys with Red Keychain.',
            '/items/'.$keys->id,
            'claim',
        ));

        // Keep the staff account referenced by seeders for the desk workflow.
        $staff->notify(new CampusNotification(
            'Claim awaiting review',
            'A new ownership claim for Set of Keys with Red Keychain needs verification.',
            '/items/'.$keys->id,
            'claim',
        ));

        $this->call(AdminSeeder::class);
    }
}
