<?php

namespace App\Models;
use App\Traits\LogsModelActivity;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use LogsModelActivity;
    use BelongsToMerchant;

    protected $fillable = ['key', 'value'];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }
}
