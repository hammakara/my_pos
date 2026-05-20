<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CafeTableResource;
use App\Services\CafeTableService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CafeTableController extends Controller
{
    protected $cafeTableService;

    public function __construct(CafeTableService $cafeTableService)
    {
        $this->cafeTableService = $cafeTableService;
    }

    public function index()
    {
        return CafeTableResource::collection($this->cafeTableService->getAllTables());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'number'   => [
                'required',
                'string',
                Rule::unique('cafe_tables', 'number')->where('floor', $request->floor ?? 1),
            ],
            'capacity' => 'required|integer|min:1',
            'floor'    => 'sometimes|required|integer|min:1',
        ]);

        $table = $this->cafeTableService->createTable($data);
        return new CafeTableResource($table);
    }

    public function show(int $id)
    {
        return new CafeTableResource($this->cafeTableService->getTableById($id));
    }

    public function update(Request $request, int $id)
    {
        // Find existing table to get its current floor if not provided in the request
        $existingTable = \App\Models\CafeTable::findOrFail($id);
        $floor = $request->floor ?? $existingTable->floor ?? 1;

        $data = $request->validate([
            'number'   => [
                'sometimes',
                'required',
                'string',
                Rule::unique('cafe_tables', 'number')
                    ->ignore($id)
                    ->where('floor', $floor),
            ],
            'capacity' => 'sometimes|required|integer|min:1',
            'floor'    => 'sometimes|required|integer|min:1',
        ]);

        $table = $this->cafeTableService->updateTable($id, $data);
        return new CafeTableResource($table);
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate(['status' => 'required|in:available,occupied,cleaning,reserved']);
        $table = $this->cafeTableService->updateStatus($id, $data['status']);
        
        return new CafeTableResource($table);
    }

    public function destroy(int $id)
    {
        $this->cafeTableService->deleteTable($id);
        return response()->json(null, 204);
    }
}

