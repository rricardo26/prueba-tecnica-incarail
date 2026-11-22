<?php

namespace Tests\Unit;

use App\Exceptions\ExternalRateLimitExceededException;
use App\Exceptions\ExternalServiceUnavailableException;
use App\Services\FootballDataClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class FootballDataClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('football-data-outbound');
    }

    public function test_allows_up_to_10_requests_per_minute_and_blocks_the_11th(): void
    {
        Http::fake([
            'api.football-data.org/*' => Http::response(['competitions' => []], 200),
        ]);

        $client = new FootballDataClient();

        for ($i = 1; $i <= 10; $i++) {
            $this->assertIsArray($client->getCompetitions());
        }

        Http::assertSentCount(10);

        $this->expectException(ExternalRateLimitExceededException::class);
        $client->getCompetitions();
    }

    public function test_throws_rate_limit_exception_when_external_api_returns_429(): void
    {
        Http::fake([
            'api.football-data.org/*' => Http::response(['message' => 'Too Many Requests'], 429),
        ]);

        $client = new FootballDataClient();

        $this->expectException(ExternalRateLimitExceededException::class);
        $client->getCompetitions();
    }

    public function test_throws_service_unavailable_when_connection_fails(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $client = new FootballDataClient();

        $this->expectException(ExternalServiceUnavailableException::class);
        $client->getCompetitions();
    }
}