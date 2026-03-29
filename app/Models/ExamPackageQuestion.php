<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamPackageQuestion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'exam_package_id',
        'question_id',
        'urutan',
    ];

    public function examPackage(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExamPackage::class);
    }

    public function question(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
