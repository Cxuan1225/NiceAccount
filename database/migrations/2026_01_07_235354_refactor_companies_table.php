<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::table('companies', function (Blueprint $table) {

            // identity
            $table->string('code', 20)->nullable()->after('id');
            $table->string('registration_no', 30)->nullable()->after('name');
            $table->string('email')->nullable()->after('registration_no');
            $table->string('phone', 30)->nullable()->after('email');

            // address
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('address_line3')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('state', 80)->nullable();
            $table->string('postcode', 20)->nullable();
            $table->string('country', 2)->default('MY');

            // accounting defaults
            $table->renameColumn('currency', 'base_currency');
            $table->unsignedTinyInteger('currency_precision')->default(2);
            $table->string('timezone', 64)->default('Asia/Kuala_Lumpur');
            $table->string('date_format', 20)->default('d/m/Y');
            $table->unsignedTinyInteger('fy_start_month')->default(1);

            // controls
            $table->date('lock_date')->nullable();
            $table->date('closing_lock_date')->nullable();
            $table->boolean('is_active')->default(true);

        });
    }

    public function down() : void {
        Schema::table('companies', function (Blueprint $table) {

            $table->dropColumn([
                'code',
                'registration_no',
                'email',
                'phone',
                'address_line1',
                'address_line2',
                'address_line3',
                'city',
                'state',
                'postcode',
                'country',
                'currency_precision',
                'timezone',
                'date_format',
                'fy_start_month',
                'lock_date',
                'closing_lock_date',
                'is_active',
            ]);

            $table->renameColumn('base_currency', 'currency');
        });
    }
};
