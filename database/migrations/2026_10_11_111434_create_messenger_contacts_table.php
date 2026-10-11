<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a salon account (customer or staff) to a Messenger conversation with the salon's Facebook Page.
 *
 *  - psid: the Page-scoped ID Meta gives us when the person messages the Page.
 *  - link_code: a short one-time code the person sends to the Page to prove which account they are.
 *  - last_inbound_at: when they last messaged the Page. Meta only lets us message them for 24 hours after that.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('user_type', 20);
            $table->unsignedInteger('userID');
            $table->string('psid', 64)->nullable()->unique();
            $table->string('link_code', 20)->nullable()->index();
            $table->timestamp('link_code_expires_at')->nullable();
            $table->timestamp('linked_at')->nullable();
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamps();

            $table->unique(['user_type', 'userID']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_contacts');
    }
};
