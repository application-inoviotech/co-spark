<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['membership_plan', 'billing_frequency', 'refund_review_note'] as $column) {
            if (! Schema::hasColumn('payments', $column)) {
                Schema::table('payments', fn (Blueprint $table) => $table->string($column)->nullable());
            }
        }
        if (! Schema::hasColumn('payments', 'refund_reconciled_at')) {
            Schema::table('payments', fn (Blueprint $table) => $table->timestamp('refund_reconciled_at')->nullable());
        }
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn(['membership_plan', 'billing_frequency', 'refund_review_note', 'refund_reconciled_at']));
    }
};
