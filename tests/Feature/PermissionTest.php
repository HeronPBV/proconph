<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionTest extends TestCase
{
    /** @test */
    public function test_user_has_permission()
    {
        $user = User::factory()->create();
        $role = Role::findByName('admin');
        $permission = Permission::findByName('list.users');

        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->assertTrue($user->can('list.users'));
    }



}
