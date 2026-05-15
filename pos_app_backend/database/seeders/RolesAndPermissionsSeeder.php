<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ─────────────────────────────────────────────
        // 1. Define all permissions grouped by module
        // ─────────────────────────────────────────────
        $permissions = [

            // User Management
            'create_user',
            'view_user',
            'update_user',
            'delete_user',
            'assign_role',

            // Role & Permission
            'create_role',
            'edit_role',
            'delete_role',
            'manage_permissions',

            // Products
            'create_product',
            'view_product',
            'update_product',
            'delete_product',
            'import_products',

            // Categories
            'create_category',
            'view_category',
            'update_category',
            'delete_category',

            // Sales
            'create_sale',
            'view_all_sales',
            'view_own_sales',
            'edit_sale',
            'cancel_sale',
            'refund_sale',

            // Payments
            'process_payment',
            'view_payments',

            // Inventory
            'view_stock',
            'adjust_stock',
            'stock_transfer',

            // Customers
            'create_customer',
            'update_customer',
            'view_customer',

            // Tables
            'create_table',
            'view_table',
            'update_table',
            'delete_table',
            'manage_table_status',

            // Kitchen / Order Status
            'view_orders',
            'update_order_status',

            // Reports
            'view_sales_report',
            'view_profit_report',
            'export_reports',

            // System Settings
            'system_settings',
            'backup_database',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // ─────────────────────────────────────────────
        // 2. Create roles and assign permissions
        // ─────────────────────────────────────────────

        // ── Admin ── gets every permission
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $admin->syncPermissions(Permission::all());

        // ── Manager ──
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'api']);
        $manager->syncPermissions([
            // Products & Categories
            'create_product', 'view_product', 'update_product',
            'create_category', 'update_category', 'view_category',

            // Sales & Orders
            'create_sale', 'view_all_sales', 'cancel_sale', 'view_orders', 'update_order_status',

            // Tables
            'view_table', 'update_table', 'manage_table_status',

            // Inventory
            'view_stock', 'adjust_stock',

            // Customers
            'create_customer', 'update_customer', 'view_customer',

            // Reports
            'view_sales_report', 'view_profit_report',
        ]);

        // ── Cashier ──
        $cashier = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'api']);
        $cashier->syncPermissions([
            'create_sale', 'view_own_sales', 'process_payment',
            'view_product', 'view_category', 'view_table', 'manage_table_status',
            'create_customer',
        ]);

        // ── Kitchen ──
        $kitchen = Role::firstOrCreate(['name' => 'kitchen', 'guard_name' => 'api']);
        $kitchen->syncPermissions([
            'view_orders', 'update_order_status', 'view_product',
        ]);

        // ── Waiter ──
        $waiter = Role::firstOrCreate(['name' => 'waiter', 'guard_name' => 'api']);
        $waiter->syncPermissions([
            'create_sale', 'view_own_sales', 'view_table', 'manage_table_status',
            'view_product', 'view_category',
        ]);

        $this->command->info('✅ Roles and permissions seeded successfully.');
        $this->command->table(
            ['Role', 'Permission Count'],
            [
                ['admin',   $admin->permissions()->count()],
                ['manager', $manager->permissions()->count()],
                ['cashier', $cashier->permissions()->count()],
                ['kitchen', $kitchen->permissions()->count()],
                ['waiter',  $waiter->permissions()->count()],
            ]
        );
    }
}
