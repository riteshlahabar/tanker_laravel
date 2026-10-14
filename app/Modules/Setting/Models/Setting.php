<?php

declare(strict_types=1);

namespace App\Modules\Setting\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['key', 'value'];
}
