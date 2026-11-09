<?php
namespace App\Http\Controllers;
class HrdAccountController extends ScopedRoleAccountController
{
    protected function accountRole(): string { return 'HRD'; }
    protected function routePrefix(): string { return 'hrd'; }
}
