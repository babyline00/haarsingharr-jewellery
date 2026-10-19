<?php

return [

    'models' => [
        'permission' => Spatie\Permission\Models\Permission::class,
        'role'       => Spatie\Permission\Models\Role::class,
    ],

    'table_names' => [
        'roles'                 => 'roles',
        'permissions'           => 'permissions',
        'role_has_permissions'  => 'permission_role',
        'model_has_permissions' => 'user_permissions',
        'model_has_roles'       => 'role_user',
    ],

    'column_names' => [
        'role_pivot_key'      => null,
        'permission_pivot_key' => null,
        'model_morph_key'     => 'user_id',
        'team_foreign_key'    => 'team_id',
    ],

    'register_permission_check_methods' => [
        'isSuperAdmin', 'hasRole', 'checkPermissionTo',
    ],

    'teams' => false,

    'display_permission_in_exception' => false,
    'display_role_in_exception'      => false,

    'enable_wildcard_permission' => false,

    'cache' => [
        'enabled' => env('SPATIE_PERMISSION_CACHE', true),
        'store'   => env('SPATIE_PERMISSION_CACHE_STORE', env('CACHE_STORE', 'database')),
        'expiration_time' => DateInterval::createFromDateString('24 hours'),
        'key' => 'spatie.permission.cache',
        'model_key' => 'permission',
    ],
];
