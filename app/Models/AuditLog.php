<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'module',
        'record_id',
        'old_value',
        'new_value',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convenient helper to record an audit log entry.
     */
    public static function record($user, string $action, string $details = '', string $module = 'System', ?int $recordId = null, $oldValue = null, $newValue = null): self
    {
        $userId = is_numeric($user) ? (int) $user : ($user?->id ?? auth()->id());

        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'old_value' => is_array($oldValue) ? $oldValue : ($oldValue ? ['details' => $oldValue] : null),
            'new_value' => is_array($newValue) ? $newValue : ($newValue ? ['details' => $newValue] : ($details ? ['details' => $details] : null)),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
