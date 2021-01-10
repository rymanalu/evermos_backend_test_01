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
            $isSuccess = $status >= 200 && $status < 400;

            $message = $isSuccess ? 'Success' : 'Error';

            $body = [
                'meta' => [
                    'status' => $status,
                    'message' => Arr::get($data, 'meta_message', $message),
                ],
            ];

            if ($isSuccess) {
                $body['data'] = Arr::except($data, 'meta_message');
            } else {
                $body['errors'] = Arr::get($data, 'errors');
            }

            $response = Response::json($body, $status, $headers, $options);

            return $callback ? $response->withCallback($callback) : $response;
        });
    }
}
