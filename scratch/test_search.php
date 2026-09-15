<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Helper\Helper;
use App\Models\AppUser;

$search = '𝓥𝓲𝓴𝓪𝓼 𝓢𝓱𝓪𝓻𝓶𝓪';
$matchedUserIds = Helper::getMatchedUserIds($search, false);

echo "Matched User IDs for expired premium number '𝓥𝓲𝓴𝓪𝓼 𝓢𝓱𝓪𝓻𝓶𝓪':\n";
var_dump($matchedUserIds);

$users = AppUser::query()
    ->where(function ($q) use ($search, $matchedUserIds) {
        if (!empty($matchedUserIds)) {
            $q->whereIn('id', $matchedUserIds)
              ->orWhereRaw('BINARY name LIKE ?', ['%' . $search . '%']);
        } else {
            $q->whereRaw('BINARY name LIKE ?', ['%' . $search . '%']);
        }
    })
    ->pluck('name', 'id');

echo "Search results for '𝓥𝓲𝓴𝓪𝓼 𝓢𝓱𝓪𝓻𝓶𝓪':\n";
var_dump($users->toArray());

$searchPlain = 'Vikas';
$matchedUserIdsPlain = Helper::getMatchedUserIds($searchPlain, false);
$usersPlain = AppUser::query()
    ->where(function ($q) use ($searchPlain, $matchedUserIdsPlain) {
        if (!empty($matchedUserIdsPlain)) {
            $q->whereIn('id', $matchedUserIdsPlain)
              ->orWhere('name', 'LIKE', '%' . $searchPlain . '%');
        } else {
            $q->where('name', 'LIKE', '%' . $searchPlain . '%');
        }
    })
    ->pluck('name', 'id');

echo "Search results for plain text 'Vikas':\n";
var_dump($usersPlain->toArray());
