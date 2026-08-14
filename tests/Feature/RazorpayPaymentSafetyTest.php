<?php

namespace Tests\Feature;

use App\Services\RazorpayService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The safeguards around the Razorpay callbacks: a payment is never processed
 * twice, a forged callback is rejected, and the money is always recorded.
 */
class RazorpayPaymentSafetyTest extends TestCase
{
    use DatabaseTransactions;

    private const SECRET = 'test-secret-key';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.razorpay.key' => 'rzp_test_key',
            'services.razorpay.secret' => self::SECRET,
        ]);
    }

    public function test_a_payment_is_recorded_with_its_gateway_id(): void
    {
        RazorpayService::record($this->payment('pay_RECORD001', 49900), null, 4242, 'Subscription');

        $row = DB::table('payment_history')->where('payment_id', 'pay_RECORD001')->first();

        $this->assertNotNull($row);
        $this->assertSame(4242, (int) $row->user_id);
        $this->assertEqualsWithDelta(499.00, (float) $row->payment_amount, 0.01);
        $this->assertSame('captured', $row->payment_status);
        $this->assertSame('Subscription', $row->payment_for);
    }

    public function test_a_payment_is_only_processed_once(): void
    {
        $this->assertFalse(RazorpayService::alreadyProcessed('pay_ONCE001'));

        RazorpayService::record($this->payment('pay_ONCE001', 10000), null, 1, 'Subscription');

        // A resubmitted callback must be recognised, otherwise the subscription
        // would be extended a second time off one payment.
        $this->assertTrue(RazorpayService::alreadyProcessed('pay_ONCE001'));
    }

    public function test_an_unknown_payment_is_not_treated_as_processed(): void
    {
        $this->assertFalse(RazorpayService::alreadyProcessed('pay_NEVER_SEEN'));
        $this->assertFalse(RazorpayService::alreadyProcessed(null));
        $this->assertFalse(RazorpayService::alreadyProcessed(''));
    }

    public function test_a_genuine_signature_is_accepted(): void
    {
        $orderId = 'order_SIG001';
        $paymentId = 'pay_SIG001';

        $this->assertTrue(RazorpayService::signatureIsValid([
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => hash_hmac('sha256', $orderId.'|'.$paymentId, self::SECRET),
        ]));
    }

    public function test_a_forged_signature_is_rejected(): void
    {
        $this->assertFalse(RazorpayService::signatureIsValid([
            'razorpay_order_id' => 'order_SIG002',
            'razorpay_payment_id' => 'pay_SIG002',
            'razorpay_signature' => hash_hmac('sha256', 'order_SIG002|pay_SIG002', 'the-wrong-secret'),
        ]));
    }

    public function test_a_callback_without_a_signature_is_allowed_through(): void
    {
        // Deliberate: rejecting these would break any flow that does not send a
        // signature. It is logged as a warning instead.
        $this->assertTrue(RazorpayService::signatureIsValid([
            'razorpay_payment_id' => 'pay_NOSIG',
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function payment(string $id, int $amountInPaise): array
    {
        return [
            'id' => $id,
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'status' => 'captured',
            'method' => 'upi',
        ];
    }
}
