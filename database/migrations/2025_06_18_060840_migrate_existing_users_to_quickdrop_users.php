<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate non-admin users from users table to quickdrop_users table
        $users = DB::table('users')
            ->where('is_admin', false)
            ->orWhereNull('is_admin')
            ->get();

        foreach ($users as $user) {
            $quickDropUserId = DB::table('quickdrop_users')->insertGetId([
                'email' => $user->email,
                'name' => $user->name,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'is_active' => true,
                'storage_used' => 0,
                'storage_limit' => 5368709120, // 5GB default
            ]);

            // Update upload_requests to use the new quickdrop_user_id
            DB::table('upload_requests')
                ->where('requesting_user_id', $user->id)
                ->update(['quickdrop_user_id' => $quickDropUserId]);

            // Update upload_objects to use the new quickdrop_owner_id
            DB::table('upload_objects')
                ->where('owner_id', $user->id)
                ->update(['quickdrop_owner_id' => $quickDropUserId]);
        }

        // Calculate storage used for each QuickDrop user
        DB::statement('
            UPDATE quickdrop_users qu
            SET storage_used = (
                SELECT COALESCE(SUM(uo.file_size), 0)
                FROM upload_objects uo
                WHERE uo.quickdrop_owner_id = qu.id
                AND uo.deleted_at IS NULL
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as it would require recreating users with passwords
        // which we don't have in the quickdrop_users table
        throw new \Exception('This migration cannot be reversed.');
    }
};
