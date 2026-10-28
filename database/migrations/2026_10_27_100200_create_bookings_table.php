<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('staff_member_id')->constrained()->restrictOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('status', 20)->default('confirmed');
            $table->unsignedInteger('price_cents');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->uuid('manage_token')->unique();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'starts_at']);
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE bookings ADD CONSTRAINT bookings_valid_range CHECK (ends_at > starts_at)');

        // Garantía final contra reservas dobles: Postgres rechaza dos reservas activas
        // del mismo profesional cuyos rangos se crucen, aunque lleguen en paralelo.
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement(<<<'SQL'
            ALTER TABLE bookings ADD CONSTRAINT bookings_staff_no_overlap
            EXCLUDE USING gist (staff_member_id WITH =, tstzrange(starts_at, ends_at, '[)') WITH &&)
            WHERE (status IN ('pending', 'confirmed'))
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
