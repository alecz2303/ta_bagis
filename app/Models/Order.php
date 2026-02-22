<?php

namespace App\Models;

use Webkul\Sales\Models\Order as BaseOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Core\Models\Channel;

class Order extends BaseOrder
{
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }
}
