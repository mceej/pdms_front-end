<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geographies', function (Blueprint $table) {
            $table->id();
            $table->string('psgc_code')->unique();
            $table->string('name');
            $table->enum('level', ['region', 'province', 'municipality', 'barangay']);
            $table->foreignId('parent_id')->nullable()->constrained('geographies')->nullOnDelete();
            $table->timestamps();
            $table->index(['level', 'parent_id']);
        });

        Schema::create('served_lists', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('program');
            $table->string('disaster_type');
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imported_at');
            $table->string('status')->default('committed');
            $table->unsignedInteger('record_count')->default(0);
            $table->timestamps();
        });

        Schema::create('payout_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('served_list_id')->constrained()->cascadeOnDelete();
            $table->string('program');
            $table->string('disaster_type');
            $table->foreignId('region_id')->constrained('geographies');
            $table->foreignId('province_id')->constrained('geographies');
            $table->foreignId('municipality_id')->constrained('geographies');
            $table->foreignId('barangay_id')->constrained('geographies');
            $table->string('payout_site')->nullable();
            $table->string('beneficiary_reference')->nullable();
            $table->decimal('target_amount', 12, 2)->default(0);
            $table->decimal('disbursed_amount', 12, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->date('served_date')->nullable();
            $table->timestamps();
            $table->index(['program', 'disaster_type']);
            $table->index(['province_id', 'municipality_id', 'barangay_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_records');
        Schema::dropIfExists('served_lists');
        Schema::dropIfExists('geographies');
    }
};
