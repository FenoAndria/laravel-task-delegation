<?php

namespace App\Models;

use App\Enums\ActivityType;
use App\Events\MissionChanged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property ActivityType $type
 * @property array<string, mixed>|null $properties
 * @property Carbon $created_at
 */
class Activity extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'mission_id',
        'task_id',
        'actor_id',
        'type',
        'properties',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'properties' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::created(fn (Activity $model) => MissionChanged::dispatch($model->mission_id));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return BelongsTo<Mission, $this>
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function description(): string
    {
        return $this->type->describe($this->properties ?? []);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function record(Mission $mission, ?User $actor, ActivityType $type, array $properties = [], ?Task $task = null): self
    {
        return static::create([
            'organization_id' => $mission->organization_id,
            'mission_id' => $mission->id,
            'task_id' => $task?->id,
            'actor_id' => $actor?->id,
            'type' => $type,
            'properties' => $properties ?: null,
        ]);
    }
}
