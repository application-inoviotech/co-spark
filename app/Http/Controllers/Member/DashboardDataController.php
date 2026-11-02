<?php

namespace App\Http\Controllers\Member;

use App\Services\MemberDashboardPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardDataController extends MemberController
{
    public function __invoke(Request $request, MemberDashboardPresenter $presenter): JsonResponse
    {
        return response()->json($presenter->present($request->user()));
    }
}
