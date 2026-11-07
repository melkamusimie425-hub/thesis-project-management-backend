<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['group_id', 'title', 'title_order', 'status', 'reviewer_comment'])]
class ProjectTitle extends Model
{
    public function group(): BelongsTo
    {
        return $this->belongsTo(IndustrialProjectGroup::class, 'group_id');
    }
}
