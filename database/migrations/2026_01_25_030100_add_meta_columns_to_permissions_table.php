<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('label')->nullable()->after('name');
            $table->string('category')->nullable()->after('label');
            $table->string('description')->nullable()->after('category');
            $table->unsignedInteger('sort_order')->default(0)->after('description');
            $table->boolean('is_active')->default(true)->after('sort_order');
        });
    }

    public function down() : void {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn([
                'label',
                'category',
                'description',
                'sort_order',
                'is_active',
            ]);
        });
    }
};
