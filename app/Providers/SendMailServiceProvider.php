<?php

namespace App\Providers;

use App\Mail\ActivationMail;
use App\Mail\PasswordResetMail;
use App\Models\ActivationToken;
use App\Models\PasswordResetToken;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class SendMailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        function sendActivationEmail(string $to, string $id): void
        {
            ActivationToken::where('user_id', $id)
                ?->delete();

            $newToken = md5($to . "facebook");

            ActivationToken::create([
                'token' => $newToken,
                'user_id' => $id,
            ]);

            $url = env("ACTIVATION_URL") . "?token=$newToken";

            Mail::to($to)->queue(new ActivationMail($url));
        }

        function sendPasswordResetEmail(string $to, string $id): void
        {
            $token = md5($to . "facebook");

            PasswordResetToken::create([
                'token' => $token,
                'user_id' => $id,
            ]);

            $url = env("PASSWORD_RESET_URL") . "?token=$token";

            Mail::to($to)->queue(new PasswordResetMail($url));
        }
    }
}
