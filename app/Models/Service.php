<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $title
 * @property string $icon
 * @property string $description
 * @property int $order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = ['title', 'icon', 'description', 'order', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    public function getActivitylogOptions(): LogOptions
    {
        // Deliberately not ->dontLogEmptyChanges(): only is_active is tracked (title/icon/
        // description are excluded to keep entries readable), so most real edits touch
        // those fields without touching is_active -- logging empty changes is what keeps
        // those saves from being silently skipped.
        return LogOptions::defaults()
            ->logOnly(['is_active'])
            ->logOnlyDirty()
            ->useLogName('services')
            ->setDescriptionForEvent(fn (string $eventName) => "The service \"{$this->title}\" was {$eventName}");
    }
}
