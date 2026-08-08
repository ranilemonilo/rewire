<?php

namespace App\Models;

use Database\Factories\GalleryItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string|null $title
 * @property string $image
 * @property string|null $caption
 * @property int $order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class GalleryItem extends Model
{
    /** @use HasFactory<GalleryItemFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = ['title', 'image', 'caption', 'order'];

    /**
     * @param  Builder<GalleryItem>  $query
     * @return Builder<GalleryItem>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'caption'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('gallery')
            ->setDescriptionForEvent(fn (string $eventName) => 'The gallery photo "'.($this->title ?: 'Untitled').'" was '.$eventName);
    }
}