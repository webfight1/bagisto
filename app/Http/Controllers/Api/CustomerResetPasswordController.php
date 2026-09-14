<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use OpenApi\Attributes as OA;
use Webkul\Customer\Models\Customer;

/**
 * Public reset-password API. Bagisto's REST package only exposes
 * forgot-password (sends email); the actual reset POST is hidden behind a
 * web form route. This wires up a JSON-friendly equivalent so the
 * SPA / Lovable frontend can finish the flow.
 */
class CustomerResetPasswordController extends Controller
{
    #[OA\Post(
        path: '/api/v1/customer/reset-password',
        operationId: 'customerResetPassword',
        summary: 'Complete password reset via email-token link',
        description: 'Posts the token from the email reset link, customer email, and new password. Bagisto vendor REST package only exposes /forgot-password (sends mail); this completes the flow.',
        tags: ['Aiamaailm Customer'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email', 'token', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'email',                 type: 'string', format: 'email'),
                new OA\Property(property: 'token',                 type: 'string', description: 'From /parool-uuesti/{token} URL in email'),
                new OA\Property(property: 'password',              type: 'string', minLength: 6),
                new OA\Property(property: 'password_confirmation', type: 'string'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Password reset successful'),
            new OA\Response(response: 422, description: 'Validation error or invalid/expired token'),
        ]
    )]
    public function reset(Request $request)
    {
        $data = $request->validate([
            'email'                 => ['required', 'email'],
            'token'                 => ['required', 'string'],
            'password'              => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $status = Password::broker('customers')->reset(
            [
                'email'                 => $data['email'],
                'token'                 => $data['token'],
                'password'              => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
            ],
            function (Customer $customer, string $password) {
                $customer->password = Hash::make($password);
                $customer->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __('shop::app.customers.signup-form.success-password-reset'),
            ]);
        }

        return response()->json([
            'message' => __($status),
            'errors'  => ['email' => [__($status)]],
        ], 422);
    }
}
