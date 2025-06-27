<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Set is_admin = 1 for the admin user
        DB::table('users')
            ->where('email', 'admin@falai.com')
            ->update(['is_admin' => 1]);
    }

    public function down(): void
    {
        // Optionally revert (set is_admin = 0)
        DB::table('users')
            ->where('email', 'admin@falai.com')
            ->update(['is_admin' => 0]);
    }
}; 