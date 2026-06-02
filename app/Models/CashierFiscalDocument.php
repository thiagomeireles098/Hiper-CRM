<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierFiscalDocument extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_AUTHORIZED = 'authorized';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'tenant_id',
        'cashier_id',
        'cashier_sale_id',
        'local_id',
        'type',
        'status',
        'provider',
        'provider_document_id',
        'access_key',
        'series',
        'number',
        'xml_path',
        'danfe_url',
        'print_payload',
        'authorization_payload',
        'error_message',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'print_payload' => 'array',
            'authorization_payload' => 'array',
            'issued_at' => 'datetime',
        ];
    }
}
