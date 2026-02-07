<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Chat extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'avatar',
        'created_by',
        'description',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function participants()
    {
        return $this->belongsToMany(User::class, 'participants')
            ->withPivot('role', 'last_read_at')
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    public function isParticipant($userId)
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    public function getOtherParticipants($userId)
    {
        return $this->participants()->where('user_id', '!=', $userId)->get();
    }
}
