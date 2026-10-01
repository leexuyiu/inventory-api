<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'description',
        'price',
        'stock_quantity',
        'reorder_level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'reorder_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected function sku(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): string => strtoupper($value),
            set: fn (string $value): string => strtoupper(trim($value)),
        );
    }

    protected function stockStatus(): Attribute
    {
        return Attribute::get(fn (): string => match (true) {
            $this->stock_quantity === 0 => 'out_of_stock',
            $this->stock_quantity <= $this->reorder_level => 'low_stock',
            default => 'in_stock',
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)->withTimestamps();
    }

    public function scopeStockLevel(Builder $query, ?string $level): Builder
    {
        return match ($level) {
            'out' => $query->where('stock_quantity', 0),
            'low' => $query->where('stock_quantity', '>', 0)
                ->whereColumn('stock_quantity', '<=', 'reorder_level'),
            'in_stock' => $query->whereColumn('stock_quantity', '>', 'reorder_level'),
            default => $query,
        };
    }
}
