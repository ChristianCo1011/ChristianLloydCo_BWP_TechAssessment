<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{
    Model,
    Relations\HasMany,
};

/**
 * A development / estate (`projects` table) that owns many {@see Property} records.
 *
 * @property int $id
 * @property string $name
 * @property string|null $code
 */
class Project extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * @return HasMany<Property, $this>
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
