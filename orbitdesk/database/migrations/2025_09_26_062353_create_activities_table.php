<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivitiesTable extends Migration
{
    public function up()
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('subject'); // creates subject_id and subject_type
            $table->string('type'); // lead, opportunity, etc.
            $table->string('action'); // created, updated, status_changed, etc.
            $table->text('description');
            $table->json('changes')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['tenant_id', 'subject_type', 'subject_id']);
            $table->index(['tenant_id', 'type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('activities');
    }
}