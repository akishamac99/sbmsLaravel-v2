<?php

// Salon details shown to customers. Change them in .env instead of editing code:
// SALON_GCASH_NUMBER, SALON_GCASH_NAME, SALON_BANK_NAME, SALON_BANK_ACCOUNT_NAME, SALON_BANK_ACCOUNT_NUMBER.
// Bank transfer is offered at checkout only when a bank account number is set.
return [
    'gcash_number' => env('SALON_GCASH_NUMBER', '0917-123-4567'),
    'gcash_name' => env('SALON_GCASH_NAME', 'Janet Arkias'),
    'bank_name' => env('SALON_BANK_NAME'),
    'bank_account_name' => env('SALON_BANK_ACCOUNT_NAME'),
    'bank_account_number' => env('SALON_BANK_ACCOUNT_NUMBER'),
];
