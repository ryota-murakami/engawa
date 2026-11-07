<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Chapter05SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_has_the_diary_visibility_and_friendship_columns(): void
    {
        $this->withoutVite();
        // Act
        $diaryColumns = Schema::getColumnListing('diaries');
        $friendshipColumns = Schema::getColumnListing('friendships');

        // Assert
        $this->assertContains('visibility', $diaryColumns);
        $this->assertContains('requested_by', $friendshipColumns);
        $this->assertTrue(Schema::hasTable('notifications'));

    }
}
