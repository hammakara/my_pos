<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\RolesAndPermissionsSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolesAndPermissionsSeeder::class);

        // Create a default admin user
        $admin = User::factory()->create([
            'name'  => 'Admin User',
            'email' => 'admin@example.com',
            'password'=>bcrypt('12341234')
        ]);
        $admin->assignRole('admin');

        // Create sample products for admin
        \App\Models\Product::create([
            'name' => 'Espresso',
            'description' => 'Rich and bold double shot',
            'user_id' => $admin->id
        ]);
        \App\Models\Product::create([
            'name' => 'Caffè Latte',
            'description' => 'Steamed milk with a shot of espresso',
            'user_id' => $admin->id
        ]);

        // Create a default manager user
        $manager = User::factory()->create([
            'name'  => 'Manager User',
            'email' => 'manager@example.com',
            'password'=>bcrypt('12341234')
        ]);
        $manager->assignRole('manager');

        \App\Models\Product::create([
            'name' => 'Croissant',
            'description' => 'Buttery and flaky french pastry',
            'user_id' => $manager->id
        ]);

        // Create a default cashier user
        $cashier = User::factory()->create([
            'name'  => 'Cashier User',
            'email' => 'cashier@example.com',
            'password'=>bcrypt('12341234')
        ]);
        $cashier->assignRole('cashier');
    }
}
