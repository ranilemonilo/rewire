<?php

namespace App\Http\Controllers;

use App\Enums\SettingKey;
use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Symfony\Component\HttpFoundation\Response;
use App\Models\GalleryItem;

class MainController extends Controller
{
  public function index(): View
{
    return view('pages.main.index', [...$this->footerData(),
        'seoDescription' => Setting::get(SettingKey::SeoDescription),
        'analyticsId' => Setting::get(SettingKey::AnalyticsId),
        'services' => Service::hydrate(Cache::remember(
            'homepage.services',
            now()->addMinutes(10),
            fn () => Service::query()->active()->ordered()->get()->toArray()
        )),
        'gallery' => GalleryItem::hydrate(Cache::remember(
            'homepage.gallery',
            now()->addMinutes(10),
            fn () => GalleryItem::query()->ordered()->take(6)->get()->toArray()
        )),
        'aboutPage' => Page::hydrate(Cache::remember(
            'homepage.about-page',
            now()->addMinutes(10),
            fn () => Page::query()->published()->oldest()->get()->toArray()
        ))->first(),
        'contactAddress' => Setting::get(SettingKey::ContactAddress),
        'contactEmail' => Setting::get(SettingKey::ContactEmail),
        'contactPhone' => Setting::get(SettingKey::ContactPhone),
    ]);
}
    

    public function blogs(): View
{
    return view('pages.main.blogs', [...$this->footerData(),
        'seoDescription' => Setting::get(SettingKey::SeoDescription, 'News, guides, and updates from '.(Setting::get(SettingKey::CompanyName) ?? 'us').'.'),
        'analyticsId' => Setting::get(SettingKey::AnalyticsId),
        'posts' => Post::query()->published()->with('author')->latest()->paginate(9),
    ]);
}
    public function blogDetail(string $slug): View
{
    $post = Post::query()->where('slug', $slug)->published()->with('author')->firstOrFail();

    return view('pages.main.blog-detail', [...$this->footerData(),
        'seoDescription' => $post->excerpt ?? Setting::get(SettingKey::SeoDescription),
        'analyticsId' => Setting::get(SettingKey::AnalyticsId),
        'post' => $post,
    ]);
}

    /**
     * Shared data for the site chrome (footer) rendered on every public page
     * via layouts/main.blade.php, kept out of Blade so no page performs its
     * own Setting lookups.
     *
     * @return array{companyName: ?string, companyTagline: ?string, socialLinks: Collection<string, string>}
     */
    private function footerData(): array
    {
        return [
            'companyName' => Setting::get(SettingKey::CompanyName),
            'companyTagline' => Setting::get(SettingKey::CompanyTagline),
            'socialLinks' => collect([
                'linkedin' => Setting::get(SettingKey::SocialLinkedin),
                'twitter' => Setting::get(SettingKey::SocialTwitter),
                'github' => Setting::get(SettingKey::SocialGithub),
                'instagram' => Setting::get(SettingKey::SocialInstagram),
            ])->filter(),
        ];
    }

    public function sitemap(): Response
    {
        $sitemap = Sitemap::create()
            ->add(Url::create(route('home')))
            ->add(Url::create(route('blogs')));

        Post::query()->published()->get()->each(
            fn (Post $post) => $sitemap->add(
                Url::create(route('blog.detail', $post->slug))->setLastModificationDate($post->updated_at)
            )
        );

        return $sitemap->toResponse(request());
    }
}