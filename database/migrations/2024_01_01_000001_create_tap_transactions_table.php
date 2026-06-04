<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tap_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('tap_id')->nullable()->index();
            $table->string('tap_object')->nullable();
            $table->string('order_reference_id')->nullable();
            $table->decimal('amount', 20, 3)->default(0);
            $table->string('currency', 3)->default('SAR');
            $table->string('status')->nullable()->index();
            $table->string('payment_method')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('source_id')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->nullableMorphs('billable');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tap_transactions');
    }
};
