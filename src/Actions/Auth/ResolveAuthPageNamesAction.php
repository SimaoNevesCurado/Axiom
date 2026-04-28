<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use SimaoCurado\Axiom\Enums\FrontendStack;

final readonly class ResolveAuthPageNamesAction
{
    /**
     * @return array<string, string>
     */
    public function handle(FrontendStack $frontendStack): array
    {
        if ($frontendStack === FrontendStack::React) {
            return [
                'appearance' => 'appearance/update',
                'sessionCreate' => 'session/create',
                'userCreate' => 'user/create',
                'userEmailResetNotificationCreate' => 'user-email-reset-notification/create',
                'userEmailVerificationNotificationCreate' => 'user-email-verification-notification/create',
                'userPasswordCreate' => 'user-password/create',
                'userPasswordEdit' => 'user-password/edit',
                'userProfileEdit' => 'user-profile/edit',
                'userTwoFactorAuthenticationShow' => 'user-two-factor-authentication/show',
                'userTwoFactorAuthenticationChallengeShow' => 'user-two-factor-authentication-challenge/show',
                'userPasswordConfirmationCreate' => 'user-password-confirmation/create',
            ];
        }

        return [
            'appearance' => 'appearance/Update',
            'sessionCreate' => 'session/Create',
            'userCreate' => 'user/Create',
            'userEmailResetNotificationCreate' => 'user-email-reset-notification/Create',
            'userEmailVerificationNotificationCreate' => 'user-email-verification-notification/Create',
            'userPasswordCreate' => 'user-password/Create',
            'userPasswordEdit' => 'user-password/Edit',
            'userProfileEdit' => 'user-profile/Edit',
            'userTwoFactorAuthenticationShow' => 'user-two-factor-authentication/Show',
            'userTwoFactorAuthenticationChallengeShow' => 'user-two-factor-authentication-challenge/Show',
            'userPasswordConfirmationCreate' => 'user-password-confirmation/Create',
        ];
    }
}
