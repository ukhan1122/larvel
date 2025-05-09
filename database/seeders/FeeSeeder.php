<?php

namespace Database\Seeders;

use App\Models\Fees;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Fees::create(['fee_type' => 'delivery', 'fee_amount' => 250.00]);
        Fees::create(['fee_type' => 'platform', 'fee_amount' => 0.10]);
        Fees::create(['fee_type' => 'market_threshold', 'fee_amount' => 3000]);
    }
}
