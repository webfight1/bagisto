<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Customer\Models\Customer;

/**
 * Overrides vendor Webkul\RestApi AuthController@get/update so we can persist
 * company_name / company_reg / vat_id on the customer row (in addition to the
 * standard first_name / last_name / phone / password fields). Frontend
 * (Lovable) displays a "Ma esindan ettevõtet" toggle in the profile — when
 * checked, company inputs are shown and stored here.
 */
class CustomerProfileController
{
    /** GET /api/v1/customer/get — customer profile incl. company fields. */
    public function get(Request $request): JsonResponse
    {
        /** @var Customer|null $c */
        $c = $request->user();
        if (! $c) {
            return response()->json(['error' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }
        $c->refresh();

        return response()->json(['data' => $this->serialize($c)]);
    }

    /** PUT /api/v1/customer/profile — update profile + company fields. */
    public function update(Request $request): JsonResponse
    {
        /** @var Customer|null $c */
        $c = $request->user();
        if (! $c) {
            return response()->json(['error' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $data = $this->validate($request, $c->id);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Password change: verify old, hash new.
        if (! empty($data['new_password'])) {
            if (empty($data['current_password'])
                || ! Hash::check($data['current_password'], $c->password)) {
                return response()->json([
                    'errors' => ['current_password' => ['Praegune parool on vale.']],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $data['password'] = Hash::make($data['new_password']);
        }
        unset($data['new_password'], $data['current_password']);

        // Raw DB update: bypasses Customer::fillable (vendor model may not list
        // company_* fields even though the columns now exist).
        DB::table('customers')->where('id', $c->id)->update(array_merge($data, [
            'updated_at' => now(),
        ]));

        $c->refresh();

        return response()->json(['data' => $this->serialize($c)]);
    }

    private function validate(Request $request, int $customerId): array
    {
        return $request->validate([
            'first_name'       => ['sometimes', 'string', 'max:255'],
            'last_name'        => ['sometimes', 'string', 'max:255'],
            'email'            => ['sometimes', 'email', 'max:255', Rule::unique('customers')->ignore($customerId)],
            'phone'            => ['sometimes', 'nullable', 'string', 'max:50'],
            'gender'           => ['sometimes', 'nullable', 'in:Male,Female,Other'],
            'date_of_birth'    => ['sometimes', 'nullable', 'date'],
            'company_name'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'company_reg'      => ['sometimes', 'nullable', 'string', 'max:50'],
            'vat_id'           => ['sometimes', 'nullable', 'string', 'max:50'],
            'current_password' => ['sometimes', 'nullable', 'string'],
            'new_password'     => ['sometimes', 'nullable', 'string', 'min:6', 'confirmed'],
            'new_password_confirmation' => ['sometimes', 'nullable', 'string'],
        ]);
    }

    private function serialize(Customer $c): array
    {
        // Refresh through raw query to pick up company_* columns not in Customer::fillable.
        $row = (array) DB::table('customers')->where('id', $c->id)->first();

        return [
            'id'                => $row['id'] ?? $c->id,
            'first_name'        => $row['first_name'] ?? null,
            'last_name'         => $row['last_name'] ?? null,
            'name'              => trim(($row['first_name'] ?? '').' '.($row['last_name'] ?? '')),
            'email'             => $row['email'] ?? null,
            'phone'             => $row['phone'] ?? null,
            'gender'            => $row['gender'] ?? null,
            'date_of_birth'     => $row['date_of_birth'] ?? null,
            'company_name'      => $row['company_name'] ?? null,
            'company_reg'       => $row['company_reg'] ?? null,
            'vat_id'            => $row['vat_id'] ?? null,
            'customer_group_id' => $row['customer_group_id'] ?? null,
            'status'            => (bool) ($row['status'] ?? false),
            'group'             => optional($c->group)->only(['id', 'code', 'name']),
        ];
    }
}
