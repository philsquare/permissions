<?php

namespace App\Policies\Billing;

use App\Models\Billing\Invoice;
use App\Models\User;
use Philsquare\Permissions\BasePolicy;

class InvoicePolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return ['accountant' => $this->permissions()->all()];
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return true;
    }
}
