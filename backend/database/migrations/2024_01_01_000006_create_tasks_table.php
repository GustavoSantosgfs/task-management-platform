<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['backlog', 'todo', 'in_progress', 'review', 'done', 'blocked'])->default('backlog');
            $table->dateTime('due_date')->nullable();
            $table->string('due_date_timezone')->default('UTC');
            $table->integer('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Required indexes from documentation
            $table->index('project_id');
            $table->index('assignee_id');
            $table->index('status');
            $table->index('priority');
            $table->index(['project_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index(['assignee_id', 'status']);
            $table->index(['project_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
