<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1);
            $table->unique(['tenant_id', 'id']);
            $table->index(['tenant_id', 'email']);
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->ulid('lead_id');
            $table->string('name', 150);
            $table->string('email', 254)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('source');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'lead_id']);
            $table->foreign(['tenant_id', 'lead_id'])->references(['tenant_id', 'id'])->on('leads')->restrictOnDelete();
            $table->index(['tenant_id', 'phone']);
            $table->index(['tenant_id', 'email']);
            $table->index(['tenant_id', 'created_at', 'id']);
            $table->index('created_by');
        });
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->restrictOnDelete();
            $table->ulid('lead_id');
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('kind', 40);
            $table->text('body')->nullable();
            $table->uuid('request_key')->nullable();
            $table->json('metadata');
            $table->timestamp('created_at');
            $table->foreign(['tenant_id', 'lead_id'])->references(['tenant_id', 'id'])->on('leads')->restrictOnDelete();
            $table->unique(['tenant_id', 'request_key']);
            $table->index(['tenant_id', 'lead_id', 'created_at', 'id'], 'lead_activity_timeline_index');
            $table->index('actor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('customers');
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'email']);
            $table->dropUnique(['tenant_id', 'id']);
            $table->dropColumn('version');
        });
    }
};
