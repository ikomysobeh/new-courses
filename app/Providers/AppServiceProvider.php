<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Scramble::configure()
            ->withOperationTransformers(function (Operation $operation, RouteInfo $routeInfo) {
                $uri = ltrim($routeInfo->route->uri(), '/');

                $domainPrefix = null;
                if (str_contains($uri, 'admin/')) {
                    $domainPrefix = 'Admin';
                }

                if (str_contains($uri, 'user/')) {
                    $domainPrefix = 'User';
                }

                if (! $domainPrefix) {
                    return;
                }

                $baseTags = count($operation->tags)
                    ? $operation->tags
                    : ['General'];

                $operation->setTags(array_map(
                    fn (string $tag) => $domainPrefix . ' - ' . $tag,
                    $baseTags,
                ));
            })
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'Bearer')
                );
            });
    }
}
