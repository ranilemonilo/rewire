<?php

namespace App\Models;

use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $content
 * @property string|null $featured_image
 * @property bool $is_published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, HasSlug, LogsActivity;

    protected $fillable = ['title', 'slug', 'excerpt', 'content', 'featured_image', 'is_published'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function getSlugOptions(): SlugOptions
    {
        // Generated from the title on create only -- regenerating on every title edit
        // would silently change the page's public URL and break existing links, so an
        // admin who wants to change the slug after creation edits it explicitly instead.
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getActivitylogOptions(): LogOptions
    {
        // Deliberately not ->dontLogEmptyChanges(): only is_published is tracked (title/
        // excerpt/content are excluded to keep entries readable), so most real edits touch
        // those fields without touching is_published -- logging empty changes is what
        // keeps those saves from being silently skipped.
        return LogOptions::defaults()
            ->logOnly(['is_published'])
            ->logOnlyDirty()
            ->useLogName('pages')
            ->setDescriptionForEvent(fn (string $eventName) => "The page \"{$this->title}\" was {$eventName}");
    }
}
