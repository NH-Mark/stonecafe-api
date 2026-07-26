<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModifierGroup\StoreModifierGroupRequest;
use App\Http\Requests\ModifierGroup\UpdateModifierGroupRequest;
use App\Http\Resources\ModifierGroupResource;
use App\Models\ModifierGroup;
use App\Services\ModifierGroupService;

class ModifierGroupController extends Controller
{
    public function __construct(
        protected ModifierGroupService $service
    ) {}

    public function index()
    {
        $groups = ModifierGroup::withCount('modifiers')
            ->latest()
            ->get();

        return ModifierGroupResource::collection($groups);
    }

    public function store(StoreModifierGroupRequest $request)
    {
        return new ModifierGroupResource(
            $this->service->create($request->validated())
        );
    }

    public function show(ModifierGroup $modifierGroup)
    {
        return new ModifierGroupResource(
            $modifierGroup->load('modifiers')
        );
    }

    public function update(
        UpdateModifierGroupRequest $request,
        ModifierGroup $modifierGroup
    ) {
        return new ModifierGroupResource(
            $this->service->update(
                $modifierGroup,
                $request->validated()
            )
        );
    }

    public function destroy(
        ModifierGroup $modifierGroup
    ) {
        $modifierGroup->delete();

        return response()->json([
            'message' => 'Modifier group deleted.'
        ]);
    }
}