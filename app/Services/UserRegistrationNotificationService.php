<?php

namespace App\Services;

use App\Mail\UserRegistrationPendingApprovalMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class UserRegistrationNotificationService
{
    public function notifyAdminsOfPendingApproval(User $user): void
    {
        $admins = User::query()
            ->where('site_role', 'admin')
            ->whereNotNull('approved_at')
            ->get()
            ->unique('email')
            ->values();

        if ($admins->isEmpty()) {
            return;
        }

        $adminUrl = route('admin.index', absolute: true);
        $registeredAt = optional($user->created_at)->format('M j, Y g:i a') ?? now()->format('M j, Y g:i a');

        foreach ($admins as $admin) {
            Mail::to($admin)->send(new UserRegistrationPendingApprovalMail(
                pendingUserName: $user->name,
                pendingUserEmail: $user->email,
                registeredAt: $registeredAt,
                adminUrl: $adminUrl,
            ));
        }
    }
}