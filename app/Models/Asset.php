<?php

namespace App\Models;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use App\Mcp\Traits\HasMcpSupport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Asset
 *
 * Represents an IT asset in the organization's asset management system.
 */
class Asset extends Model
{
    use HasFactory, HasMcpSupport, HasTaxonomy, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Core Identification
        'asset_tag',
        'serial_number',
        'name',
        'asset_type_id',
        'category_id',
        'status_id',

        // Hardware Specifications
        'manufacturer',
        'model',
        'processor',
        'ram_gb',
        'storage_type',
        'storage_capacity_gb',
        'graphics_card',
        'screen_size',
        'mac_address',
        'ip_address',
        'hostname',
        'operating_system',
        'os_version',

        // Assignment & Location
        'assigned_to_user_id',
        'assigned_at',
        'location_id',
        'building',
        'floor',
        'room',
        'department_id',

        // Financial Information
        'purchase_date',
        'purchase_price',
        'purchase_order_number',
        'supplier_id',
        'invoice_number',
        'depreciation_method',
        'depreciation_rate',
        'current_value',
        'residual_value',

        // Warranty & Support
        'warranty_start_date',
        'warranty_end_date',
        'warranty_type',
        'warranty_provider',
        'support_contract_number',
        'support_expiry_date',

        // Lifecycle Management
        'received_date',
        'deployment_date',
        'last_audit_date',
        'next_audit_date',
        'retirement_date',
        'disposal_date',
        'disposal_method',
        'expected_life_years',

        // Maintenance & Service
        'last_maintenance_date',
        'next_maintenance_date',
        'maintenance_notes',
        'condition_id',

        // Software & Licensing
        'license_key',
        'license_type',
        'license_seats',
        'license_expiry_date',

        // Security & Compliance
        'encryption_enabled',
        'antivirus_installed',
        'last_security_scan',
        'compliance_status_id',
        'data_classification_id',

        // Relationships & Dependencies
        'parent_asset_id',

        // Additional Metadata
        'notes',
        'custom_fields',
        'tags',
        'image_url',
        'qr_code',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'purchase_date' => 'date',
        'assigned_at' => 'datetime',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'support_expiry_date' => 'date',
        'received_date' => 'date',
        'deployment_date' => 'date',
        'last_audit_date' => 'date',
        'next_audit_date' => 'date',
        'retirement_date' => 'date',
        'disposal_date' => 'date',
        'last_maintenance_date' => 'datetime',
        'next_maintenance_date' => 'datetime',
        'last_security_scan' => 'datetime',
        'license_expiry_date' => 'date',
        'purchase_price' => 'decimal:2',
        'current_value' => 'decimal:2',
        'residual_value' => 'decimal:2',
        'depreciation_rate' => 'decimal:2',
        'screen_size' => 'decimal:2',
        'encryption_enabled' => 'boolean',
        'antivirus_installed' => 'boolean',
        'is_active' => 'boolean',
        'custom_fields' => 'array',
        'tags' => 'array',
        'license_key' => 'encrypted',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    /**
     * Get the user to whom this asset is assigned.
     */
    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Get the user who created this asset record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this asset record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the category this asset belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the location of this asset.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the department this asset belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the supplier of this asset.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the parent asset (for hierarchical assets).
     */
    public function parentAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'parent_asset_id');
    }

    /**
     * Get the child assets.
     */
    public function childAssets(): HasMany
    {
        return $this->hasMany(Asset::class, 'parent_asset_id');
    }

    /**
     * Get the asset type taxonomy term.
     */
    public function assetType(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class, 'asset_type_id');
    }

    /**
     * Get the status taxonomy term.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class, 'status_id');
    }

    /**
     * Get the condition taxonomy term.
     */
    public function condition(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class, 'condition_id');
    }

    /**
     * Get the compliance status taxonomy term.
     */
    public function complianceStatus(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class, 'compliance_status_id');
    }

    /**
     * Get the data classification taxonomy term.
     */
    public function dataClassification(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class, 'data_classification_id');
    }

    /**
     * Get the implementations associated with this asset.
     */
    public function implementations(): BelongsToMany
    {
        return $this->belongsToMany(Implementation::class)
            ->withTimestamps();
    }

    /**
     * Get the asset type name accessor.
     */
    public function getAssetTypeNameAttribute(): ?string
    {
        return $this->assetType?->name;
    }

    /**
     * Get the status name accessor.
     */
    public function getStatusNameAttribute(): ?string
    {
        return $this->status?->name;
    }

    /**
     * Get the condition name accessor.
     */
    public function getConditionNameAttribute(): ?string
    {
        return $this->condition?->name;
    }

    /**
     * Get the compliance status name accessor.
     */
    public function getComplianceStatusNameAttribute(): ?string
    {
        return $this->complianceStatus?->name;
    }

    /**
     * Get the data classification name accessor.
     */
    public function getDataClassificationNameAttribute(): ?string
    {
        return $this->dataClassification?->name;
    }

    /**
     * Scope a query to only include active assets.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include assigned assets.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAssigned($query)
    {
        return $query->whereNotNull('assigned_to_user_id');
    }

    /**
     * Scope a query to filter by asset type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAssetType($query, string $assetTypeName)
    {
        return $query->whereHas('assetType', function ($q) use ($assetTypeName) {
            $q->where('name', $assetTypeName);
        });
    }

    /**
     * Scope a query to filter by status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $statusName)
    {
        return $query->whereHas('status', function ($q) use ($statusName) {
            $q->where('name', $statusName);
        });
    }
}
