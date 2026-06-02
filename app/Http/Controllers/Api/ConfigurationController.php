<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfigurationController extends Controller
{
    public function index()
    {
        $configurations = Configuration::query()
            ->orderBy('key')
            ->get()
            ->map(fn (Configuration $configuration) => $configuration->toApiArray())
            ->values();

        return response()->json($configurations);
    }

    public function show(string $key)
    {
        $configuration = Configuration::query()->where('key', $key)->first();

        if (! $configuration) {
            return response()->json(null, 404);
        }

        return response()->json($configuration->toApiArray());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255', 'unique:configurations,key', Configuration::KEY_VALIDATION_RULE],
            'value' => ['required', Configuration::valueValidator()],
        ]);

        $configuration = Configuration::create([
            'key' => $validated['key'],
            'value' => Configuration::normalizeValue($validated['value']),
        ]);

        return response()->json($configuration->toApiArray(), 201);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'configurations' => 'required|array|min:1',
            'configurations.*.key' => ['required', 'string', 'max:255', Configuration::KEY_VALIDATION_RULE],
            'configurations.*.value' => ['required', Configuration::valueValidator()],
        ]);

        DB::beginTransaction();

        try {
            $updated = collect($validated['configurations'])
                ->map(fn (array $item) => Configuration::upsertByKey($item['key'], $item['value']))
                ->map(fn (Configuration $configuration) => $configuration->toApiArray())
                ->values();

            DB::commit();

            return response()->json($updated);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw ValidationException::withMessages([
                'configurations' => $th->getMessage(),
            ]);
        }
    }
}
