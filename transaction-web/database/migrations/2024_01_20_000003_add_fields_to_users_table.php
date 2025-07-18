<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('name');
            $table->string('last_name')->after('first_name');
            $table->string('phone')->nullable()->after('email');
            $table->decimal('balance', 15, 2)->default(0.00)->after('phone');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('balance');
            $table->string('user_id')->unique()->after('id');
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->dropColumn(['first_name', 'last_name', 'phone', 'balance', 'status', 'user_id']);
        });
    }
};
