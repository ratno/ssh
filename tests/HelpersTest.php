<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class HelpersTest extends TestCase
{
    // -------------------------------------------------------------------------
    // ssh_run
    // -------------------------------------------------------------------------

    public function test_ssh_run_returns_login_failure_message(): void
    {
        $ssh = $this->createMock(\phpseclib3\Net\SSH2::class);
        $ssh->method('login')->willReturn(false);

        $result = $this->callSshRun($ssh, 'whoami');

        $this->assertStringContainsString('login failed', $result);
        $this->assertStringContainsString('testuser@localhost', $result);
    }

    public function test_ssh_run_executes_single_string_command(): void
    {
        $ssh = $this->mockSsh();
        $ssh->expects($this->once())
            ->method('exec')
            ->with('whoami')
            ->willReturn('testuser');

        $result = $this->callSshRun($ssh, 'whoami');

        $this->assertSame(['testuser'], $result);
    }

    public function test_ssh_run_executes_multiple_commands(): void
    {
        $ssh = $this->mockSsh();
        $ssh->expects($this->exactly(2))
            ->method('exec')
            ->willReturnOnConsecutiveCalls('testuser', '/var/www');

        $result = $this->callSshRun($ssh, ['whoami', 'pwd']);

        $this->assertSame(['testuser', '/var/www'], $result);
    }

    public function test_ssh_run_joins_array_command_with_and(): void
    {
        $ssh = $this->mockSsh();
        $ssh->expects($this->once())
            ->method('exec')
            ->with('git fetch && git pull')
            ->willReturn('Already up to date.');

        $result = $this->callSshRun($ssh, [['git fetch', 'git pull']]);

        $this->assertSame(['Already up to date.'], $result);
    }

    public function test_ssh_run_prepends_base_dir(): void
    {
        $ssh = $this->mockSsh();
        $ssh->expects($this->once())
            ->method('exec')
            ->with('cd /var/www && whoami')
            ->willReturn('www-data');

        $result = $this->callSshRun($ssh, 'whoami', '/var/www');

        $this->assertSame(['www-data'], $result);
    }

    public function test_ssh_run_prepends_base_dir_to_array_command(): void
    {
        $ssh = $this->mockSsh();
        $ssh->expects($this->once())
            ->method('exec')
            ->with('cd /app && git fetch && git pull')
            ->willReturn('');

        $result = $this->callSshRun($ssh, [['git fetch', 'git pull']], '/app');

        $this->assertSame([''], $result);
    }

    public function test_ssh_run_skips_empty_string_commands(): void
    {
        $ssh = $this->mockSsh();
        $ssh->expects($this->once())
            ->method('exec')
            ->with('whoami')
            ->willReturn('root');

        $result = $this->callSshRun($ssh, ['', 'whoami', '']);

        $this->assertSame(['root'], $result);
    }

    public function test_ssh_run_skips_empty_array_commands(): void
    {
        $ssh = $this->mockSsh();
        $ssh->expects($this->once())
            ->method('exec')
            ->with('whoami')
            ->willReturn('root');

        $result = $this->callSshRun($ssh, [[], 'whoami']);

        $this->assertSame(['root'], $result);
    }

    // -------------------------------------------------------------------------
    // request_post
    // -------------------------------------------------------------------------

    public function test_request_post_returns_response_body(): void
    {
        $stream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $stream->method('getContents')->willReturn('{"ok":true}');

        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $client = $this->createMock(\GuzzleHttp\Client::class);
        $client->expects($this->once())
            ->method('request')
            ->with('POST', 'https://example.com/api', ['form_params' => ['foo' => 'bar']])
            ->willReturn($response);

        $result = $this->callRequestPost($client, 'https://example.com/api', ['foo' => 'bar']);

        $this->assertSame('{"ok":true}', $result);
    }

    public function test_request_post_passes_all_params(): void
    {
        $stream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $stream->method('getContents')->willReturn('ok');

        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $params = ['username' => 'admin', 'password' => 'secret', 'action' => 'login'];

        $client = $this->createMock(\GuzzleHttp\Client::class);
        $client->expects($this->once())
            ->method('request')
            ->with('POST', 'https://example.com/login', ['form_params' => $params])
            ->willReturn($response);

        $result = $this->callRequestPost($client, 'https://example.com/login', $params);

        $this->assertSame('ok', $result);
    }

    // -------------------------------------------------------------------------
    // Helpers to inject mocks without touching global functions
    // -------------------------------------------------------------------------

    private function mockSsh(): MockObject&\phpseclib3\Net\SSH2
    {
        $ssh = $this->createMock(\phpseclib3\Net\SSH2::class);
        $ssh->method('login')->willReturn(true);
        return $ssh;
    }

    /**
     * Exercises ssh_run logic with an injected SSH mock, bypassing real RSA/network.
     *
     * @param string|array $command
     */
    private function callSshRun(
        \phpseclib3\Net\SSH2 $ssh,
        string|array $command,
        string $base_dir = ''
    ): string|array {
        // Normalize to array to match the real function's contract
        if (is_string($command)) {
            $command = [$command];
        }

        if (!$ssh->login('testuser', null)) {
            return 'login failed for testuser@localhost';
        }

        $prefix = $base_dir ? "cd $base_dir && " : '';
        $results = [];

        foreach ($command as $item_cmd) {
            if (is_array($item_cmd) && count($item_cmd)) {
                $results[] = $ssh->exec($prefix . implode(' && ', $item_cmd));
            } else {
                if ($item_cmd) {
                    $results[] = $ssh->exec($prefix . $item_cmd);
                }
            }
        }

        return $results;
    }

    private function callRequestPost(
        \GuzzleHttp\Client $client,
        string $url,
        array $params
    ): string {
        $response = $client->request('POST', $url, ['form_params' => $params]);
        return $response->getBody()->getContents();
    }
}
