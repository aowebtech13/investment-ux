<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $user->update([
            'onboarding_description' => $request->input('description'),
            'interest_area' => $request->input('interest_area'),
            'onboarding_completed' => true,
        ]);
        return response()->json(['message' => 'Onboarding saved', 'user' => $user]);
    }
}
