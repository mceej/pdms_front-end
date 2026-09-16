<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServedList extends Model
{
    protected $fillable = ['file_name', 'program', 'disaster_type', 'imported_by', 'imported_at', 'status', 'record_count'];

    protected function casts(): array
    {
        return ['imported_at' => 'datetime'];
    }

    public function records(): HasMany
    {
        return $this->hasMany(PayoutRecord::class);
    }
}
