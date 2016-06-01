<?php

namespace Yab\Quarx\Console;

use Config;
use Artisan;
use Illuminate\Console\Command;
use Yab\Laracogs\Generators\CrudGenerator;

class ModuleMake extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'module:make {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a module for Quarx';

    /**
     * Generate a CRUD stack
     *
     * @return mixed
     */
    public function handle()
    {
        $crudGenerator = new CrudGenerator();

        $name = ucfirst(str_singular($this->argument('name')));

        $moduleDirectory = base_path('quarx/modules/'.ucfirst(($name)));

        if (! is_dir(base_path('quarx'))) {
            @mkdir(base_path('quarx'));
        }

        if (! is_dir(base_path('quarx/modules'))) {
            @mkdir(base_path('quarx/modules'));
        }

        @mkdir($moduleDirectory);
        @mkdir($moduleDirectory.'/Assets');
        @mkdir($moduleDirectory.'/Publishes');
        @mkdir($moduleDirectory.'/Publishes/app/Http', 0777, true);
        @mkdir($moduleDirectory.'/Publishes/app/Http/Controllers/Quarx', 0777, true);
        @mkdir($moduleDirectory.'/Publishes/resources/themes/default', 0777, true);
        @mkdir($moduleDirectory.'/Controllers');
        @mkdir($moduleDirectory.'/Services');
        @mkdir($moduleDirectory.'/Views');
        @mkdir($moduleDirectory.'/Tests');

        file_put_contents($moduleDirectory.'/config.php', "<?php \n\n\n return [];");
        file_put_contents($moduleDirectory.'/Views/menu.blade.php', "<li><a href=\"<?= URL::to('quarx/".strtolower(($name))."'); ?>\"><span class=\"fa fa-file\"></span> ".ucfirst(($name))."</a></li>");

        $config = [
            'bootstrap'                  => false,
            'semantic'                   => false,
            '_path_service_'             => $moduleDirectory.'/Services',
            '_path_controller_'          => $moduleDirectory.'/Controllers',
            '_path_views_'               => $moduleDirectory.'/Views',
            '_path_tests_'               => $moduleDirectory.'/Tests',
            '_path_routes_'              => $moduleDirectory.'/routes.php',
            'routes_prefix'              => "<?php \n\nRoute::group(['namespace' => 'Quarx\Modules\\".ucfirst(($name))."\Controllers', 'prefix' => 'quarx', 'middleware' => ['web', 'auth', 'quarx']], function () { \n\n",
            'routes_suffix'              => "\n\n});",
            '_app_namespace_'            => app()->getInstance()->getNamespace(),
            '_namespace_services_'       => 'Quarx\Modules\\'.ucfirst(($name)).'\Services',
            '_namespace_controller_'     => 'Quarx\Modules\\'.ucfirst(($name)).'\Controllers',
            '_name_name_'               => (strtolower($name)),
            '_lower_case_'               => strtolower($name),
            '_lower_casePlural_'         => (strtolower($name)),
            '_camel_case_'               => ucfirst(camel_case($name)),
            '_camel_casePlural_'         => ucfirst((camel_case($name))),
            'template_source'            => __DIR__.'/../Templates/Basic/',
        ];

        $appConfig = $config;
        $appConfig['template_source'] = __DIR__.'/../Templates/AppBasic/';
        $appConfig['_path_controller_'] = $moduleDirectory.'/Publishes/app/Http/Controllers/Quarx';
        $appConfig['_path_views_'] = $moduleDirectory.'/Publishes/resources/themes/default';
        $appConfig['_path_routes_'] = $moduleDirectory.'/Publishes/app/Http/'.$config['_lower_casePlural_'].'-routes.php';
        $appConfig['_namespace_controller_'] = $config['_app_namespace_'].'Http\Controllers\Quarx';
        $appConfig['routes_prefix'] = "<?php \n\nRoute::group(['namespace' => 'Quarx', 'middleware' => ['web']], function () {\n\n";
        $appConfig['routes_suffix'] = "\n\n});";

        try {
            $this->info('Building the admin side...');

            $this->line('Building controller...');
            $crudGenerator->createController($config);

            $this->line('Building service...');
            $crudGenerator->createService($config);

            $this->line('Building views...');
            $crudGenerator->createViews($config);

            $this->line('Building routes...');
            $crudGenerator->createRoutes($config, false);

            $this->info('Building the theme side...');

            $this->line('Building controller...');
            $crudGenerator->createController($appConfig);

            $this->line('Building views...');
            $crudGenerator->createViews($appConfig);

            $this->line('Building routes...');
            @file_put_contents($moduleDirectory.'/Publishes/app/Http/'.$config['_lower_casePlural_'].'-routes.php', '');
            $crudGenerator->createRoutes($appConfig, false);

            $this->line('You will need to publish your module to make it available to your vistors:');
            $this->comment('php artisan module:publish '.str_plural($table));
            $this->line('');
            $this->info('Add this to your `app/Providers/RouteServiceProver.php` in the `mapWebRoutes` method:');
            $this->comment("\nrequire app_path('Http/".$config['_lower_casePlural_']."-routes.php');\n");
        } catch (Exception $e) {
            throw new Exception("Unable to generate your Module", 1);
        }

        $this->info('Module for '.$name.' is done.');
    }
}
