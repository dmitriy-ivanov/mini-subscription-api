<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    /**
     * Register a new customer.
     *
     * @param  array<string, string>  $data
     * @return Customer
     */
    public function register(array $data): Customer
    {
        return Customer::create([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }
}
