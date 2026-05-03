<?php

namespace App\Models;

use App\Enums\PropertyStatus;
use Illuminate\Database\Eloquent\{
    Model,
    Relations\BelongsTo,
    SoftDeletes,
};

/**
 * A sellable unit (`properties` table) belonging to a {@see Project}.
 *
 * @property int $id
 * @property int $project_id
 * @property string $label
 * @property PropertyStatus $status
 * @property string|null $price Stored as decimal; cast to string with two fractional digits.
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Property extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'label',
        'status',
        'price',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PropertyStatus::class,
            'price' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
