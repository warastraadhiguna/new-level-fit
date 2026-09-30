<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureOwnerForExcelDownloads;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureOwnerForExcelDownloadsTest extends TestCase
{
    public function test_owner_can_download_excel(): void
    {
        $response = app(EnsureOwnerForExcelDownloads::class)->handle(
            $this->requestFor('OWNER', 'member-active.index', ['excel' => '1']),
            fn () => response('allowed')
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_non_owner_cannot_download_excel_using_query_parameter(): void
    {
        $this->expectException(HttpException::class);

        app(EnsureOwnerForExcelDownloads::class)->handle(
            $this->requestFor('ADMIN', 'member-active.index', ['excel' => '1']),
            fn () => response('not allowed')
        );
    }

    public function test_non_owner_cannot_open_dedicated_excel_route(): void
    {
        $this->expectException(HttpException::class);

        app(EnsureOwnerForExcelDownloads::class)->handle(
            $this->requestFor('CS', 'memberRegistrationExcel'),
            fn () => response('not allowed')
        );
    }

    public function test_non_excel_request_is_not_restricted(): void
    {
        $response = app(EnsureOwnerForExcelDownloads::class)->handle(
            $this->requestFor('ADMIN', 'member-active.index'),
            fn () => response('allowed')
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_all_roles_can_download_member_pt_check_in_report_excel(): void
    {
        foreach (['OWNER', 'ADMIN', 'CS', 'CSPOS', 'FC', 'PT'] as $role) {
            $response = app(EnsureOwnerForExcelDownloads::class)->handle(
                $this->requestFor($role, 'report-member-pt-checkin', ['excel' => '1']),
                fn () => response('allowed')
            );

            $this->assertSame(200, $response->getStatusCode(), "Role {$role} tidak dapat download Excel.");
        }
    }

    private function requestFor(string $role, string $routeName, array $query = []): Request
    {
        $request = Request::create('/', 'GET', $query);
        $route = new Route(['GET'], '/', fn () => null);
        $route->name($routeName);

        $user = new User();
        $user->role = $role;

        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
