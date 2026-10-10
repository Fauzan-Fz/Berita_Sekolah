<?php

namespace App\Models;

use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'category_id', 'title', 'slug', 'content',
        'cover_image', 'status', 'rejection_reason', 'views_count', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title).'-'.Str::random(6);
            }
            $post->title = HtmlSanitizer::cleanPlainText($post->title);
            $post->content = HtmlSanitizer::clean($post->content);
        });

        static::updating(function (Post $post) {
            $post->title = HtmlSanitizer::cleanPlainText($post->title);
            $post->content = HtmlSanitizer::clean($post->content);
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}
