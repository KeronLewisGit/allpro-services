<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A floor plan, specification or tender document sent with a quote request. Private disk only.
 *
 * @property int $id
 * @property int $quote_request_id
 * @property string $path
 * @property string $original_name
 * @property string|null $mime
 * @property int $size
 * @property-read QuoteRequest $quote
 */
class QuoteFile extends Model
{
    protected $fillable = ['path', 'original_name', 'mime', 'size'];

    /** @return BelongsTo<QuoteRequest, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class, 'quote_request_id');
    }
}
