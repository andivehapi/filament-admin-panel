<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FahrschuleClient
{
    /**
     * Make a POST request to the fahrschule.live API with the configured token and base URL.
     *
     * @param string $endpoint The API endpoint (e.g. '/services/sa/Admin/firmaOverview')
     * @param array $data Additional data to send with the request
     * @return \Illuminate\Http\Client\Response
     */
    public function post(string $endpoint, array $data = [])
    {
        $base_url = config('services.fahrschule_live.base_url');
        $token = config('services.fahrschule_live.token');
        return Http::baseUrl($base_url)
            ->asForm()
            ->post($endpoint, array_merge([
                'token' => $token,
            ], $data));
    }
} 