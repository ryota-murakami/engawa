<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Greeting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GreetingController extends Controller
{
    /** Render /learn; Laravel injects the service registered by its class name. */
    public function __invoke(Request $request, Greeting $greeting): Response
    {
        return Inertia::render('course/hello', [
            'message' => $greeting->forMember($request->user()),
            'memberCount' => User::query()->count(),
        ]);
    }
}
