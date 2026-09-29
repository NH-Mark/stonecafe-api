<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SapaadImportUploadRequest;
use App\Models\HistoricalImport;
use App\Services\Imports\Sapaad\SapaadImportService;
use Illuminate\Http\JsonResponse;

class SapaadImportController extends Controller
{
    public function store(
        SapaadImportUploadRequest $request,
        SapaadImportService $service
    ): JsonResponse {
        $import = $service->createImport(
            ordersFile: $request->file('orders_file'),
            itemsFile: $request->file('items_file'),
            locationId: $request->input('location_id')
        );

        return response()->json([
            'success' => true,
            'message' => 'Sapaad files uploaded and validated.',
            'data' => [
                'import_id' => $import->id,
                'status' => $import->status,
                'total_orders' => $import->total_orders,
                'valid_orders' => $import->valid_orders,
                'invalid_orders' => $import->invalid_orders,
            ],
        ], 201);
    }

    public function validation(
        HistoricalImport $historicalImport
    ): JsonResponse {
        $historicalImport->load('rows');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $historicalImport->id,
                'source' => $historicalImport->source,
                'status' => $historicalImport->status,

                'summary' => [
                    'total_orders' => $historicalImport->total_orders,
                    'valid_orders' => $historicalImport->valid_orders,
                    'invalid_orders' => $historicalImport->invalid_orders,
                    'imported_orders' => $historicalImport->imported_orders,
                ],

                'rows' => $historicalImport->rows,
            ],
        ]);
    }

    public function execute(
        HistoricalImport $historicalImport,
        SapaadImportService $service
    ): JsonResponse {
        $result = $service->executeImport(
            $historicalImport
        );

        return response()->json([
            'success' => true,
            'message' => 'Sapaad orders imported successfully.',
            'data' => $result,
        ]);
    }
}