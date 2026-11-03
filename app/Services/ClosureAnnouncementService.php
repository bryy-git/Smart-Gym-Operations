<?php

namespace App\Services;

use App\Models\GymClosure;
use Illuminate\Database\Eloquent\Builder;

class ClosureAnnouncementService
{
    public function relevant(): Builder
    {
        return GymClosure::query()
            ->where('end_datetime', '>', now())
            ->orderBy('start_datetime')
            ->orderBy('closure_id');
    }
}
