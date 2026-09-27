<?php
use App\Http\Middleware\ApiTokenMiddleware;
use App\Http\Middleware\CrmWebAuth;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\WorkspaceContextMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
->withMiddleware(function(Middleware $middleware): void {
    // Authentication and workspace context must run before implicit route-model binding.
    $middleware->prependToPriorityList(
        before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
        prepend: CrmWebAuth::class,
    );
    $middleware->prependToPriorityList(
        before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
        prepend: ApiTokenMiddleware::class,
    );
    $middleware->prependToPriorityList(
        before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
        prepend: WorkspaceContextMiddleware::class,
    );

    $middleware->alias([
        'crm.auth'=>CrmWebAuth::class,
        'crm.role'=>RoleMiddleware::class,
        'api.token'=>ApiTokenMiddleware::class,
        'workspace'=>WorkspaceContextMiddleware::class,
    ]);
})
->withExceptions(fn(Exceptions $exceptions): void => {})
->create();
