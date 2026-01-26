<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Developer module tables.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Servers for SSH connections
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name', 128);
            $table->string('ip', 45);
            $table->unsignedSmallInteger('port')->default(22);
            $table->string('user', 64)->default('root');
            $table->text('private_key')->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('workspace_id');
            $table->index(['workspace_id', 'status']);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
