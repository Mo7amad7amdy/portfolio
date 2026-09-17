<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitEvent extends Model
{
    public const UPDATED_AT = null;

    /** Events the browser script may send. */
    public const NAMES = ['cv_download', 'social_click', 'project_click', 'email_click', 'whatsapp_click', 'outbound_click', 'section', 'contact_submit'];

    public const LABELS = [
        'cv_download' => 'CV downloads',
        'social_click' => 'Social profile clicks',
        'project_click' => 'Project link clicks',
        'email_click' => 'Email clicks',
        'whatsapp_click' => 'WhatsApp clicks',
        'outbound_click' => 'Other outbound clicks',
        'contact_submit' => 'Contact form sent',
        'section' => 'Section views',
    ];

    protected $fillable = ['visit_id', 'name', 'target', 'created_at'];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
