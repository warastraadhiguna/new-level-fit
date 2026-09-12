<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsurePtReadOnlyAccess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsurePtReadOnlyAccessTest extends TestCase
{
    public function test_pt_can_open_an_allowed_list_page(): void
    {
        $request = $this->requestFor('GET', 'trainer-session.index');

        $response = app(EnsurePtReadOnlyAccess::class)->handle($request, function () {
            return response('allowed');
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('allowed', $response->getContent());
    }

    public function test_pt_can_open_an_allowed_pt_free_list_page(): void
    {
        $request = $this->requestFor('GET', 'pt-free.active');

        $response = app(EnsurePtReadOnlyAccess::class)->handle($request, function () {
            return response('allowed');
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('allowed', $response->getContent());
    }

    public function test_pt_cannot_open_an_unlisted_page(): void
    {
        $this->expectException(HttpException::class);

        app(EnsurePtReadOnlyAccess::class)->handle(
            $this->requestFor('GET', 'members.index'),
            fn () => response('not allowed')
        );
    }

    public function test_pt_cannot_send_a_write_request_even_to_an_allowed_route(): void
    {
        $this->expectException(HttpException::class);

        app(EnsurePtReadOnlyAccess::class)->handle(
            $this->requestFor('POST', 'trainer-session.index'),
            fn () => response('not allowed')
        );
    }

    private function requestFor(string $method, string $routeName): Request
    {
        $request = Request::create('/', $method);
        $route = new Route([$method], '/', fn () => null);
        $route->name($routeName);

        $user = new User();
        $user->role = 'PT';

        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
