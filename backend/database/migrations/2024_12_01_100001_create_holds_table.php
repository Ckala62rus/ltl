<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained('slots')->onDelete('cascade');
            $table->string('status')->default('held')->comment('Статус: held, confirmed, cancelled');
            $table->string('idempotency_key')->unique()->comment('Ключ идемпотентности');
            $table->timestamp('expires_at')->comment('Время истечения холда');
            $table->timestamps();
            
            $table->index(['slot_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('holds');
    }
};

