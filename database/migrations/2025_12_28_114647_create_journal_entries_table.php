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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->index();

            $table->date('entry_date');
            $table->string('reference_no')->nullable(); // optional numbering later
            $table->string('memo')->nullable();

            // links back to source docs later (sales_invoice, expense, etc.)
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->string('status', 20)->default('DRAFT'); // DRAFT / POSTED (optional)
            $table->timestamps();

            $table->index(['company_id', 'entry_date']);
            $table->index(['company_id', 'source_type', 'source_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
