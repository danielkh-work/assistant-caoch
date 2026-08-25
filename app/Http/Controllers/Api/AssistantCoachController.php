<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\User;
use Illuminate\Http\Request;

class AssistantCoachController extends Controller
{
    private const ASSISTANT_ROLES = ['assistant_coach', 'performance_coach'];

    public function index(Request $request): BaseResponse
    {
        $headCoach = $this->assertHeadCoach();

        $query = User::query()
            ->where('head_coach_id', $headCoach->id)
            ->whereIn('role', self::ASSISTANT_ROLES)
            ->orderBy('name');

        if ($request->has('page') || $request->has('per_page')) {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = max(1, min(500, (int) $request->input('per_page', 20)));

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            return new BaseResponse(
                STATUS_CODE_OK,
                STATUS_CODE_OK,
                'assistant coach list',
                $paginator->items(),
                null,
                null,
                [
                    'total' => $paginator->total(),
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'last_page' => $paginator->lastPage(),
                ],
            );
        }

        $coaches = $query->get();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'assistant coach list',
            $coaches
        );
    }

    public function store(Request $request): BaseResponse
    {
        $headCoach = $this->assertHeadCoach();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:assistant_coach,performance_coach'],
        ]);

        $assistant = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'head_coach_id' => $headCoach->id,
            'sport_id' => $headCoach->sport_id,
            'is_subscribe' => $headCoach->is_subscribe,
            'subscription_id' => $headCoach->subscription_id,
            'status' => 'approved',
        ]);

        $headCoachRoles = $headCoach->roles->pluck('name');
        $assistant->assignRole($headCoachRoles);

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Add Assistant Coach Successfully',
            $assistant->fresh()
        );
    }

    public function show(Request $request, int $id): BaseResponse
    {
        $headCoach = $this->assertHeadCoach();
        $assistant = $this->findOwnedAssistant($headCoach, $id);

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Assistant coach retrieved successfully',
            $assistant
        );
    }

    public function update(Request $request, int $id): BaseResponse
    {
        $headCoach = $this->assertHeadCoach();
        $assistant = $this->findOwnedAssistant($headCoach, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$assistant->id],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'role' => ['sometimes', 'in:assistant_coach,performance_coach'],
        ]);

        if (array_key_exists('name', $validated)) {
            $assistant->name = $validated['name'];
        }

        if (array_key_exists('email', $validated)) {
            $assistant->email = $validated['email'];
        }

        if (array_key_exists('role', $validated)) {
            $assistant->role = $validated['role'];
        }

        if (! empty($validated['password'] ?? null)) {
            $assistant->password = $validated['password'];
        }

        $assistant->save();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Assistant coach updated successfully',
            $assistant->fresh()
        );
    }

    public function updateStatus(Request $request, int $id): BaseResponse
    {
        $headCoach = $this->assertHeadCoach();
        $assistant = $this->findOwnedAssistant($headCoach, $id);

        $validated = $request->validate([
            'status' => ['required', 'in:approved,inactive'],
        ]);

        $assistant->status = $validated['status'];
        $assistant->save();

        if ($validated['status'] === 'inactive') {
            $assistant->tokens()->delete();
        }

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Assistant coach status updated successfully',
            $assistant->fresh()
        );
    }

    public function destroy(Request $request, int $id): BaseResponse
    {
        $headCoach = $this->assertHeadCoach();
        $assistant = $this->findOwnedAssistant($headCoach, $id);

        $assistant->tokens()->delete();
        $assistant->mangleEmailForDelete();
        $assistant->delete();

        return new BaseResponse(
            STATUS_CODE_OK,
            STATUS_CODE_OK,
            'Assistant coach deleted successfully'
        );
    }

    private function assertHeadCoach(): User
    {
        /** @var User $headCoach */
        $headCoach = auth()->user();

        if ($headCoach->role !== 'head_coach') {
            abort(403);
        }

        return $headCoach;
    }

    private function findOwnedAssistant(User $headCoach, int $id): User
    {
        return User::query()
            ->whereKey($id)
            ->where('head_coach_id', $headCoach->id)
            ->whereIn('role', self::ASSISTANT_ROLES)
            ->firstOrFail();
    }
}
