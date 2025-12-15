<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataItemFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_item_id',
        'file_name',
        'drive_file_id',
        'drive_web_view_link',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function dataItem()
    {
        return $this->belongsTo(DataItem::class);
    }
}
