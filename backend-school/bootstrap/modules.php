<?php

/*
|--------------------------------------------------------------------------
| Register Module Service Providers
|--------------------------------------------------------------------------
|
| This file is responsible for registering all module service providers
| that are defined in the modules configuration file.
|
*/

$moduleProviders = config('modules.providers', []);

foreach ($moduleProviders as $provider) {
    if (class_exists($provider)) {
        app()->register($provider);
    }
}