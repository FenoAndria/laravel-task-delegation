<?php

namespace App\Models;

use App\Events\MissionChanged;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'mission_id',
        'user_id',
        'body',
    ];

    protected static function booted(): void
    {
        static::created(fn (Comment $model) => MissionChanged::dispatch($model->mission_id));
        static::deleted(fn (Comment $model) => MissionChanged::dispatch($model->mission_id));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Mission, $this>
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }
}
