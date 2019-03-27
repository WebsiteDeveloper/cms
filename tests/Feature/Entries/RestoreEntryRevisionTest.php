<?php

namespace Tests\Feature\Entries;

use Mockery;
use Tests\TestCase;
use Tests\FakesRoles;
use Statamic\API\User;
use Statamic\API\Entry;
use Statamic\API\Folder;
use Statamic\Fields\Fields;
use Illuminate\Support\Carbon;
use Statamic\Fields\Blueprint;
use Statamic\Revisions\Revision;
use Statamic\Revisions\WorkingCopy;
use Facades\Tests\Factories\EntryFactory;
use Tests\PreventSavingStacheItemsToDisk;
use Facades\Statamic\Fields\BlueprintRepository;

class RestoreEntryRevisionTest extends TestCase
{
    use FakesRoles;
    use PreventSavingStacheItemsToDisk;

    public function setUp(): void
    {
        parent::setUp();
        $this->dir = __DIR__.'/tmp';
        config(['statamic.revisions.path' => $this->dir]);
    }

    public function tearDown(): void
    {
        Folder::delete($this->dir);
        parent::tearDown();
    }

    /** @test */
    function it_restores_an_entry_to_another_revision()
    {
        $this->withoutExceptionHandling();
        $this->setTestBlueprint('test', ['foo' => ['type' => 'text']]);
        $this->setTestRoles(['test' => ['access cp', 'edit blog entries']]);
        $user = User::make()->id('user-1')->assignRole('test')->save();

        $revision = tap((new Revision)
            ->key('collections/blog/en/123')
            ->date(Carbon::createFromTimestamp('1553546421'))
            ->attributes([
                'published' => false,
                'slug' => 'existing-slug',
                'data' => ['foo' => 'existing foo']
            ]))->save();

        WorkingCopy::fromRevision($revision)->save();

        $entry = EntryFactory::id('123')
            ->slug('test')
            ->collection('blog')
            ->published(true)
            ->data([
                'blueprint' => 'test',
                'title' => 'Title',
                'foo' => 'bar',
            ])->create();

        $this->assertTrue($entry->published());

        $this
            ->actingAs($user)
            ->restore($entry, ['revision' => '1553546421'])
            ->assertOk()
            ->assertSessionHas('success');

        $entry = Entry::find($entry->id());
        $this->assertEquals('existing-slug', $entry->slug());
        $this->assertEquals(['foo' => 'existing foo'], $entry->data());
        $this->assertFalse($entry->published());
        $this->assertFalse($entry->hasWorkingCopy());
    }

    private function restore($entry, $payload)
    {
        return $this->post($entry->restoreRevisionUrl(), $payload);
    }

    private function setTestBlueprint($handle, $fields)
    {
        $fields = collect($fields)->map(function ($field, $handle) {
            return compact('handle', 'field');
        })->all();

        $blueprint = Mockery::mock(Blueprint::class);
        $blueprint->shouldReceive('fields')->andReturn(new Fields($fields));

        $blueprint->shouldReceive('ensureField')->andReturnSelf();
        $blueprint->shouldReceive('ensureFieldPrepended')->andReturnSelf();

        BlueprintRepository::shouldReceive('find')->with('test')->andReturn($blueprint);
    }
}