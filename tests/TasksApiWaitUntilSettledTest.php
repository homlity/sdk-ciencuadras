<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Tests;

use Ciencuadras\Sdk\Api\TasksApi;
use Ciencuadras\Sdk\Data\TaskSnapshot;
use Ciencuadras\Sdk\Data\TaskStatus;
use Ciencuadras\Sdk\Http\ApiResponse;
use Ciencuadras\Sdk\Http\HttpClientInterface;
use PHPUnit\Framework\TestCase;

/**
 * Fake HTTP client driven by a response sequence.
 * Each request() call pops the next entry:
 *  - ApiResponse  → returned as-is
 *  - \Throwable   → thrown
 */
final class SequenceHttpClient implements HttpClientInterface
{
    /** @var list<ApiResponse|\Throwable> */
    private array $sequence;

    /** @param list<ApiResponse|\Throwable> $sequence */
    public function __construct(array $sequence)
    {
        $this->sequence = $sequence;
    }

    public function request(string $method, string $path, array $options = []): ApiResponse
    {
        if ($this->sequence === []) {
            throw new \LogicException('No more responses in sequence.');
        }

        $next = array_shift($this->sequence);

        if ($next instanceof \Throwable) {
            throw $next;
        }

        return $next;
    }
}

final class TasksApiWaitUntilSettledTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Factory helpers
    // -----------------------------------------------------------------------

    private function okResponse(string $id, string $status): ApiResponse
    {
        $body = json_encode(['id' => $id, 'status' => $status], JSON_THROW_ON_ERROR);
        return new ApiResponse(200, [], $body);
    }

    private function errorResponse(): ApiResponse
    {
        return new ApiResponse(500, [], '{"error":"internal"}');
    }

    private function noSleep(): callable
    {
        return static fn (int $_s) => null;
    }

    // -----------------------------------------------------------------------
    // Settles before timeout
    // -----------------------------------------------------------------------

    public function testItReturnsSnapshotWhenTaskSettlesImmediately(): void
    {
        $client = new SequenceHttpClient([
            $this->okResponse('task-1', 'COMPLETED'),
        ]);
        $api = new TasksApi($client);

        $result = $api->waitUntilSettled('task-1', ['sleepFn' => $this->noSleep()]);

        self::assertSame(TaskStatus::COMPLETED, $result->status());
    }

    public function testItRetriesUntilTaskSettles(): void
    {
        $client = new SequenceHttpClient([
            $this->okResponse('task-2', 'RUNNING'),
            $this->okResponse('task-2', 'RUNNING'),
            $this->okResponse('task-2', 'COMPLETED'),
        ]);
        $api = new TasksApi($client);

        $result = $api->waitUntilSettled('task-2', [
            'maxAttempts'     => 5,
            'intervalSeconds' => 0,
            'sleepFn'         => $this->noSleep(),
        ]);

        self::assertSame(TaskStatus::COMPLETED, $result->status());
    }

    // -----------------------------------------------------------------------
    // throwOnTimeout: true (default) — must throw RuntimeException
    // -----------------------------------------------------------------------

    public function testItThrowsWhenTimeoutAndThrowOnTimeoutIsTrue(): void
    {
        $client = new SequenceHttpClient([
            $this->okResponse('task-3', 'RUNNING'),
            $this->okResponse('task-3', 'RUNNING'),
        ]);
        $api = new TasksApi($client);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/task-3/');

        $api->waitUntilSettled('task-3', [
            'maxAttempts' => 2,
            'sleepFn'     => $this->noSleep(),
        ]);
    }

    // -----------------------------------------------------------------------
    // throwOnTimeout: false — must return snapshot instead of throwing
    // -----------------------------------------------------------------------

    public function testItReturnsLastSnapshotWhenTimeoutAndThrowOnTimeoutIsFalse(): void
    {
        $client = new SequenceHttpClient([
            $this->okResponse('task-4', 'RUNNING'),
            $this->okResponse('task-4', 'RUNNING'),
        ]);
        $api = new TasksApi($client);

        $result = $api->waitUntilSettled('task-4', [
            'maxAttempts'    => 2,
            'throwOnTimeout' => false,
            'sleepFn'        => $this->noSleep(),
        ]);

        self::assertInstanceOf(TaskSnapshot::class, $result);
        self::assertSame(TaskStatus::RUNNING, $result->status());
    }

    // -----------------------------------------------------------------------
    // Bug scenario: HTTP errors on every attempt + throwOnTimeout: false
    //
    // Before the fix the condition `!$throwOnTimeout && $snapshot instanceof TaskSnapshot`
    // evaluated to false when $snapshot was null (all getSnapshot calls threw), so the
    // method threw RuntimeException even though throwOnTimeout was false.
    // -----------------------------------------------------------------------

    public function testItReturnsReadySnapshotWhenGetSnapshotAlwaysThrowsAndThrowOnTimeoutIsFalse(): void
    {
        // 500 responses cause BaseApi::send() to throw ApiException on every attempt
        $client = new SequenceHttpClient([
            $this->errorResponse(),
            $this->errorResponse(),
            $this->errorResponse(),
        ]);
        $api = new TasksApi($client);

        $result = $api->waitUntilSettled('task-5', [
            'maxAttempts'    => 3,
            'throwOnTimeout' => false,
            'sleepFn'        => $this->noSleep(),
        ]);

        self::assertInstanceOf(TaskSnapshot::class, $result);
        self::assertSame('task-5', $result->id());
        self::assertSame(TaskStatus::READY, $result->status());
    }

    /**
     * Regression: a single failing attempt with throwOnTimeout: false must
     * never raise RuntimeException.
     */
    public function testThrowOnTimeoutFalseIsNeverIgnoredWhenSnapshotIsNull(): void
    {
        $client = new SequenceHttpClient([
            $this->errorResponse(),
        ]);
        $api = new TasksApi($client);

        $result = $api->waitUntilSettled('task-6', [
            'maxAttempts'    => 1,
            'throwOnTimeout' => false,
            'sleepFn'        => $this->noSleep(),
        ]);

        self::assertInstanceOf(TaskSnapshot::class, $result);
        self::assertSame(TaskStatus::READY, $result->status());
    }
}
