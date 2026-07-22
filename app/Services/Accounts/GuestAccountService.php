<?php

namespace App\Services\Accounts;

use App\Mail\AutoCreatedAccountMail;
use App\Mail\ExistingAccountOrderMail;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

class GuestAccountService
{
    public function process(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedOrder->guest_account_status, [
                Order::GUEST_ACCOUNT_CREATED,
                Order::GUEST_ACCOUNT_EXISTING,
            ], true)) {
                return $lockedOrder->load('user');
            }

            if ($lockedOrder->guest_account_status !== Order::GUEST_ACCOUNT_PENDING) {
                throw new RuntimeException('Заказ не был оформлен гостем.');
            }

            if (
                $lockedOrder->payment_status !== PaymentTransaction::STATUS_PAID
                || $lockedOrder->paid_at === null
                || ! $lockedOrder->paymentTransactions()
                    ->where('status', PaymentTransaction::STATUS_PAID)
                    ->exists()
            ) {
                throw new RuntimeException('Аккаунт можно привязать только к оплаченному заказу.');
            }

            $email = Str::lower(trim($lockedOrder->customer_email));
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->lockForUpdate()
                ->first();
            $temporaryPassword = null;
            $accountCreated = false;
            $credentialMailer = null;

            if (! $user) {
                $credentialMailer = $this->credentialMailer();
                $user = User::query()->createOrFirst(
                    ['email' => $email],
                    function () use (&$temporaryPassword): array {
                        $temporaryPassword = Str::password(32);

                        return [
                            'password' => Hash::make($temporaryPassword),
                            'is_auto_created' => true,
                            'must_change_password' => true,
                            'status' => User::STATUS_ACTIVE,
                        ];
                    }
                );
                $accountCreated = $user->wasRecentlyCreated;

                if (! $accountCreated) {
                    $temporaryPassword = null;
                    $credentialMailer = null;
                }
            }

            $lockedOrder->forceFill([
                'user_id' => $user->getKey(),
                'customer_email' => $email,
                'guest_account_status' => $accountCreated
                    ? Order::GUEST_ACCOUNT_CREATED
                    : Order::GUEST_ACCOUNT_EXISTING,
            ])->save();

            $this->queueMailAfterCommit(
                $lockedOrder,
                $email,
                $temporaryPassword,
                $accountCreated,
                $credentialMailer,
            );

            return $lockedOrder->load('user');
        }, 3);
    }

    private function queueMailAfterCommit(
        Order $order,
        string $email,
        ?string $temporaryPassword,
        bool $accountCreated,
        ?string $credentialMailer,
    ): void {
        $orderNumber = $order->order_number;

        if ($accountCreated) {
            if ($temporaryPassword === null || $credentialMailer === null) {
                throw new RuntimeException('Временный пароль не был сформирован.');
            }

            DB::afterCommit(function () use ($credentialMailer, $email, $orderNumber, $temporaryPassword): void {
                Mail::mailer($credentialMailer)
                    ->to($email)
                    ->queue(new AutoCreatedAccountMail(
                        $email,
                        $temporaryPassword,
                        $orderNumber,
                    ));
            });

            return;
        }

        DB::afterCommit(function () use ($email, $orderNumber): void {
            Mail::to($email)->queue(new ExistingAccountOrderMail($orderNumber));
        });
    }

    private function credentialMailer(): string
    {
        $mailer = (string) config('mail.credentials_mailer', 'smtp');

        if (! $this->isSafeForCredentials($mailer)) {
            throw new LogicException('Почтовый транспорт для временных паролей настроен небезопасно.');
        }

        return $mailer;
    }

    private function isSafeForCredentials(string $mailer, array $visited = []): bool
    {
        if ($mailer === '' || in_array($mailer, $visited, true)) {
            return false;
        }

        $configuration = config("mail.mailers.{$mailer}");

        if (! is_array($configuration)) {
            return false;
        }

        $transport = $configuration['transport'] ?? null;

        if (in_array($transport, ['log', 'array'], true)) {
            return false;
        }

        if (in_array($transport, ['failover', 'roundrobin'], true)) {
            $nestedMailers = $configuration['mailers'] ?? [];

            if (! is_array($nestedMailers) || $nestedMailers === []) {
                return false;
            }

            $visited[] = $mailer;

            foreach ($nestedMailers as $nestedMailer) {
                if (! is_string($nestedMailer) || ! $this->isSafeForCredentials($nestedMailer, $visited)) {
                    return false;
                }
            }
        }

        return is_string($transport) && $transport !== '';
    }
}
