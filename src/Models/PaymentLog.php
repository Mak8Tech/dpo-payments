<?php

namespace Mak8Tech\DpoPayments\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    protected $table = 'dpo_payment_logs';

    protected $fillable = [
        'reference',
        'token',
        'action',
        'type',
        'payload',
        'response',
        'status_code',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Create a request log
     */
    public static function logRequest(string $reference, string $action, $payload, string $token = null): self
    {
        return static::create([
            'reference' => $reference,
            'token' => $token,
            'action' => $action,
            'type' => 'request',
            'payload' => is_array($payload) ? json_encode($payload) : $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Create a response log
     */
    public static function logResponse(string $reference, string $action, $response, string $statusCode = null, string $token = null): self
    {
        return static::create([
            'reference' => $reference,
            'token' => $token,
            'action' => $action,
            'type' => 'response',
            'response' => is_array($response) ? json_encode($response) : $response,
            'status_code' => $statusCode,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Create a callback log
     */
    public static function logCallback(string $reference, $payload, string $token = null): self
    {
        return static::create([
            'reference' => $reference,
            'token' => $token,
            'action' => 'callback',
            'type' => 'callback',
            'payload' => is_array($payload) ? json_encode($payload) : $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
