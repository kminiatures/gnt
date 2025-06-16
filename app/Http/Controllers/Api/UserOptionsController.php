<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserOptionsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'user_options' => $user->user_options ?? []
        ]);
    }
    
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'user_options' => 'required|array'
        ]);
        
        $user->update([
            'user_options' => $request->user_options
        ]);
        
        return response()->json([
            'message' => 'User options updated successfully',
            'user_options' => $user->user_options
        ]);
    }
}
