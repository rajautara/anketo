<?php

use App\Libraries\SubmissionNotifier;
use CodeIgniter\Config\Services;
use CodeIgniter\Email\Email;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit coverage for the submission-notification path that does NOT require a
 * database: the disabled short-circuit, override-recipient sending with answer
 * formatting, and exception-safety. (The owner-email lookup path needs Shield's
 * users table and is exercised manually per the README.)
 *
 * @internal
 */
final class SubmissionNotifierTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('url'); // notifier uses site_url()
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'id'                   => 7,
            'user_id'              => 1,
            'title'                => 'Contact Us',
            'notify_on_submission' => 1,
            'notification_email'   => 'owner-override@example.com',
        ], $overrides);
    }

    /** @return list<array<string,mixed>> */
    private function answers(): array
    {
        return [
            ['field_id' => 1, 'field_key' => 'name', 'field_label' => 'Your Name', 'value' => 'Ada Lovelace', 'file_path' => null],
            ['field_id' => 2, 'field_key' => 'topics', 'field_label' => 'Topics', 'value' => json_encode(['Sales', 'Support']), 'file_path' => null],
            ['field_id' => 3, 'field_key' => 'resume', 'field_label' => 'Resume', 'value' => 'cv.pdf', 'file_path' => 'forms/7/abc123.pdf'],
        ];
    }

    public function testDisabledFormSendsNothing(): void
    {
        $fake = new FakeEmail();
        Services::injectMock('email', $fake);

        (new SubmissionNotifier())->notify($this->form(['notify_on_submission' => 0]), 99, $this->answers());

        $this->assertArrayNotHasKey('sent', $fake->captured, 'No email should be sent when notifications are disabled.');
    }

    public function testSendsToOverrideWithFormattedAnswers(): void
    {
        $fake = new FakeEmail();
        Services::injectMock('email', $fake);

        (new SubmissionNotifier())->notify($this->form(), 99, $this->answers());

        $this->assertTrue($fake->captured['sent'] ?? false, 'Email should be sent.');
        $this->assertSame('owner-override@example.com', $fake->captured['to']);
        $this->assertStringContainsString('Contact Us', $fake->captured['subject']);
        $this->assertSame('html', $fake->captured['mailType']);

        $body = $fake->captured['message'];
        $this->assertStringContainsString('Ada Lovelace', $body);              // plain value
        $this->assertStringContainsString('Sales, Support', $body);            // checkbox JSON -> comma-joined
        $this->assertStringContainsString('cv.pdf', $body);                    // file -> original filename
        $this->assertStringContainsString('forms/7/submissions/99', $body);    // detail link
    }

    public function testNeverThrowsWhenEmailFails(): void
    {
        $fake            = new FakeEmail();
        $fake->throwOnSend = true;
        Services::injectMock('email', $fake);

        // Must not bubble any exception into the caller (the public submitter).
        (new SubmissionNotifier())->notify($this->form(), 99, $this->answers());

        $this->assertStringContainsString('Ada Lovelace', $fake->captured['message'] ?? '', 'Body was composed before the send failure.');
    }

    public function testMissingRecipientSkips(): void
    {
        // Override blank -> would fall back to owner lookup; but with an unknown
        // user_id and no DB, resolveRecipient returns null and we skip. We assert
        // it does not throw and does not send. (Runs without DB because the mock
        // UserModel lookup returns null via the try/catch guard.)
        $fake = new FakeEmail();
        Services::injectMock('email', $fake);

        (new SubmissionNotifier())->notify($this->form(['notification_email' => '', 'user_id' => 999999]), 99, $this->answers());

        $this->assertArrayNotHasKey('sent', $fake->captured);
    }
}

/**
 * Records the fluent Email calls instead of sending.
 */
class FakeEmail extends Email
{
    /** @var array<string,mixed> */
    public array $captured = [];
    public bool $throwOnSend = false;

    public function setTo($to)
    {
        $this->captured['to'] = $to;

        return $this;
    }

    public function setSubject($subject)
    {
        $this->captured['subject'] = $subject;

        return $this;
    }

    public function setMailType($type = 'text')
    {
        $this->captured['mailType'] = $type;

        return $this;
    }

    public function setMessage($body)
    {
        $this->captured['message'] = $body;

        return $this;
    }

    public function send($autoClear = true): bool
    {
        if ($this->throwOnSend) {
            throw new \RuntimeException('SMTP down');
        }
        $this->captured['sent'] = true;

        return true;
    }

    public function printDebugger($include = ['headers', 'subject', 'body']): string
    {
        return '';
    }
}
