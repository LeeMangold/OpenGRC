<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class FileAttachment
 *
 * @property int $id
 * @property string $file_name
 * @property string $file_path
 * @property int $file_size
 * @property Carbon $uploaded_at
 * @property int $uploaded_by
 * @property int $data_request_id
 * @property-read DataRequest $dataRequest
 *
 * @method static Builder|FileAttachment newModelQuery()
 * @method static Builder|FileAttachment newQuery()
 * @method static Builder|FileAttachment query()
 * @method static Builder|FileAttachment whereFileName($value)
 * @method static Builder|FileAttachment whereFilePath($value)
 * @method static Builder|FileAttachment whereFileSize($value)
 * @method static Builder|FileAttachment whereUploadedAt($value)
 * @method static Builder|FileAttachment whereUploadedBy($value)
 * @method static Builder|FileAttachment whereDataRequestId($value)
 *
 * @mixin Eloquent
 */
class FileAttachment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_name',
        'file_path',
        'file_size',
        'uploaded_at',
        'uploaded_by',
        'data_request_id',
    ];

    /**
     * Get the data request that owns the file attachment.
     */
    public function dataRequest(): BelongsTo
    {
        return $this->belongsTo(DataRequest::class);
    }

    public function dataRequestResponse(): BelongsTo
    {
        return $this->belongsTo(DataRequestResponse::class);
    }

    public function auditItem(): BelongsTo
    {
        return $this->belongsTo(AuditItem::class);
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
