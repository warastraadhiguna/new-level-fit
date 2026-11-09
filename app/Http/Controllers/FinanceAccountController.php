<?php
namespace App\Http\Controllers;
class FinanceAccountController extends ScopedRoleAccountController
{
    protected function accountRole(): string { return 'FINANCE'; }
    protected function routePrefix(): string { return 'finance'; }
}
