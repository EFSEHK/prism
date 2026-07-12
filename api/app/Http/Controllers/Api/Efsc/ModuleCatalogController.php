<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Services\ModuleCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleCatalogController extends Controller
{
    public function __construct(
        private readonly ModuleCatalogService $catalog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $platform = $request->query('platform');
        if (is_string($platform)) {
            $platform = strtolower(trim($platform));
            if (! in_array($platform, ['web', 'mobile'], true)) {
                $platform = null;
            }
        } else {
            $platform = null;
        }

        $modules = $this->catalog->forUser($request->user(), $platform);

        return response()->json([
            'data' => $modules,
        ]);
    }
}
