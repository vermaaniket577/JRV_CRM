<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_id',
        'tenant_id',
        'column_name',
        'display_name',
        'data_type',
        'form_type',
        'is_primary',
        'is_auto_increment',
        'is_nullable',
        'is_searchable',
        'is_visible',
        'is_editable',
        'is_required',
        'is_foreign_key',
        'references_table',
        'references_column',
        'display_column',
        'default_value',
        'enum_values',
        'display_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_auto_increment' => 'boolean',
        'is_nullable' => 'boolean',
        'is_searchable' => 'boolean',
        'is_visible' => 'boolean',
        'is_editable' => 'boolean',
        'is_required' => 'boolean',
        'is_foreign_key' => 'boolean',
        'enum_values' => 'array',
        'display_order' => 'integer',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(CrmTable::class, 'table_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Map MySQL data type to form input type.
     */
    public static function mapDataTypeToFormType(string $dataType, string $columnName): string
    {
        $dt = strtolower($dataType);
        $cn = strtolower($columnName);

        // Name-based detection takes priority
        if (str_contains($cn, 'email') || str_contains($cn, 'mail')) return 'email';
        if (str_contains($cn, 'phone') || str_contains($cn, 'mobile') || str_contains($cn, 'tel')) return 'tel';
        if (str_contains($cn, 'url') || str_contains($cn, 'website') || str_contains($cn, 'link')) return 'url';
        if (str_contains($cn, 'password') || str_contains($cn, 'secret')) return 'password';
        if (str_contains($cn, 'color') || str_contains($cn, 'colour')) return 'color';

        // Type-based mapping
        return match (true) {
            in_array($dt, ['text', 'mediumtext', 'longtext']) => 'textarea',
            in_array($dt, ['int', 'bigint', 'smallint', 'mediumint', 'tinyint']) => 'number',
            in_array($dt, ['decimal', 'float', 'double', 'numeric']) => 'number',
            in_array($dt, ['date']) => 'date',
            in_array($dt, ['datetime', 'timestamp']) => 'datetime-local',
            in_array($dt, ['time']) => 'time',
            in_array($dt, ['year']) => 'number',
            in_array($dt, ['enum', 'set']) => 'select',
            $dt === 'tinyint' || ($dt === 'boolean') => 'checkbox',
            in_array($dt, ['json', 'jsonb']) => 'textarea',
            in_array($dt, ['blob', 'mediumblob', 'longblob']) => 'file',
            default => 'text',
        };
    }
}
