<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A photo a client attached to a service request. Private disk only.
 *
 * @property int $id
 * @property int $service_ticket_id
 * @property string $path
 * @property string $original_name
 * @property string|null $mime
 * @property int $size
 * @property-read ServiceTicket $ticket
 */
class TicketPhoto extends Model
{
    protected $fillable = ['path', 'original_name', 'mime', 'size'];

    /** @return BelongsTo<ServiceTicket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }
}
