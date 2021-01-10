<?php

namespace App\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerResponseMacro();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    protected function registerResponseMacro()
    {
        Response::macro('api', function (array $data = [], $status = 200, array $headers = [], $options = 0, $callback = null) {
            $message = $status >= 200 && $status < 400 ? 'Success' : 'Error';

            $data = [
                'meta' => [
                    'status' => $status,
                    'message' => Arr::get($data, 'meta_message', $message),
                ],
                'data' => Arr::except($data, 'meta_message'),
            ];

            $response = Response::json($data, $status, $headers, $options);

            return $callback ? $response->withCallback($callback) : $response;
        });
    }
}
