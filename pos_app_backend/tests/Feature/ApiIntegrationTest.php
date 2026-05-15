<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Artisan::call('migrate:fresh');
    Role::create(['name' => 'admin', 'guard_name' => 'api']);
    Role::create(['name' => 'cashier', 'guard_name' => 'api']);
    Permission::create(['name' => 'view_product', 'guard_name' => 'api']);
    Permission::create(['name' => 'view_category', 'guard_name' => 'api']);
});

test('auth routes work', function () {
    $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(201);

    $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ])->assertStatus(200);
});

test('resource routes require authentication', function () {
    $this->getJson('/api/products')->assertStatus(401);
    $this->getJson('/api/categories')->assertStatus(401);
    $this->getJson('/api/tables')->assertStatus(401);
});

test('authenticated user can access resources', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'admin', 'guard_name' => 'api']);
    $role->givePermissionTo(['view_product', 'view_category']);
    $user->assignRole('admin');
    
    $this->actingAs($user, 'api');

    $this->getJson('/api/products')->assertStatus(200);
    $this->getJson('/api/categories')->assertStatus(200);
    $this->getJson('/api/tables')->assertStatus(200);
});
