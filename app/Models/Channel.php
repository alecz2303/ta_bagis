<?php

namespace App\Models;

use Webkul\Core\Models\Channel as BaseChannel;

class Channel extends BaseChannel
{
    // OJO: deja que el prefijo lo ponga Bagisto/tu conexión
    protected $table = 'channels';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
}
