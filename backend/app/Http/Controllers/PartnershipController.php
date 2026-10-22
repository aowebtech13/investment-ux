<?php

namespace App\Http\Controllers;

use App\Models\Partnership;
use Illuminate\Http\JsonResponse;

class PartnershipController extends Controller
{
    public function index(): JsonResponse
    {
        $partnerships = Partnership::where('is_active', true)
            ->orderBy('amount', 'asc')
            ->get();

        return response()->json($partnerships);
    }

    public function show($id): JsonResponse
    {
        $partnership = Partnership::findOrFail($id);
        return response()->json($partnership);
    }
}
