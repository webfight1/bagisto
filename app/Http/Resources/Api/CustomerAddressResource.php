<?php

namespace App\Http\Resources\Api;

use Webkul\RestApi\Http\Resources\V1\Shop\Customer\CustomerAddressResource as VendorCustomerAddressResource;

class CustomerAddressResource extends VendorCustomerAddressResource
{
    public function toArray($request)
    {
        return array_merge(parent::toArray($request), [
            'company_reg' => $this->company_reg,
        ]);
    }
}
