<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies', 'id', 'jel_company_id_fk'); // <- explicit name

            $table->foreignId('journal_entry_id')
                ->constrained(null, 'id', 'jel_journal_entry_id_fk')
                ->cascadeOnDelete();

            $table->foreignId('account_id')
                ->constrained('chart_of_accounts', 'id', 'jel_account_id_fk');

            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->string('description')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'account_id'], 'jel_company_account_idx');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
