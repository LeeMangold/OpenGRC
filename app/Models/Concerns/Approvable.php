<?php

namespace App\Models\Concerns;

use App\Models\Approval;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Trait for models that can be approved.
 *
 * Models using this trait should have an `approver_id` column
 * to designate who is allowed to approve the record.
 *
 * @property int|null $approver_id The designated approver's user ID
 */
trait Approvable
{
    /**
     * Get all approvals for this model.
     */
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * Get the latest/current approval for this model.
     */
    public function latestApproval(): MorphOne
    {
        return $this->morphOne(Approval::class, 'approvable')->latestOfMany('approved_at');
    }

    /**
     * Get the designated approver for this model.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Check if this model has been approved.
     */
    public function isApproved(): bool
    {
        return $this->latestApproval()->exists();
    }

    /**
     * Check if the given user can approve this model.
     *
     * STRICT: Only the designated approver can approve.
     * If no approver is designated, no one can approve.
     * There are NO exceptions, not even for superadmin.
     */
    public function canBeApprovedBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        // If no approver is designated, no one can approve
        if ($this->approver_id === null) {
            return false;
        }

        // Only the designated approver can approve - no exceptions
        return $this->approver_id === $user->id;
    }

    /**
     * Approve this model.
     *
     * @throws \InvalidArgumentException If the user is not authorized to approve
     */
    public function approve(User $user, string $signature, ?string $notes = null): Approval
    {
        if (! $this->canBeApprovedBy($user)) {
            throw new \InvalidArgumentException('User is not authorized to approve this record.');
        }

        return $this->approvals()->create([
            'approver_id' => $user->id,
            'approver_name' => $user->name,
            'approver_email' => $user->email,
            'signature' => $signature,
            'notes' => $notes,
            'approved_at' => now(),
        ]);
    }

    /**
     * Get the approval status display.
     */
    public function getApprovalStatusAttribute(): string
    {
        if ($this->isApproved()) {
            $approval = $this->latestApproval;

            return "Approved by {$approval->approver_name} on {$approval->approved_at->format('M j, Y')}";
        }

        if ($this->approver_id) {
            return "Pending approval from {$this->approver->name}";
        }

        return 'No approver assigned';
    }
}
