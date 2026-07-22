<?php

namespace Tests\Unit\Mail;

use App\Mail\AutoCreatedAccountMail;
use App\Mail\ExistingAccountOrderMail;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Tests\TestCase;

class AccountMailTest extends TestCase
{
    public function test_credentials_mail_is_encrypted_and_queued_after_commit(): void
    {
        $mail = new AutoCreatedAccountMail(
            'buyer@example.test',
            'temporary-password',
            'ORDER-001',
        );

        $this->assertInstanceOf(ShouldBeEncrypted::class, $mail);
        $this->assertInstanceOf(ShouldQueueAfterCommit::class, $mail);
        $this->assertSame('mail.accounts.auto-created', $mail->content()->view);
        $this->assertSame('temporary-password', $mail->temporaryPassword);
    }

    public function test_existing_account_mail_contains_no_password_property(): void
    {
        $mail = new ExistingAccountOrderMail('ORDER-002');

        $this->assertInstanceOf(ShouldBeEncrypted::class, $mail);
        $this->assertInstanceOf(ShouldQueueAfterCommit::class, $mail);
        $this->assertSame('mail.accounts.existing-order', $mail->content()->view);
        $this->assertFalse(property_exists($mail, 'temporaryPassword'));
    }
}
