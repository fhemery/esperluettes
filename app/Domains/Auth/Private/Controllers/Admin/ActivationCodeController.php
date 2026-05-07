<?php

namespace App\Domains\Auth\Private\Controllers\Admin;

use App\Domains\Auth\Private\Models\ActivationCode;
use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Private\Requests\Admin\ActivationCodeRequest;
use App\Domains\Auth\Private\Services\ActivationCodeService;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ActivationCodeController extends Controller
{
    public function __construct(
        private readonly ActivationCodeService $service,
        private readonly ProfilePublicApi $profileApi,
    ) {}

    public function index(Request $request): View
    {
        $query = ActivationCode::query()->orderBy('created_at', 'desc');

        if ($code = $request->input('code')) {
            $query->where('code', 'like', '%' . $code . '%');
        }

        $status = $request->input('status');
        match ($status) {
            'active' => $query->whereNull('used_at')->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            }),
            'used' => $query->whereNotNull('used_at'),
            'expired' => $query->whereNull('used_at')->whereNotNull('expires_at')->where('expires_at', '<=', now()),
            default => null,
        };

        $codes = $query->paginate(20)->withQueryString();

        $userIds = collect($codes->items())
            ->flatMap(fn ($c) => array_filter([$c->sponsor_user_id, $c->used_by_user_id]))
            ->unique()
            ->values()
            ->toArray();

        $profiles = [];
        if (!empty($userIds)) {
            foreach ($this->profileApi->getPublicProfiles($userIds) as $userId => $profile) {
                $profiles[$userId] = $profile->display_name;
            }
        }

        $filters = ['code' => $request->input('code', ''), 'status' => $status ?? ''];

        return view('auth::pages.admin.activation-codes.index', compact('codes', 'profiles', 'filters'));
    }

    public function create(): View
    {
        return view('auth::pages.admin.activation-codes.create');
    }

    public function store(ActivationCodeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $sponsorUser = !empty($data['sponsor_user_id'])
            ? User::findOrFail($data['sponsor_user_id'])
            : null;

        $this->service->generateCode(
            sponsorUser: $sponsorUser,
            comment: $data['comment'] ?? null,
            expiresAt: !empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
        );

        return redirect()
            ->route('auth.admin.activation-codes.index')
            ->with('success', __('auth::admin.activation_codes.created'));
    }

    public function destroy(ActivationCode $activationCode): RedirectResponse
    {
        if ($activationCode->isUsed()) {
            return back()->with('error', __('auth::admin.activation_codes.delete_blocked'));
        }

        $this->service->deleteCode($activationCode);

        return redirect()
            ->route('auth.admin.activation-codes.index')
            ->with('success', __('auth::admin.activation_codes.deleted'));
    }
}
