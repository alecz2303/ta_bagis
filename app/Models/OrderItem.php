<?php

namespace App\Models;

use Webkul\Sales\Models\OrderItem as BaseOrderItem;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderItem extends BaseOrderItem
{
    /**
     * IMPORTANTE:
     * - Si tu BD usa prefijo (ej. tac_order_items), NO forces $table = 'order_items'
     * - Deja que el modelo base resuelva el nombre real.
     */
    // protected $table = 'order_items';

    /**
     * Debe ser compatible con el padre:
     * Webkul\Sales\Models\OrderItem::product(): MorphTo
     *
     * Bagisto normalmente usa:
     * - product_type (string)
     * - product_id   (int)
     */
    public function product(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'product_type', 'product_id');
    }
}
