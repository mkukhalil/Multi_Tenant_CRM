<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'subject_id',
        'subject_type',
        'type',
        'action',
        'description',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeForLead($query, $leadId)
    {
        return $query->where('subject_type', Lead::class)
                    ->where('subject_id', $leadId);
    }

    // Constants
    const ACTIONS = [
        'created', 'updated', 'deleted', 'status_changed', 
        'assigned', 'converted', 'follow_up_added', 'note_added'
    ];

    const TYPES = ['lead', 'opportunity', 'contact', 'account'];
}