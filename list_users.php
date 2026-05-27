<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = \Illuminate\Support\Facades\DB::table('users')
    ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
    ->select('users.id', 'users.name', 'users.email', 'users.role_id', 'roles.name as role_name')
    ->orderBy('users.id')
    ->get();

echo "\n";
echo str_pad('ID', 5) . str_pad('Nom', 25) . str_pad('Email', 35) . str_pad('role_id', 10) . "Role\n";
echo str_repeat('-', 85) . "\n";

foreach ($users as $user) {
    echo str_pad($user->id, 5)
        . str_pad($user->name ?? '(null)', 25)
        . str_pad($user->email ?? '(null)', 35)
        . str_pad($user->role_id ?? '(null)', 10)
        . ($user->role_name ?? '(aucun role)') . "\n";
}

echo "\n";
