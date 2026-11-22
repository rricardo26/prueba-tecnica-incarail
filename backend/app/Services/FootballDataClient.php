<?php

namespace App\Services;

use App\Exceptions\ExternalRateLimitExceededException;
use App\Exceptions\ExternalResourceRestrictedException;
use App\Exceptions\ExternalServiceUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

final class FootballDataClient
{
    private PendingRequest $http;

    public function __construct()
    {
        $this->http = Http::baseUrl((string) config('services.football_data.base_url'))
            ->withHeaders([
                'X-Auth-Token' => (string) config('services.football_data.key'),
            ])
            ->timeout(10)
            ->acceptJson();
    }

    public function getCompetitions(): array
    {
        $data = $this->send('GET', '/competitions');

        return $data['competitions'] ?? [];
    }

    public function getCompetitionDetail(int $externalCompetitionId): array
    {
        return $this->send('GET', "/competitions/{$externalCompetitionId}");
    }

    public function getCompetitionTeams(int $externalCompetitionId): array
    {
        $data = $this->send('GET', "/competitions/{$externalCompetitionId}/teams");

        return $data['teams'] ?? [];
    }

    private function send(string $method, string $uri): array
    {
        $allowed = RateLimiter::attempt(
            key: 'football-data-outbound',
            maxAttempts: 10,
            callback: fn () => true,
            decaySeconds: 60
        );

        if (! $allowed) {
            throw new ExternalRateLimitExceededException(
                'Se alcanzó el límite preventivo de 10 peticiones por minuto hacia football-data.org.'
            );
        }

        try {
            $response = $this->http->send($method, $uri);
        } catch (ConnectionException $e) {
            throw new ExternalServiceUnavailableException(
                'No se pudo establecer conexión con football-data.org.',
                503,
                $e
            );
        }

        if ($response->status() === 429) {
            throw new ExternalRateLimitExceededException(
                'football-data.org rechazó la solicitud por límite de tasa (429).'
            );
        }

        if ($response->status() === 403) {
            throw new ExternalResourceRestrictedException(
                'Esta competición no está incluida en el plan gratuito de football-data.org.'
            );
        }

        if ($response->serverError()) {
            throw new ExternalServiceUnavailableException(
                'football-data.org devolvió un error interno.',
                502
            );
        }

        $response->throw();

        return $response->json() ?? [];
    }
}