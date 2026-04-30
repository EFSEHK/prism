<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NavigationRequest;
use App\Repositories\Navigation\NavigationRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NavigationController extends Controller
{
    protected $navigationRepository;

    public function __construct(NavigationRepositoryInterface $navigationRepository)
    {
        $this->navigationRepository = $navigationRepository;
    }

    public function index(): JsonResponse
    {
        $navigations = $this->navigationRepository->all();
        return response()->json($navigations);
    }

    public function store(NavigationRequest $request): JsonResponse
    {
        try {
            $navigation = $this->navigationRepository->create($request->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'Navigation created successfully',
                'data' => $navigation
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create navigation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        $navigation = $this->navigationRepository->find($id);
        return response()->json($navigation);
    }

    public function update(NavigationRequest $request, $id): JsonResponse
    {
        try {
            $navigation = $this->navigationRepository->update($id, $request->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'Navigation updated successfully',
                'data' => $navigation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update navigation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $this->navigationRepository->delete($id);
        return response()->json(['message' => 'Navigation deleted successfully'], 200);
    }

    public function active(): JsonResponse
    {
        $navigations = $this->navigationRepository->getActiveNavigation();
        return response()->json($navigations);
    }
} 