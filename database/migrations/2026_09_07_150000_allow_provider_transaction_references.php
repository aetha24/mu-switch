<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Providers generate different kinds of unique reference. PostgreSQL's
     * original UUID-only column caused a Lipila reference to fail while saving
     * the local transaction, before the outgoing Lipila request was sent.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE "transactions" ALTER COLUMN "transaction_id" TYPE varchar(255) USING "transaction_id"::text');
        }
    }

    /**
     * Reversing would be unsafe once non-UUID provider references exist.
     */
    public function down(): void
    {
        // Intentionally irreversible.
    }
};
