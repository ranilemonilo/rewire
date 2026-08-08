<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

test('member cannot access the blog list page', function () {
    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $this->actingAs($member)->get(route('content-management.blogs'))->assertForbidden();
});

test('guest is redirected away from the blog list page', function () {
    $this->get(route('content-management.blogs'))->assertRedirect(route('login'));
});

test('member cannot create a post even via direct component call', function () {
    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $this->actingAs($member);

    Livewire::test('pages::app.content-management.blogs')
        ->call('create')
        ->assertForbidden();
});

test('member cannot save a post even via direct component call', function () {
    Storage::fake('public');

    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $this->actingAs($member);

    Livewire::test('pages::app.content-management.blogs')
        ->set('title', 'Hacked Post')
        ->set('featuredImageUpload', UploadedFile::fake()->image('post.jpg'))
        ->call('save')
        ->assertForbidden();

    $this->assertDatabaseMissing('posts', ['title' => 'Hacked Post']);
});

test('member cannot delete a post even via direct component call', function () {
    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $post = Post::factory()->create();

    $this->actingAs($member);

    Livewire::test('pages::app.content-management.blogs')
        ->call('delete', $post->id)
        ->assertForbidden();

    $this->assertDatabaseHas('posts', ['id' => $post->id]);
});

test('admin can view the blog list', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $post = Post::factory()->create(['title' => 'Seeded Post']);

    $this->actingAs($admin)
        ->get(route('content-management.blogs'))
        ->assertOk()
        ->assertSee($post->title);
});

test('admin can create a post with a featured image', function () {
    Storage::fake('public');

    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.blogs')
        ->call('create')
        ->set('title', 'My New Post')
        ->set('excerpt', 'An excerpt.')
        ->set('body', 'The body.')
        ->set('featuredImageUpload', UploadedFile::fake()->image('post.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $post = Post::query()->where('title', 'My New Post')->firstOrFail();

    expect($post->slug)->toBe('my-new-post');
    expect($post->author_id)->toBe($admin->id);
    Storage::disk('public')->assertExists($post->featured_image);
});

test('creating a post without a featured image fails validation', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.blogs')
        ->call('create')
        ->set('title', 'No Image Post')
        ->set('body', 'Body.')
        ->call('save')
        ->assertHasErrors(['featuredImageUpload' => 'required']);
});

test('uploading an svg as featured image is rejected', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.blogs')
        ->call('create')
        ->set('title', 'Malicious Post')
        ->set('body', 'Body.')
        ->set('featuredImageUpload', UploadedFile::fake()->create('malicious.svg', 10, 'image/svg+xml'))
        ->call('save')
        ->assertHasErrors('featuredImageUpload');

    $this->assertDatabaseMissing('posts', ['title' => 'Malicious Post']);
});

test('admin can edit an existing post without touching its featured image', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $post = Post::factory()->create(['title' => 'Original Title', 'featured_image' => 'blog/existing.jpg']);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.blogs')
        ->call('edit', $post->id)
        ->set('title', 'Updated Title')
        ->call('save')
        ->assertHasNoErrors();

    expect($post->fresh()->title)->toBe('Updated Title');
    expect($post->fresh()->featured_image)->toBe('blog/existing.jpg');
});

test('admin can delete a post', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $post = Post::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.blogs')->call('delete', $post->id);

  $this->assertSoftDeleted('posts', ['id' => $post->id]);
    expect(Post::find($post->id))->toBeNull();
});

test('creating a post without a slug generates one from the title', function () {
    $post = Post::create([
        'title' => 'Hello World',
        'slug' => '',
        'body' => 'Body.',
        'is_published' => false,
    ]);

    expect($post->slug)->toBe('hello-world');
});

test('creating a post with a duplicate slug gets a unique suffix', function () {
    Post::factory()->create(['slug' => 'hello-world']);

    $post = Post::create([
        'title' => 'Hello World',
        'slug' => 'hello-world',
        'body' => 'Body.',
        'is_published' => false,
    ]);

    expect($post->slug)->toBe('hello-world-1');
});

test('updating a post\'s title does not change its existing slug', function () {
    $post = Post::factory()->create(['title' => 'Original Title', 'slug' => 'original-title']);

    $post->update(['title' => 'A Completely Different Title']);

    expect($post->fresh()->slug)->toBe('original-title');
});

test('editing a post logs activity', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $post = Post::factory()->create(['is_published' => false]);

    $this->actingAs($admin);

    $countBeforeEdit = Activity::count();

    Livewire::test('pages::app.content-management.blogs')
        ->call('edit', $post->id)
        ->set('isPublished', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Activity::count())->toBe($countBeforeEdit + 1);

    $activity = Activity::query()->latest('id')->first();

    expect($activity->event)->toBe('updated');
    expect($activity->description)->toContain($post->title);
    expect($activity->attribute_changes['attributes'])->toHaveKey('is_published');
});

test('editing only the slug is captured in the activity log', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $post = Post::factory()->create(['slug' => 'original-slug']);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.blogs')
        ->call('edit', $post->id)
        ->set('slug', 'new-slug')
        ->call('save')
        ->assertHasNoErrors();

    $activity = Activity::query()->latest('id')->first();

    expect($activity->attribute_changes['attributes'])->toHaveKey('slug');
    expect($activity->attribute_changes['attributes']['slug'])->toBe('new-slug');
});

