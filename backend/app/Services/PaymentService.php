<?php

namespace App\Services;

use App\Models\Payment;

class PaymentService
{
    /**
     * Create a new payment record
     */
    public function createPayment(array $data): Payment
    {
        if (empty($data['billing_month'])) {
            $data['billing_month'] = now()->startOfMonth()->toDateString();
        }

        return Payment::create($data);
    }

    /**
     * Find payment by UUID
     */
    public function findPaymentByUuid(string $uuid): ?Payment
    {
        return Payment::where('uuid', $uuid)
            ->with(['subscription.plan', 'company'])
            ->first();
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Payment $payment, string $status, ?\DateTime $paidAt = null): Payment
    {
        $payment->update([
            'status' => $status,
            'paid_at' => $paidAt,
        ]);

        return $payment->fresh();
    }
}
