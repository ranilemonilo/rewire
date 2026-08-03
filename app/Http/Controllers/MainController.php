<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\Setting;
use App\Models\GalleryItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Symfony\Component\HttpFoundation\Response;

class MainController extends Controller
{
    public function index(): View
    {
        return view('pages.main.index', [...$this->footerData(),
            'seoDescription' => Setting::get('seo_description'),
            'analyticsId' => Setting::get('analytics_id'),
           'services' => Service::query()
    ->active()
    ->ordered()
    ->get(),

'gallery' => GalleryItem::query()
    ->ordered()
    ->take(6)
    ->get(),

'aboutPage' => Page::query()
    ->published()
    ->oldest()
    ->first(),
            'contactAddress' => Setting::get('contact_address'),
            'contactEmail' => Setting::get('contact_email'),
            'contactPhone' => Setting::get('contact_phone'),
        ]);
    }

    public function blogs(): View
    {
        return view('pages.main.blogs', [...$this->footerData(),
            'posts' => Post::query()->published()->with('author')->latest()->paginate(9),
        ]);
    }

    public function blogDetail(string $slug): View
    {
        $post = Post::query()->where('slug', $slug)->published()->with('author')->firstOrFail();

        return view('pages.main.blog-detail', [...$this->footerData(),
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
            'companyName' => Setting::get('company_name'),
            'companyTagline' => Setting::get('company_tagline'),
            'socialLinks' => collect([
                'linkedin' => Setting::get('social_linkedin'),
                'twitter' => Setting::get('social_twitter'),
                'github' => Setting::get('social_github'),
                'instagram' => Setting::get('social_instagram'),
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
