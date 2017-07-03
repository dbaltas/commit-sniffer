<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\AuthorMap;

class RepoMapAuthorTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @group integration
     */
    public function testMapAuthorCreatesAuthorWhenDoesNotExist()
    {
        $expectedAuthor = 'dbaltas@travelplanet24.com';
        $expectedMap = 'dbaltas@travelplanet24.com';
        Artisan::call('repo:map-author', [
            'author' => $expectedAuthor,
            'map' => $expectedMap,
        ]);

        $author = AuthorMap::where('author', $expectedAuthor)
            ->where('map', $expectedMap);
        $this->assertNotNull($author);
    }

    /**
     * @group integration
     */
    public function testMapAuthorUpdatesAuthorWhenAlreadyExists()
    {
        Artisan::call('repo:map-author', [
            'author' => 'foo@travelplanet24.com',
            'map' => 'foo@travelplanet24.com',
        ]);

        $expectedAuthor = 'dbaltas@travelplanet24.com';
        $expectedMap = 'dbaltas@travelplanet24.com';
        Artisan::call('repo:map-author', [
            'author' => $expectedAuthor,
            'map' => $expectedMap,
        ]);

        $author = AuthorMap::where('author', $expectedAuthor)
            ->where('map', $expectedMap);
        $this->assertNotNull($author);
    }
}
