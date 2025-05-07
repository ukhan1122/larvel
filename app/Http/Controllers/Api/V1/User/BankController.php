<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\BankDetail;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BankController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $request->validate([
            'bank_name' => 'nullable|string|max:255',
            'account_title' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:50',
            'branch_code' => 'nullable|string|max:50',
        ]);

        $user = auth()->user();

        $bankDetail = BankDetail::updateOrCreate(
            ['user_id' => $user->id],
            $request->only('bank_name', 'account_title', 'account_number', 'iban', 'branch_code')
        );

        return $this->successResponse($bankDetail, 'Bank details stored successfully.');
    }

    public function show()
    {
        $user = auth()->user();
        $bankDetail = $user->bankDetail;

        if (!$bankDetail) {
            return $this->errorResponse('No bank details found.');
        }

        return $this->successResponse($bankDetail);
    }
}
