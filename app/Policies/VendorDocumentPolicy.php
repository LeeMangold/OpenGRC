<?php

namespace App\Policies;

use App\Models\VendorDocument;
use App\Models\VendorUser;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * VendorDocument permissions mirror Survey permissions.
 * Users who can manage Surveys can also manage Vendor Documents.
 */
class VendorDocumentPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        // VendorUsers can view their own vendor's documents
        if ($user instanceof VendorUser) {
            return true;
        }

        return $user->can('List Surveys');
    }

    public function view(Authenticatable $user, VendorDocument $vendorDocument): bool
    {
        // VendorUsers can view documents belonging to their vendor
        if ($user instanceof VendorUser) {
            return $vendorDocument->vendor_id === $user->vendor_id;
        }

        return $user->can('Read Surveys');
    }

    public function create(Authenticatable $user): bool
    {
        // VendorUsers can upload documents
        if ($user instanceof VendorUser) {
            return true;
        }

        return $user->can('Create Surveys');
    }

    public function update(Authenticatable $user, VendorDocument $vendorDocument): bool
    {
        // VendorUsers can update their own vendor's documents
        if ($user instanceof VendorUser) {
            return $vendorDocument->vendor_id === $user->vendor_id;
        }

        return $user->can('Update Surveys');
    }

    public function delete(Authenticatable $user, VendorDocument $vendorDocument): bool
    {
        // VendorUsers cannot delete documents
        if ($user instanceof VendorUser) {
            return false;
        }

        return $user->can('Delete Surveys');
    }

    public function restore(Authenticatable $user, VendorDocument $vendorDocument): bool
    {
        // VendorUsers cannot restore documents
        if ($user instanceof VendorUser) {
            return false;
        }

        return $user->can('Delete Surveys');
    }

    public function forceDelete(Authenticatable $user, VendorDocument $vendorDocument): bool
    {
        // VendorUsers cannot force delete documents
        if ($user instanceof VendorUser) {
            return false;
        }

        return $user->can('Delete Surveys');
    }
}
