<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\PaymentLog;
use App\Http\Requests\StorePaymentRequest;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FundsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('funds.index');
    }

    /**
     * Store a manual payment request (EasyPaisa, JazzCash, etc.)
     */
    public function store(StorePaymentRequest $request)
    {
        $validated = $request->validated();

        // Check if reference already exists to prevent double submission
        $exists = Transaction::where('reference', $validated['reference'])
            ->where('type', 'deposit')
            ->exists();

        if ($exists) {
            return back()->withErrors(['reference' => 'This transaction reference has already been submitted.']);
        }

        // FIXED HIGH-5: Use secure service for exchange rate, not session
        $rate = ExchangeRateService::getUsdToPkr();
        $amountInUsd = round($validated['amount'] / $rate, 6);

        try {
            $transaction = Transaction::create([
                'user_id'     => Auth::id(),
                'amount'      => $amountInUsd,
                'type'        => 'deposit',
                'description' => strtoupper($validated['method']) . " Deposit (PKR " . number_format($validated['amount'], 2) . ")",
                'status'      => 'pending',
                'reference'   => $validated['reference'],
            ]);

            // Create a payment log for auditing
            PaymentLog::create([
                'user_id'        => Auth::id(),
                'transaction_id' => $transaction->id,
                'gateway'        => $validated['method'],
                'status'         => 'pending',
                'amount'         => $amountInUsd,
                'reference'      => $validated['reference'],
            ]);

            Log::info('Manual payment submitted', [
                'transaction_id' => $transaction->id,
                'user_id'        => Auth::id(),
                'method'         => $validated['method'],
                'amount_pkr'     => $validated['amount'],
                'amount_usd'     => $amountInUsd,
            ]);

            return redirect()->route('transactions.index')
                ->with('success', 'Your payment request has been submitted and is pending verification. It usually takes 10-30 minutes.');

        } catch (\Exception $e) {
            Log::error('Payment submission failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Failed to submit payment request. Please try again.']);
        }
    }
}
