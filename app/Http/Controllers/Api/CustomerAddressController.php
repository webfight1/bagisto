<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\CustomerAddressResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Webkul\RestApi\Http\Controllers\V1\Shop\Customer\AddressController as VendorAddressController;
use Webkul\Shop\Http\Requests\Customer\AddressRequest;

class CustomerAddressController extends VendorAddressController
{
    public function resource(): string
    {
        return CustomerAddressResource::class;
    }

    public function store(AddressRequest $request): Response
    {
        $customer = $this->resolveShopUser($request);

        Event::dispatch('customer.addresses.create.before');

        $data = array_merge($request->only([
            'company_name',
            'company_reg',
            'first_name',
            'last_name',
            'vat_id',
            'address',
            'country',
            'state',
            'city',
            'postcode',
            'phone',
            'email',
            'default_address',
        ]), [
            'customer_id' => $customer->id,
            'address'     => implode(PHP_EOL, array_filter($request->input('address'))),
        ]);

        if (! empty($data['default_address'])) {
            $this->getRepositoryInstance()->where('customer_id', $data['customer_id'])
                ->where('default_address', 1)
                ->update(['default_address' => 0]);
        }

        $customerAddress = $this->getRepositoryInstance()->create($data);

        Event::dispatch('customer.addresses.create.after', $customerAddress);

        return response([
            'data'    => new CustomerAddressResource($customerAddress),
            'message' => trans('rest-api::app.shop.customer.addresses.create-success'),
        ]);
    }

    public function update(AddressRequest $request, int $id): Response
    {
        $customer = $this->resolveShopUser($request);

        Event::dispatch('customer.addresses.update.before', $id);

        $customerAddress = $customer->addresses()->findOrFail($id);

        $customerAddress->update(array_merge(request()->only([
            'customer_id',
            'company_name',
            'company_reg',
            'first_name',
            'last_name',
            'vat_id',
            'address',
            'country',
            'state',
            'city',
            'postcode',
            'phone',
            'email',
            'default_address',
        ]), [
            'address' => implode(PHP_EOL, array_filter($request->input('address'))),
        ]));

        Event::dispatch('customer.addresses.update.after', $customerAddress);

        return response([
            'data'    => new CustomerAddressResource($customerAddress),
            'message' => trans('rest-api::app.shop.customer.addresses.update-success'),
        ]);
    }
}
