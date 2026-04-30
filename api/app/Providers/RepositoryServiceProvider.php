<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Repositories\Roles\RoleRepositoryInterface;
use App\Repositories\Roles\RoleRepository;

use App\Repositories\Permissions\PermissionRepositoryInterface;
use App\Repositories\Permissions\PermissionRepository;

use App\Repositories\Navigation\NavigationRepositoryInterface;
use App\Repositories\Navigation\NavigationRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(NavigationRepositoryInterface::class, NavigationRepository::class);
    }

    public function boot()
    {
        //
    }
} 