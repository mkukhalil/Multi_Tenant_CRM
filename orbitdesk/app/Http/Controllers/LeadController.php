<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\User;
use App\Models\Activity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LeadController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
{
    $user = Auth::user();
    $filters = $request->only(['status', 'priority', 'source', 'assigned_to', 'search']);

    $query = Lead::with(['assignedTo', 'createdBy']);

    // Super Admin → all leads (with optional tenant filter)
    if ($user->hasRole('Super Admin')) {
        $query->with('tenant');

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }
    }
    // Tenant Admin → all leads in tenant
    elseif ($user->hasRole('Tenant Admin')) {
        $query->where('tenant_id', $user->tenant_id);
    }
    // Manager → their own + their agents’ leads
    elseif ($user->hasRole('Manager')) {
        $agentIds = User::role('Agent')
            ->where('tenant_id', $user->tenant_id)
            ->pluck('id');

        $query->where('tenant_id', $user->tenant_id)
            ->where(function ($q) use ($user, $agentIds) {
                $q->where('assigned_to', $user->id)
                    ->orWhereIn('assigned_to', $agentIds);
            });
    }
    // Agent → only their assigned leads
    elseif ($user->hasRole('Agent')) {
        $query->where('tenant_id', $user->tenant_id)
            ->where('assigned_to', $user->id);
    }

    // ✅ Apply filters (status, priority, source, search, etc.)
    if (!empty($filters)) {
        $query->filter($filters);
    }

    $leads = $query->latest()->paginate($request->get('per_page', 15));

    return response()->json($leads);
}

    public function store(Request $request)
    {
        $this->authorize('create', Lead::class);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'source' => 'nullable|string|in:' . implode(',', Lead::SOURCES),
            'status' => 'nullable|string|in:' . implode(',', Lead::STATUSES),
            'priority' => 'nullable|string|in:' . implode(',', Lead::PRIORITIES),
            'stage' => 'nullable|string|max:50',
            'value' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'next_follow_up' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // ✅ Check assigned_to respects tenant + role restrictions
        if (!empty($data['assigned_to'])) {
            $assignedUser = User::find($data['assigned_to']);

            if ($user = Auth::user()) {
                if ($user->hasRole('Super Admin')) {
                    // no restriction
                } elseif ($user->hasRole('Tenant Admin')) {
                    if ($assignedUser->tenant_id !== $user->tenant_id) {
                        return response()->json(['error' => 'Invalid assignee'], 422);
                    }
                } elseif ($user->hasRole('Manager')) {
                    $agentIds = User::role('Agent')
                        ->where('tenant_id', $user->tenant_id)
                        ->pluck('id')
                        ->push($user->id);

                    if (!$agentIds->contains($assignedUser->id)) {
                        return response()->json(['error' => 'Invalid assignee'], 422);
                    }
                } elseif ($user->hasRole('Agent')) {
                    if ($assignedUser->id !== $user->id) {
                        return response()->json(['error' => 'Agents can only assign to themselves'], 422);
                    }
                }
            }
        }

        $lead = Lead::create([
            'tenant_id' => Auth::user()->tenant_id,
            'created_by' => Auth::id(),
            'assigned_to' => $data['assigned_to'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'title' => $data['title'] ?? null,
            'source' => $data['source'] ?? 'other',
            'status' => $data['status'] ?? 'new',
            'priority' => $data['priority'] ?? 'medium',
            'stage' => $data['stage'] ?? 'prospect',
            'value' => $data['value'] ?? 0,
            'next_follow_up' => $data['next_follow_up'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->logActivity($lead, 'created', 'Lead created');

        return response()->json($lead->load(['assignedTo', 'createdBy']), 201);
    }

    public function show(Lead $lead)
    {
        $this->authorize('view', $lead);
        return response()->json($lead->load(['assignedTo', 'createdBy', 'tenant']));
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $originalData = $lead->getAttributes();

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'source' => 'nullable|string|in:' . implode(',', Lead::SOURCES),
            'status' => 'nullable|string|in:' . implode(',', Lead::STATUSES),
            'priority' => 'nullable|string|in:' . implode(',', Lead::PRIORITIES),
            'stage' => 'nullable|string|max:50',
            'value' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'next_follow_up' => 'nullable|date',
            'last_contacted' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $lead->update($data);

        $changes = $this->getChanges($originalData, $lead->getAttributes());
        if (!empty($changes)) {
            $this->logActivity($lead, 'updated', 'Lead updated', $changes);
        }

        return response()->json($lead->load(['assignedTo', 'createdBy']));
    }

    public function destroy(Lead $lead)
    {
        $this->authorize('delete', $lead);
        $this->logActivity($lead, 'deleted', 'Lead deleted');
        $lead->delete();

        return response()->json(['message' => 'Lead deleted successfully']);
    }

    public function stats()
    {
        $user = Auth::user();
        $baseQuery = Lead::query();

        if (!$user->hasRole('Super Admin')) {
            $baseQuery->where('tenant_id', $user->tenant_id);

            if ($user->hasRole('Manager')) {
                $agentIds = User::role('Agent')
                    ->where('tenant_id', $user->tenant_id)
                    ->pluck('id');

                $baseQuery->where(function ($q) use ($user, $agentIds) {
                    $q->where('assigned_to', $user->id)
                        ->orWhereIn('assigned_to', $agentIds);
                });
            } elseif ($user->hasRole('Agent')) {
                $baseQuery->where('assigned_to', $user->id);
            }
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'new' => (clone $baseQuery)->where('status', 'new')->count(),
            'contacted' => (clone $baseQuery)->where('status', 'contacted')->count(),
            'qualified' => (clone $baseQuery)->where('status', 'qualified')->count(),
            'won' => (clone $baseQuery)->where('status', 'won')->count(),
            'lost' => (clone $baseQuery)->where('status', 'lost')->count(),
            'by_priority' => [
                'low' => (clone $baseQuery)->where('priority', 'low')->count(),
                'medium' => (clone $baseQuery)->where('priority', 'medium')->count(),
                'high' => (clone $baseQuery)->where('priority', 'high')->count(),
            ],
            'by_source' => (clone $baseQuery)
                ->selectRaw('source, count(*) as count')
                ->groupBy('source')
                ->pluck('count', 'source'),
        ];

        return response()->json($stats);
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $request->validate([
            'status' => 'required|string|in:' . implode(',', Lead::STATUSES),
        ]);

        $oldStatus = $lead->status;
        $lead->update(['status' => $request->status]);

        $this->logActivity($lead, 'status_changed',
            "Status changed from {$oldStatus} to {$request->status}",
            ['old_status' => $oldStatus, 'new_status' => $request->status]
        );

        return response()->json($lead);
    }

    public function assign(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $user = Auth::user();
        $assignee = User::find($request->assigned_to);

        // ✅ Restrict assignment based on role
        if ($user->hasRole('Tenant Admin')) {
            if ($assignee->tenant_id !== $user->tenant_id) {
                return response()->json(['error' => 'Invalid assignee'], 422);
            }
        } elseif ($user->hasRole('Manager')) {
            $agentIds = User::role('Agent')
                ->where('tenant_id', $user->tenant_id)
                ->pluck('id')
                ->push($user->id);
            if (!$agentIds->contains($assignee->id)) {
                return response()->json(['error' => 'Invalid assignee'], 422);
            }
        } elseif ($user->hasRole('Agent') && $assignee->id !== $user->id) {
            return response()->json(['error' => 'Agents can only assign to themselves'], 422);
        }

        $oldAssignee = $lead->assigned_to;
        $lead->update(['assigned_to' => $request->assigned_to]);

        $this->logActivity($lead, 'assigned',
            "Lead assigned to {$assignee->name}",
            ['old_assignee' => $oldAssignee, 'new_assignee' => $request->assigned_to]
        );

        return response()->json($lead->load('assignedTo'));
    }

    public function bulkActions(Request $request)
    {
        $request->validate([
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'exists:leads,id',
            'action' => 'required|in:delete,assign,update_status',
            'assigned_to' => 'required_if:action,assign|exists:users,id',
            'status' => 'required_if:action,update_status|in:' . implode(',', Lead::STATUSES),
        ]);

        $leads = Lead::whereIn('id', $request->lead_ids)->get();

        foreach ($leads as $lead) {
            $this->authorize('update', $lead);
        }

        switch ($request->action) {
            case 'delete':
                foreach ($leads as $lead) {
                    $this->logActivity($lead, 'deleted', 'Lead deleted via bulk action');
                }
                Lead::whereIn('id', $request->lead_ids)->delete();
                $message = 'Leads deleted successfully';
                break;

            case 'assign':
                $user = User::find($request->assigned_to);
                foreach ($leads as $lead) {
                    $this->logActivity($lead, 'assigned',
                        "Lead assigned to {$user->name} via bulk action",
                        ['new_assignee' => $request->assigned_to]
                    );
                }
                Lead::whereIn('id', $request->lead_ids)
                    ->update(['assigned_to' => $request->assigned_to]);
                $message = 'Leads assigned successfully';
                break;

            case 'update_status':
                foreach ($leads as $lead) {
                    $this->logActivity($lead, 'status_changed',
                        "Status changed to {$request->status} via bulk action",
                        ['new_status' => $request->status]
                    );
                }
                Lead::whereIn('id', $request->lead_ids)
                    ->update(['status' => $request->status]);
                $message = 'Leads status updated successfully';
                break;
        }

        return response()->json(['message' => $message]);
    }

    public function activities(Lead $lead)
    {
        $this->authorize('view', $lead);

        $activities = $lead->activities()
            ->with('user')
            ->latest()
            ->get();

        return response()->json($activities);
    }

    private function logActivity(Lead $lead, string $action, string $description, array $changes = [])
    {
        Activity::create([
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => Auth::id(),
            'subject_id' => $lead->id,
            'subject_type' => Lead::class,
            'type' => 'lead',
            'action' => $action,
            'description' => $description,
            'changes' => $changes,
        ]);
    }

    private function getChanges(array $original, array $updated)
    {
        $changes = [];
        foreach ($updated as $key => $value) {
            if (array_key_exists($key, $original) && $original[$key] != $value) {
                $changes[$key] = [
                    'from' => $original[$key],
                    'to' => $value
                ];
            }
        }
        return $changes;
    }

    public function filterOptions()
    {
        $user = Auth::user();

        $options = [
            'statuses' => Lead::STATUSES,
            'priorities' => Lead::PRIORITIES,
            'sources' => Lead::SOURCES,
            'assigned_users' => [],
        ];

        if ($user->hasRole('Super Admin')) {
            $options['assigned_users'] = User::select('id', 'name', 'email')->get();
            $options['tenants'] = \App\Models\Tenant::select('id', 'name')->get();
        } elseif ($user->hasRole('Tenant Admin')) {
            $options['assigned_users'] = User::where('tenant_id', $user->tenant_id)
                ->select('id', 'name', 'email')
                ->get();
        } elseif ($user->hasRole('Manager')) {
            $agentIds = User::role('Agent')
                ->where('tenant_id', $user->tenant_id)
                ->pluck('id')
                ->push($user->id);

            $options['assigned_users'] = User::whereIn('id', $agentIds)
                ->select('id', 'name', 'email')
                ->get();
        } else {
            $options['assigned_users'] = [$user->only(['id', 'name', 'email'])];
        }

        return response()->json($options);
    }

    public function overdueLeads(Request $request)
    {
        $user = Auth::user();

        $query = Lead::overdue()->with(['assignedTo', 'createdBy']);

        if (!$user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);

            if ($user->hasRole('Manager')) {
                $agentIds = User::role('Agent')
                    ->where('tenant_id', $user->tenant_id)
                    ->pluck('id');

                $query->where(function ($q) use ($user, $agentIds) {
                    $q->where('assigned_to', $user->id)
                        ->orWhereIn('assigned_to', $agentIds);
                });
            } elseif ($user->hasRole('Agent')) {
                $query->where('assigned_to', $user->id);
            }
        }

        $leads = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($leads);
    }

    public function dueTodayLeads(Request $request)
    {
        $user = Auth::user();

        $query = Lead::dueToday()->with(['assignedTo', 'createdBy']);

        if (!$user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);

            if ($user->hasRole('Manager')) {
                $agentIds = User::role('Agent')
                    ->where('tenant_id', $user->tenant_id)
                    ->pluck('id');

                $query->where(function ($q) use ($user, $agentIds) {
                    $q->where('assigned_to', $user->id)
                        ->orWhereIn('assigned_to', $agentIds);
                });
            } elseif ($user->hasRole('Agent')) {
                $query->where('assigned_to', $user->id);
            }
        }

        $leads = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($leads);
    }

    public function addNote(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $currentNotes = $lead->notes ? $lead->notes . "\n\n" : '';
        $newNote = "[" . now()->format('Y-m-d H:i') . "] " . Auth::user()->name . ": " . $request->note;

        $lead->update(['notes' => $currentNotes . $newNote]);

        $this->logActivity($lead, 'note_added', 'Note added to lead');

        return response()->json(['message' => 'Note added successfully', 'lead' => $lead]);
    }
}
