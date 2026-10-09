<?php

use App\Exceptions\ApiCustomException;
use App\Http\Middleware\OwnLocation;
use App\Http\Middleware\OwnsOffering;
use App\Http\Middleware\RejectWildcardScope;
use App\Http\Middleware\RequireHintedUserForStepUp;
use App\Http\Middleware\RequireLoginForWriteScope;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetWebThemeAndLocale;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: '',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [SetLocale::class,]);
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'owns.location' => OwnLocation::class,
            'owns.offering' => OwnsOffering::class,
        ]);
        // Theme and language go first in the web group; the priority list
        // keeps them right after StartSession, which they need to read and
        // store the values in the session.
        $middleware->web(prepend: [
            SetWebThemeAndLocale::class,
        ]);
        $middleware->appendToPriorityList(StartSession::class, SetWebThemeAndLocale::class);
        $middleware->web(append: [
            RejectWildcardScope::class,
            RequireLoginForWriteScope::class,
            RequireHintedUserForStepUp::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        //EDB 10/09/26: a JSON 404 answers a translated message instead of the internal model name (R40)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => __('exceptions.not_found')], 404);
            }
        });
        /** This is not required as laravel calls it automatically form the class
        $exceptions->render(
            function (Throwable $e, $request) {
                if ($e instanceof ApiCustomException) {
                    return $e->render($request);
                }
            }
        );
         */
    })
    ->withSchedule(
        function (Schedule $schedule) {
            $schedule->command('certifications:purge')->daily(); //EDB 09/04/26: deleting expried certifications, pending cronjob.
            $schedule->command('model:prune', [ //EDB 09/04/26: deleting not active profiles, pending cronjob.
                '--model' => [User::class],
            ])->weekly();
        }
    )->create();
