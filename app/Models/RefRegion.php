<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Wilayah SIAKAD (id_wilayah Neo Feeder): provinsi > kab/kota > kecamatan.
 */
class RefRegion extends Model
{
    public const LEVEL_PROVINCE = 1;
    public const LEVEL_REGENCY = 2;
    public const LEVEL_DISTRICT = 3;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'level',
        'name',
        'parent_id',
    ];

    protected $casts = [
        'level' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
