<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupRecord extends Model
{
    public $timestamps = false;

    protected $table = 'backup_records';

    protected $primaryKey = 'backup_id';

    protected $fillable = [
        'backup_type',
        'storage_location',
        'file_name',
        'backup_status',
        'created_at',
        'created_by',
        'restored_at',
        'restored_by',
        'restoration_status',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function restorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by', 'user_id');
    }
}
