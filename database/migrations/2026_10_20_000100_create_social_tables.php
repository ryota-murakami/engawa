<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('bio')->nullable();
            $table->boolean('email_social_notifications')->default(true);
        });

        Schema::create('diaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('body');
            $table->string('visibility', 16)->default('friends');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['visibility', 'created_at']);
        });

        Schema::create('diary_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['diary_id', 'created_at']);
        });

        Schema::create('friendships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_low_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_high_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 16)->default('pending');
            $table->timestamps();
            $table->unique(['user_low_id', 'user_high_id']);
            $table->index(['user_low_id', 'status']);
            $table->index(['user_high_id', 'status']);
        });

        Schema::create('blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blocker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['blocker_id', 'blocked_id']);
            $table->index(['blocked_id', 'blocker_id']);
        });

        Schema::create('communities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 80);
            $table->text('description');
            $table->timestamps();
        });

        Schema::create('memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16)->default('member');
            $table->timestamps();
            $table->unique(['community_id', 'user_id']);
            $table->index(['user_id', 'community_id']);
        });

        Schema::create('topics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('body');
            $table->timestamps();
            $table->index(['community_id', 'created_at']);
        });

        Schema::create('topic_replies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['topic_id', 'created_at']);
        });

        Schema::create('footprints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('visitor_id')->constrained('users')->cascadeOnDelete();
            $table->date('visited_on');
            $table->timestamp('last_visited_at');
            $table->unique(['owner_id', 'visitor_id', 'visited_on']);
            $table->index(['owner_id', 'last_visited_at']);
        });

        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diary_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('disk', 32);
            $table->string('path')->unique();
            $table->string('mime', 80);
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->index(['user_id', 'kind']);
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // This course intentionally uses PostgreSQL in development and tests.
        DB::statement("ALTER TABLE diaries ADD CONSTRAINT diaries_visibility_check CHECK (visibility IN ('members', 'friends', 'private'))");
        DB::statement('ALTER TABLE friendships ADD CONSTRAINT friendships_pair_check CHECK (user_low_id < user_high_id)');
        DB::statement('ALTER TABLE friendships ADD CONSTRAINT friendships_requester_check CHECK (requested_by IN (user_low_id, user_high_id))');
        DB::statement("ALTER TABLE friendships ADD CONSTRAINT friendships_status_check CHECK (status IN ('pending', 'accepted'))");
        DB::statement('ALTER TABLE blocks ADD CONSTRAINT blocks_not_self_check CHECK (blocker_id <> blocked_id)');
        DB::statement("ALTER TABLE memberships ADD CONSTRAINT memberships_role_check CHECK (role IN ('owner', 'member'))");
        DB::statement('ALTER TABLE footprints ADD CONSTRAINT footprints_not_self_check CHECK (owner_id <> visitor_id)');
        DB::statement("ALTER TABLE media ADD CONSTRAINT media_parent_check CHECK ((kind = 'avatar' AND diary_id IS NULL) OR (kind = 'diary' AND diary_id IS NOT NULL))");
        DB::statement('ALTER TABLE media ADD CONSTRAINT media_size_check CHECK (size >= 0)');
    }

    public function down(): void
    {
        foreach (['notifications', 'media', 'footprints', 'topic_replies', 'topics', 'memberships', 'communities', 'blocks', 'friendships', 'diary_comments', 'diaries'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['bio', 'email_social_notifications']);
        });
    }
};
