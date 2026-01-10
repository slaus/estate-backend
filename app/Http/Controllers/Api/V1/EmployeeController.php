<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EmployeeResource;
use App\Http\Resources\Api\V1\Public\EmployeePublicResource;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::query()
            ->when($request->user(), function ($query) {
                return $query;
            }, function ($query) {
                return $query->published();
            })
            ->orderBy('order')
            ->paginate($request->get('per_page', 15));

        return EmployeeResource::collection($employees);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|array',
            'name.uk' => 'required|string',
            'position' => 'required|string|max:255',
            'description' => 'nullable|array',
            'details' => 'nullable|array',
            'image' => 'nullable|string|max:255',
            'order' => 'integer',
            'visibility' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $employee = Employee::create($request->all());

        return new EmployeeResource($employee);
    }

    public function show(Request $request, Employee $employee)
    {
        if (!$request->user() && !$employee->visibility) {
            return response()->json([
                'message' => 'Співробітник не знайдений'
            ], 404);
        }

        return new EmployeeResource($employee);
    }

    public function update(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|array',
            'name.uk' => 'sometimes|string',
            'position' => 'sometimes|string|max:255',
            'description' => 'nullable|array',
            'details' => 'nullable|array',
            'image' => 'nullable|string|max:255',
            'order' => 'sometimes|integer',
            'visibility' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $employee->update($request->all());

        return new EmployeeResource($employee);
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return response()->json(null, 204);
    }

    // Публичные методы
    public function indexPublic(Request $request)
    {
        $employees = Employee::published()
            ->orderBy('order')
            ->get();

        return EmployeePublicResource::collection($employees);
    }
}