test('the slug field follows the title while creating', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.blogs')
        ->call('create')
        ->set('title', 'First Draft Title')
        ->assertSet('slug', 'first-draft-title')
        ->set('title', 'Renamed Before Publishing')
        ->assertSet('slug', 'renamed-before-publishing');
});

test('editing a post does not change its slug when the title changes', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $post = Post::factory()->create(['title' => 'Original Title', 'slug' => 'original-title']);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.blogs')
        ->call('edit', $post->id)
        ->set('title', 'A Completely Different Title')
        ->assertSet('slug', 'original-title')
        ->call('save')
        ->assertHasNoErrors();

    expect($post->fresh()->slug)->toBe('original-title');
});

test('the slug can be edited manually while editing a post', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $post = Post::factory()->create(['slug' => 'original-slug']);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.blogs')
        ->call('edit', $post->id)
        ->set('slug', 'custom-slug')
        ->call('save')
        ->assertHasNoErrors();

    expect($post->fresh()->slug)->toBe('custom-slug');
});

test('saving a post with a slug already used by another post fails validation', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    Post::factory()->create(['slug' => 'taken-slug']);
    $post = Post::factory()->create(['slug' => 'my-slug']);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.blogs')
        ->call('edit', $post->id)
        ->set('slug', 'taken-slug')
        ->call('save')
        ->assertHasErrors(['slug' => 'unique']);
});

test('admin can set a published-at date on a post', function () {
    Storage::fake('public');

    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.blogs')
        ->call('create')
        ->set('title', 'Scheduled Post')
        ->set('body', 'Body.')
        ->set('featuredImageUpload', UploadedFile::fake()->image('post.jpg'))
        ->set('publishedAt', '2026-08-01T09:00')
        ->call('save')
        ->assertHasNoErrors();

    $post = Post::query()->where('title', 'Scheduled Post')->firstOrFail();

    expect($post->published_at?->format('Y-m-d H:i'))->toBe('2026-08-01 09:00');
});

test('publishing a post without a published-at date defaults it to now', function () {
    $post = Post::factory()->create(['is_published' => false, 'published_at' => null]);

    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);
    $this->travelTo(now()->setTime(10, 30));

    Livewire::test('pages::app.content-management.blogs')
        ->call('edit', $post->id)
        ->set('isPublished', true)
        ->set('publishedAt', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($post->fresh()->published_at?->format('Y-m-d H:i'))->toBe(now()->format('Y-m-d H:i'));
});

test('a post cannot end up published with a null published_at', function () {
    $post = Post::factory()->create(['is_published' => true, 'published_at' => now()->subDay()]);

    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.blogs')
        ->call('edit', $post->id)
        ->set('title', 'Still Published')
        ->call('save')
        ->assertHasNoErrors();

    expect($post->fresh()->published_at)->not->toBeNull();
});

test('search matches by slug as well as title', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    Post::factory()->create(['title' => 'Unrelated Title', 'slug' => 'find-me-by-slug']);
    Post::factory()->create(['title' => 'Another Post', 'slug' => 'another-post']);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.blogs')
        ->set('search', 'find-me-by-slug')
        ->assertSee('Unrelated Title')
        ->assertDontSee('Another Post');
});

test('clicking remove on a persisted featured image does not touch storage until save', function () {
    Storage::fake('public');
    Storage::disk('public')->put('blog/existing.jpg', 'fake-content');

    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $post = Post::factory()->create(['featured_image' => 'blog/existing.jpg']);

    $this->actingAs($admin);

    $component = Livewire::test('pages::app.content-management.blogs')
        ->call('edit', $post->id)
        ->call('clearFeaturedImage');

    Storage::disk('public')->assertExists('blog/existing.jpg');
    expect($post->fresh()->featured_image)->toBe('blog/existing.jpg');

    $component->call('save')->assertHasNoErrors();

    Storage::disk('public')->assertMissing('blog/existing.jpg');
    expect($post->fresh()->featured_image)->toBeNull();
});

test('clicking remove on a newly selected upload only cancels that selection', function () {
    Storage::fake('public');
    Storage::disk('public')->put('blog/existing.jpg', 'fake-content');

    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $post = Post::factory()->create(['featured_image' => 'blog/existing.jpg']);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.blogs')
        ->call('edit', $post->id)
        ->set('featuredImageUpload', UploadedFile::fake()->image('replacement.jpg'))
        ->call('clearFeaturedImage')
        ->assertSet('featuredImageUpload', null)
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('public')->assertExists('blog/existing.jpg');
    expect($post->fresh()->featured_image)->toBe('blog/existing.jpg');
});

test('the status badge shows draft, scheduled, and published correctly', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    Post::factory()->create(['title' => 'Unpublished Article', 'is_published' => false]);
    Post::factory()->create(['title' => 'Future Article', 'is_published' => true, 'published_at' => now()->addWeek()]);
    Post::factory()->create(['title' => 'Live Article', 'is_published' => true, 'published_at' => now()->subDay()]);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.blogs')
        ->assertSeeInOrder(['Unpublished Article', 'Draft'])
        ->assertSeeInOrder(['Future Article', 'Scheduled'])
        ->assertSeeInOrder(['Live Article', 'Published']);
});