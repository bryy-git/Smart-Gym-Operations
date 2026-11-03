<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('welcome', [
            'plans' => MembershipPlan::query()
                ->where('plan_status', 'Active')
                ->orderBy('plan_name')
                ->get(),
        ]);
    }
}
