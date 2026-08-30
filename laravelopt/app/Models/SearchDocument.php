<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchDocument extends Model
{
    protected $table = 'search_documents';

    protected $fillable = [
        'searchable_type', 'searchable_id', 'locale', 'type',
        'title', 'url', 'image', 'price', 'currency', 'in_stock',
        'popularity', 'weight',
        'norm_title', 'norm_strict', 'norm_all',
        'stem_strict', 'stem_all', 'translit_strict', 'translit_all',
        'tokens', 'translit',
        'token_fields', 'translit_fields',
        'strict_tokens', 'strict_translit',
        'title_tokens', 'keyword_tokens', 'body_tokens',
    ];

    protected $casts = [
        'tokens' => 'array',
        'translit' => 'array',
        'token_fields' => 'array',
        'translit_fields' => 'array',
        'strict_tokens' => 'array',
        'strict_translit' => 'array',
        'title_tokens' => 'array',
        'keyword_tokens' => 'array',
        'body_tokens' => 'array',
        'in_stock' => 'boolean',
        'price' => 'decimal:2',
        'popularity' => 'float',
        'weight' => 'float',
    ];
}
