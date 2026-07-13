<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

class HealthController extends Controller
{
    public function check()
    {
        $checks = [
            'database' => $this->isOk(fn () => DB::connection()->getPdo()),
            'queue' => $this->isOk(fn () => Queue::size()),
        ];

        $up = ! in_array('down', $checks, true);

        return response()->json([
            'data' => [
                'status' => $up ? 'up' : 'down',
                'checks' => $checks,
            ],
        ], $up ? 200 : 503);
    }

    // Each dependency is probed independently: one failing (e.g. the database) must not stop
    // the other (e.g. the queue) from being evaluated and reported in the same response.
    private function isOk(callable $probe): string
    {
        try {
            $probe();

            return 'ok';
        } catch (Throwable) {
            return 'down';
        }
    }
}
