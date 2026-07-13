<?php

// Strangler fig routing table. An ordered list of rules, path prefix => backend
// base URL: the first prefix that matches the request path wins. Anything that
// matches no rule falls through to the default backend.

return [
    'rules' => [
        // Enrollment management for course 3 has moved to EventHub (Chapter 11's case
        // study): this is the first rule this table has ever needed, added the same way
        // every future one will be, without touching the routing logic itself.
        '/api/legacy-enrollments' => 'http://localhost:8000',
    ],
    'default' => 'http://localhost:8001',
];
