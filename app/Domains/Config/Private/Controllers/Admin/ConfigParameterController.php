<?php

namespace App\Domains\Config\Private\Controllers\Admin;

use App\Domains\Config\Public\Services\ConfigParameterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

class ConfigParameterController extends Controller
{
    public function __construct(
        private ConfigParameterService $parameterService,
    ) {}

    public function index()
    {
        $parameters = $this->parameterService->listParametersWithValues();
        $grouped = collect($parameters)->groupBy(fn ($p) => $p['definition']->domain);

        return view('config::pages.admin.parameters.index', compact('grouped'));
    }

    public function update(Request $request, string $domain, string $key): JsonResponse
    {
        $definition = $this->parameterService->getDefinition($key, $domain);

        if (!$definition) {
            return response()->json(['success' => false, 'message' => 'Parameter not found'], 404);
        }

        try {
            // Parse the value according to type
            $rawValue = $request->input('value');
            $value = match ($definition->type->value) {
                'bool' => filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
                'int', 'time' => (int) $rawValue,
                default => $rawValue,
            };

            $this->parameterService->setParameterValue($key, $domain, $value);

            return response()->json([
                'success' => true,
                'message' => __('config::admin.parameters.saved'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function reset(string $domain, string $key): JsonResponse
    {
        $definition = $this->parameterService->getDefinition($key, $domain);

        if (!$definition) {
            return response()->json(['success' => false, 'message' => 'Parameter not found'], 404);
        }

        $this->parameterService->resetParameterToDefault($key, $domain);

        return response()->json([
            'success' => true,
            'message' => __('config::admin.parameters.reset_success'),
            'defaultValue' => $definition->default,
        ]);
    }
}
