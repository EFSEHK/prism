<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Services\ModuleCatalogService;
use App\Support\AuthActor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleAppsController extends Controller
{
    public function __construct(
        private readonly ModuleCatalogService $catalog,
    ) {}

    public function index(): JsonResponse
    {
        abort_unless(AuthActor::canManageApps(), 403);

        return response()->json([
            'data' => $this->catalog->adminCatalog(),
            'roles' => $this->catalog->assignableRoles(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        abort_unless(AuthActor::canManageApps(), 403);

        $validated = $request->validate([
            'modules' => 'required|array',
            'modules.*.id' => 'required|string|max:64',
            'modules.*.status' => 'required|string|in:live,coming_soon,disabled',
            'modules.*.visible_roles' => 'nullable|array',
            'modules.*.visible_roles.*' => 'string|max:64',
        ]);

        $data = $this->catalog->syncSettings($validated['modules']);

        return response()->json([
            'message' => 'App visibility saved.',
            'data' => $data,
            'roles' => $this->catalog->assignableRoles(),
        ]);
    }
}
