<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetForm extends Model
{
    protected $fillable = ['form_number', 'asset_id', 'user_id' , 'status' , 'issued_user_id','assets'];


    protected $casts = [
        'assets' => 'array',
    ];
    public function asset():HasMany
    {
        return $this->hasMany(Asset::class , 'form_id');
    }



    public function user() :BelongsTo
    {
        return $this->belongsTo(User::class , 'user_id');
    }

    public function issued_user() :BelongsTo
    {
        return $this->belongsTo(User::class , 'issued_user_id');
    }
}