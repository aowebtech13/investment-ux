<?php


if (function_exists('opcache_reset')) {
    $reset = opcache_reset();
} else {
    $reset = null;
}

header('Content-Type: application/json');
echo json_encode([
    'opcache_available' => $reset !== null,
    'opcache_reset'     => $reset,
    'message'           => 'OPcache reset request processed. Refresh your login now.',
]);
