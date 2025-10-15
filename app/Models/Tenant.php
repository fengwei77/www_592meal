<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant Model (租戶 Schema 管理)
 *
 * 管理每個店家的獨立 PostgreSQL Schema
 * Schema 命名格式: tenant_{store_id}
 */
class Tenant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'schema_name',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Boot the model.
     *
     * 自動生成 schema_name 如果未提供
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($tenant) {
            if (empty($tenant->schema_name)) {
                // 注意：此時 $tenant->id 尚未生成，需要在建立 Tenant 時手動指定 schema_name
                // 實際應用中會在 StoreOnboarding 組件中明確指定
            }
        });
    }

    /**
     * 關聯：所屬店家
     *
     * @return BelongsTo<Store, Tenant>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
