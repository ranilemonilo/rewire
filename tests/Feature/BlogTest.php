<?php

use App\Enums\SettingKey;
use App\Models\Setting;
use App\Models\Post;

test('the blog index shows published posts and hides drafts', function () {
    $published = Post::factory()->create(['title' => 'Published Post', 'is_published' => true]);
    $draft = Post::factory()->create(['title' => 'Draft Post', 'is_published' => false]);

    $this->get(route('blogs'))
        ->assertOk()
        ->assertSee($published->title)
        ->assertDontSee($draft->title);
});

test('the blog show page renders a published post', function () {
    $post = Post::factory()->create([
        'title' => 'A Published Post',
        'body' => 'This is the body of the post.',
        'is_published' => true,
    ]);

    $this->get(route('blog.detail', $post->slug))
        ->assertOk()
        ->assertSee($post->title)
        ->assertSee($post->body);
});

test('visiting a draft post 404s', function () {
    $post = Post::factory()->create(['is_published' => false]);

    $this->get(route('blog.detail', $post->slug))->assertNotFound();
});

test('visiting a nonexistent post slug 404s', function () {
    $this->get(route('blog.detail', 'nonexistent-slug'))->assertNotFound();
});

test('the blog index shows a custom SEO description when set', function () {
    Setting::put(SettingKey::SeoDescription, 'Custom blog description for SEO.');

    $this->get(route('blogs'))
        ->assertOk()
        ->assertSee('Custom blog description for SEO.', false);
});

test('the blog show page uses the post excerpt as its meta description', function () {
    $post = Post::factory()->create([
        'title' => 'A Published Post',
        'excerpt' => 'A short summary of this specific post.',
        'is_published' => true,
    ]);

    $this->get(route('blog.detail', $post->slug))
        ->assertOk()
        ->assertSee('A short summary of this specific post.', false);
});

test('the blog show page falls back to the global SEO description when the post has no excerpt', function () {
    Setting::put(SettingKey::SeoDescription, 'Fallback site-wide description.');

    $post = Post::factory()->create([
        'title' => 'A Published Post',
        'excerpt' => null,
        'is_published' => true,
    ]);

    $this->get(route('blog.detail', $post->slug))
        ->assertOk()
        ->assertSee('Fallback site-wide description.', false);
});
