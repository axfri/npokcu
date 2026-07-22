<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('account');
        }

        return view('account.change-password');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $newPassword = $request->validated('password');
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($newPassword),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        Auth::guard('web')->logoutOtherDevices($newPassword);
        $this->deleteOtherDatabaseSessions($request, $user->getKey());

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('account')
            ->with('success', 'Новый пароль сохранён.');
    }

    private function deleteOtherDatabaseSessions(Request $request, int|string $userId): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table((string) config('session.table', 'sessions'))
            ->where('user_id', $userId)
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}
