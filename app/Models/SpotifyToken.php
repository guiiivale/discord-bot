<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpotifyToken extends Model
{
    use HasFactory;

    protected $table = 'spotify_token';

    protected $fillable = [
        'token',
        'expires_in',
        'token_type',
    ];
}
