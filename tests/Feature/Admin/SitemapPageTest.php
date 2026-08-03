test('admin can view the sitemap page and only sees published posts', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $published = Post::factory()->create([
        'title' => 'Published Post',
        'is_published' => true,
    ]);

    $draft = Post::factory()->create([
        'title' => 'Draft Post',
        'is_published' => false,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sitemap'))
        ->assertOk()
        ->assertSee(route('blog.detail', $published->slug))
        ->assertDontSee(route('blog.detail', $draft->slug));
});