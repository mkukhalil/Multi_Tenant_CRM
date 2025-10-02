<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'assigned_to',
        'name',
        'email',
        'phone',
        'company',
        'title',
        'source',
        'status',
        'priority',
        'stage',
        'value',
        'notes',
        'next_follow_up',
        'last_contacted',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'value' => 'decimal:2',
        'next_follow_up' => 'datetime',
        'last_contacted' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'new',
        'priority' => 'medium',
        'stage' => 'prospect',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activities()
    {
        return $this->morphMany(\App\Models\Activity::class, 'subject');
    }

    // Scopes
    public function scopeFilter($query, $filters)
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        
        if (isset($filters['source'])) {
            $query->where('source', $filters['source']);
        }
        
        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }
        
        if (isset($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('company', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('phone', 'like', '%' . $filters['search'] . '%');
            });
        }
    }

    public function scopeOverdue($query)
    {
        return $query->where('next_follow_up', '<', now())
                    ->whereNotIn('status', ['won', 'lost']);
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('next_follow_up', today())
                    ->whereNotIn('status', ['won', 'lost']);
    }

    // Accessors & Mutators
    public function getIsOverdueAttribute()
    {
        return $this->next_follow_up && $this->next_follow_up->lt(now()) && 
               !in_array($this->status, ['won', 'lost']);
    }

    public function setNextFollowUpAttribute($value)
    {
        $this->attributes['next_follow_up'] = $value ?: null;
    }

    // Business Logic Methods
    public function markAsContacted()
    {
        $this->update([
            'last_contacted' => now(),
            'status' => $this->status === 'new' ? 'contacted' : $this->status
        ]);
    }

    public function convertToCustomer()
    {
        // You'll need to implement customer conversion logic
        $this->update(['status' => 'won']);
        
        // Create customer record, opportunity, etc.
        return true;
    }

    // Constants
    const STATUSES = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
    const PRIORITIES = ['low', 'medium', 'high', 'urgent'];
    const SOURCES = ['website', 'referral', 'social_media', 'cold_call', 'email', 'other'];
}