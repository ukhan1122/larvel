<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{


    public function showAll() {

        $wallets = Wallet::with(['holder'])->get();

        return response()->json([
            'wallets' => $wallets
        ]);
    }


    /**
     * Show the default wallet and balance for a user.
     */
    public function show(User $user)
    {
        return response()->json([
            'user_id' => $user->id,
            'balance' => $user->balance,
            'wallet'  => $user->wallet,
        ]);
    }

    /**
     * List all transactions for a user across all wallets.
     */
    public function transactions(User $user)
    {
        // transactions() returns a morphMany of all wallet transactions :contentReference[oaicite:2]{index=2}
        $data = $user
            ->transactions()
            ->orderBy('created_at','desc')
            ->paginate(25);

        return response()->json($data);
    }

    /**
     * Confirm an unconfirmed transaction.
     */
    public function confirm(User $user, Transaction $transaction)
    {
        // Ensure ownership
        if (! ($transaction->payable_id === $user->id
            && $transaction->payable_type === get_class($user))
        ) {
            abort(404, 'Transaction not found for this user');
        }

        // confirm() from CanConfirm trait :contentReference[oaicite:3]{index=3}
        $ok = $user->confirm($transaction);

        return response()->json(['confirmed' => $ok]);
    }

    /**
     * Cancel (reset) a confirmed transaction.
     */
    public function cancel(User $user, Transaction $transaction)
    {
        if (! ($transaction->payable_id === $user->id
            && $transaction->payable_type === get_class($user))
        ) {
            abort(404, 'Transaction not found for this user');
        }

        // resetConfirm() from CanConfirm trait :contentReference[oaicite:4]{index=4}
        $ok = $user->resetConfirm($transaction);

        return response()->json(['canceled' => $ok]);
    }

    /**
     * Deposit funds into the user's default wallet.
     */
    public function deposit(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $txn = $user->deposit($request->amount);

        return response()->json(['transaction' => $txn]);
    }

    /**
     * Withdraw funds from the user's default wallet.
     */
    public function withdraw(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $txn = $user->withdraw($request->amount);

        return response()->json(['transaction' => $txn]);
    }
}
