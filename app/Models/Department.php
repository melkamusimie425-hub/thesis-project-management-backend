<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name', 'head_name', 'head_id', 'office_location', 'email', 'phone', 'description',
])]
class Department extends Model
{
    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_id');
    }
}
