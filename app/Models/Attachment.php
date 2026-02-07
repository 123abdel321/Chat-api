<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'filename',
        'original_name',
        'mime_type',
        'path',
        'size',
        'dimensions',
        'duration',
    ];

    protected $casts = [
        'dimensions' => 'array',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }
}
