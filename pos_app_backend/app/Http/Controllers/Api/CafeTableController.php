<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CafeTable;
use App\Http\Resources\CafeTableResource;
use Illuminate\Http\Request;

class CafeTableController extends Controller
{
    public function index()
    {
        return CafeTableResource::collection(CafeTable::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'number'   => 'required|string|unique:cafe_tables,number',
            'capacity' => 'required|integer|min:1',
        ]);

        $table = CafeTable::create($data);
        return new CafeTableResource($table);
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate(['status' => 'required|in:available,occupied,cleaning,reserved']);
        $table = CafeTable::findOrFail($id);
        $table->update(['status' => $data['status']]);
        
        return new CafeTableResource($table);
    }

    public function destroy(int $id)
    {
        CafeTable::destroy($id);
        return response()->json(null, 204);
    }
}
