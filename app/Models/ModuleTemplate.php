<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleTemplate extends Model
{
    use Blameable;

    protected $fillable = [
        'key',
        'name',
        'order_no',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function projectModules(): HasMany
    {
        return $this->hasMany(ProjectModule::class, 'template_id');
    }
}
