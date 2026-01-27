<?php

declare(strict_types=1);

namespace Core\Developer\Models;

use Core\Tenant\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Server model for SSH connections.
 *
 * Stores server connection details with encrypted private key storage.
 * Used with RemoteServerManager trait for remote command execution.
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string $ip
 * @property int $port
 * @property string $user
 * @property string|null $private_key
 * @property string $status
 * @property \Carbon\Carbon|null $last_connected_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Server extends Model
{
    use BelongsToWorkspace;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'ip',
        'port',
        'user',
        'private_key',
        'status',
        'last_connected_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'private_key',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'port' => 'integer',
        'last_connected_at' => 'datetime',
        'private_key' => 'encrypted',
    ];

    /**
     * Default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'port' => 22,
        'user' => 'root',
        'status' => 'pending',
    ];

    /**
     * Get the decrypted private key.
     *
     * Note: With the 'encrypted' cast, $this->private_key is automatically decrypted.
     * This method provides a null-safe accessor.
     */
    public function getDecryptedPrivateKey(): ?string
    {
        return $this->private_key;
    }

    /**
     * Check if the server has a valid private key.
     */
    public function hasPrivateKey(): bool
    {
        return $this->getDecryptedPrivateKey() !== null;
    }

    /**
     * Get the server's connection string.
     */
    public function getConnectionStringAttribute(): string
    {
        return "{$this->user}@{$this->ip}:{$this->port}";
    }

    /**
     * Check if the server is connected.
     */
    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    /**
     * Check if the server is in a failed state.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark the server as failed.
     */
    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
        ]);

        if ($reason) {
            activity()
                ->performedOn($this)
                ->withProperties(['reason' => $reason])
                ->log('Server connection failed');
        }
    }

    /**
     * Configure activity logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'ip', 'port', 'user', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope to only connected servers.
     */
    public function scopeConnected(Builder $query): Builder
    {
        return $query->where('status', 'connected');
    }

    /**
     * Scope to only failed servers.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to only pending servers.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
