<?php

namespace App\Http\Controllers\Producer;

use App\Http\Controllers\Controller;
use App\Models\Producer;
use Illuminate\Http\Request;

/** Shared access checks for producer portal endpoints (SOW: producers only see their own data). */
abstract class ProducerController extends Controller
{
    protected function producer(Request $request): Producer
    {
        $producer = $request->user()->producer;
        abort_unless($producer, 403, 'This area is for COSPARK producers.');

        return $producer;
    }

    /** Leads, earnings and benefits unlock once the producer is approved. */
    protected function activeProducer(Request $request): Producer
    {
        $producer = $this->producer($request);
        abort_unless($producer->isActive(), 403, 'Available once your producer application is approved.');

        return $producer;
    }
}
