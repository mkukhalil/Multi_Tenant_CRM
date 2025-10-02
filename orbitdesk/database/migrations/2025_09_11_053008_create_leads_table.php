<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_to')->nullable();

            $table->string('title')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('source')->nullable();

            $table->enum('status', ['new', 'contacted', 'qualified', 'lost', 'converted'])
                  ->default('new');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium')->after('status');

            $table->dateTime('next_follow_up')->nullable();
            $table->dateTime('last_contacted')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes(); // 👈 this adds the deleted_at column

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
