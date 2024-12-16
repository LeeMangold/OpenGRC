<?php

namespace App\Policies;

use App\Models\DataRequest;
use App\Models\DataRequestResponse;
use App\Models\User;
use Illuminate\Support\Str;

class DataRequestPolicy
{
    protected string $model = DataRequest::class;

    //    public function viewAny(User $user, DataRequestResponse $dataRequestResponse): bool
    //    {
    //        return $user->can('List '.Str::plural(class_basename($this->model)));
    //    }

    public function view(User $user): bool
    {
        return $user->can('Read '.Str::plural(class_basename($this->model)));
    }

    public function create(User $user): bool
    {
        return $user->can('Create '.Str::plural(class_basename($this->model)));
    }

    public function update(User $user, DataRequest $dataRequest): bool
    {
        return $dataRequest->requestee_id === $user->id;
    }

    public function delete(User $user): bool
    {
        return $user->can('Delete '.Str::plural(class_basename($this->model)));
    }
}
