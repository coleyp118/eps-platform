<?php
use PHPUnit\Framework\TestCase;
use Eko\Eps\EpsClient;

final class ClientRefIdTest extends TestCase
{
    private function client(): EpsClient
    {
        return new EpsClient(
            'dev123',
            'TEST_ACCESS_KEY_DO_NOT_USE',
            'sandbox',
            now: fn () => 1700000000000
        );
    }

    private function postParams(array $extra = []): array
    {
        return array_merge([
            'initiator_id' => '9962981729',
            'user_code' => '20810200',
            'customer_id' => '9123456789',
            'recipient_id' => '1',
            'amount' => 100,
            'tid' => '1',
        ], $extra);
    }

    public function testAutoGeneratesClientRefIdForNonGetRequest(): void
    {
        $target = $this->client()->resolveTarget('dmt-send-otp', $this->postParams());
        $body = json_decode($target['body'], true);

        $this->assertIsArray($body);
        $this->assertArrayHasKey('client_ref_id', $body);
        $this->assertIsString($body['client_ref_id']);
        $this->assertNotSame('', $body['client_ref_id']);
    }

    public function testPreservesExplicitClientRefId(): void
    {
        $target = $this->client()->resolveTarget(
            'dmt-send-otp',
            $this->postParams(['client_ref_id' => 'MY_REF'])
        );
        $body = json_decode($target['body'], true);

        $this->assertSame('MY_REF', $body['client_ref_id']);
    }

    public function testDoesNotInjectClientRefIdForGetRequest(): void
    {
        $target = $this->client()->resolveTarget('dmt-get-sender', [
            'customer_id' => '9123456789',
            'initiator_id' => '9962981729',
            'user_code' => '20810200',
        ]);

        $this->assertStringNotContainsString('client_ref_id', $target['url']);
        $this->assertNull($target['body']);
    }

    public function testGeneratesDistinctClientRefIdsAcrossCalls(): void
    {
        $first = $this->client()->resolveTarget('dmt-send-otp', $this->postParams());
        $second = $this->client()->resolveTarget('dmt-send-otp', $this->postParams());
        $firstBody = json_decode($first['body'], true);
        $secondBody = json_decode($second['body'], true);

        $this->assertNotSame($firstBody['client_ref_id'], $secondBody['client_ref_id']);
    }
}
