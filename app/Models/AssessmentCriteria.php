<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'category', 'applies_to', 'weight', 'is_active'])]
class AssessmentCriteria extends Model
{
    protected $table = 'assessment_criteria';

    protected $casts = [
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function results(): HasMany
    {
        return $this->hasMany(AssessmentResult::class, 'criteria_id');
    }
}
