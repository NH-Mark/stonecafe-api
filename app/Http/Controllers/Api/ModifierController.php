<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modifier\StoreModifierRequest;
use App\Http\Requests\Modifier\UpdateModifierRequest;
use App\Http\Resources\ModifierResource;
use App\Models\Modifier;
use App\Services\ModifierService;

class ModifierController extends Controller
{
    public function __construct(
        protected ModifierService $service
    ) {}

    public function index()
    {
        return ModifierResource::collection(
            Modifier::with('group')->latest()->get()
        );
    }

    public function store(StoreModifierRequest $request)
    {
        return new ModifierResource(
            $this->service->create(
                $request->validated()
            )
        );
    }

    public function update(
        UpdateModifierRequest $request,
        Modifier $modifier
    ) {
        return new ModifierResource(
            $this->service->update(
                $modifier,
                $request->validated()
            )
        );
    }

    public function destroy(
        Modifier $modifier
    ) {
        $modifier->delete();

        return response()->json([
            'message' => 'Modifier deleted.'
        ]);
    }
}