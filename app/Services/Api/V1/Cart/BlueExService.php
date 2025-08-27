<?php

namespace App\Services\Api\V1\Cart;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlueExService
{
    public function sendOrderToBlueEx($order, $itemsData, $products, $buyerTotal)
    {
        try {
            // ---- Get delivery (customer) address ----
            $address = \App\Models\Address::find($order->delivery_address_id);

            $customerFullAddress = $this->buildFullAddress([
                $address->address_line_1 ?? null,
                $address->address_line_2 ?? null,
                $address->city ?? null,
                $address->state_province_or_region ?? null,
            ]);

            // ---- Customer (buyer) fields ----
            $customerFirst = $order->buyer->first_name ?? $order->guest_name ?? '';
            $customerLast  = $order->buyer->last_name ?? '';
            $customerPhone = $order->buyer->phone ?? $order->guest_phone ?? '';
            $customerEmail = $order->buyer->email ?? null;

            // ---- Shipper (seller) fields ----
            // Fallbacks come from config if seller fields are missing
            $seller         = $order->seller;
            $shipperName    = config('services.blueex.default_shipper_name', 'Closyyyy Seller');
            $shipperEmail   = $seller->email ?? config('services.blueex.default_shipper_email', 'noreply@closyyyy.test');
            $shipperPhone   = $seller->phone ?? config('services.blueex.default_shipper_phone', '03000000000');

            // If you store seller address in DB, plug it here; otherwise use config fallback
            $shipperAddress = $this->buildFullAddress([
                $seller->address_line_1 ?? config('services.blueex.default_shipper_address_line_1', 'Pickup Location'),
                $seller->address_line_2 ?? null,
                $seller->city ?? config('services.blueex.default_shipper_city', 'Karachi'),
                $seller->state ?? null,
            ]);

            // ---- City codes (BlueEX expects codes like KHI, LHE, ISB, etc.) ----
            $customerCityCode = $this->cityToBlueExCode($address->city ?? '');
            $shipperCityCode  = $this->cityToBlueExCode($seller->city ?? config('services.blueex.default_shipper_city', 'Karachi'));

            // ---- Items & weight ----
            $defaultItemWeight = (float) config('services.blueex.default_item_weight', 0.5); // kg
            $totalWeightKg     = 0.0;
            $productsDetail    = [];

            foreach ($itemsData as $item) {
                $p = $products[$item['product_id']];
                $qty = (int) $item['quantity'];

                // Expect product weight in kilograms; fallback to default if missing
                $w = (float) ($p->weight ?? $defaultItemWeight);

                $totalWeightKg += ($w * $qty);

                $productsDetail[] = [
                    'product_code'      => (string) ($p->id ?? $p->sku ?? 'NA'),
                    'product_name'      => (string) ($p->title ?? 'Item'),
                    'product_price'     => (string) ($p->price ?? 0),
                    'product_weight'    => (string) $w,
                    'product_quantity'  => (string) $qty,
                    'product_variations'=> (string) ($p->variation ?? $p->size ?? $p->color ?? ''),
                    'sku_code'          => (string) ($p->sku ?? ($p->id ?? 'NA')),
                ];
            }

            if ($totalWeightKg <= 0) {
                $totalWeightKg = (float) config('services.blueex.default_total_weight', 1.0);
            }

            // ---- Configurable knobs ----
            $username      = config('services.blueex.username', 'closyyyy');
            $password      = config('services.blueex.password', '12345');
            $endpoint      = config('services.blueex.endpoint', 'https://apis.blue-ex.com/api/V4/CreateBooking');
            $serviceCode   = config('services.blueex.service_code', 'BG');
            $paymentType   = config('services.blueex.payment_type', 'COD'); // COD | CC
            $fragile       = config('services.blueex.fragile', 'N');        // Y | N
            $parcelType    = config('services.blueex.parcel_type', 'P');    // P (parcel) | D (document)
            $insuranceReq  = config('services.blueex.insurance_require', 'N');
            $insuranceVal  = config('services.blueex.insurance_value', '0');
            $testBit       = config('services.blueex.testbit', 'Y');        // Y in sandbox
            $cnGenerate    = config('services.blueex.cn_generate', 'Y');    // get CN back
            $multiPickup   = config('services.blueex.multi_pickup', 'Y');

            // ---- Payload per BlueEX docs ----
            $payload = [
                'shipper_name'        => 'Closyyyy',
                'shipper_email'       => $shipperEmail,
                'shipper_contact'     => $shipperPhone,
                'shipper_address'     => $shipperAddress,
                'shipper_city'        => $shipperCityCode,

                'customer_name'       => trim($customerFirst . ' ' . $customerLast) ?: $customerFirst,
                'customer_email'      => $customerEmail,
                'customer_contact'    => $customerPhone,
                'customer_address'    => $customerFullAddress,
                'customer_city'       => $customerCityCode,
                'customer_country'    => 'PK',
                'customer_comment'    => 'Order from Closyyyy',

                'shipping_charges'    => (string) ($order->delivery_fee ?? 0),
                'payment_type'        => $paymentType,            // 'COD' or 'CC'
                'service_code'        => $serviceCode,            // 'BE' sample
                'total_order_amount'  => (string) $buyerTotal,
                'total_order_weight'  => (string) $totalWeightKg,
                'order_refernce_code' => (string) ($order->tracking_no ?? ('CLSY-' . $order->id)),
                'fragile'             => $fragile,
                'parcel_type'         => $parcelType,
                'insurance_require'   => $insuranceReq,
                'insurance_value'     => $insuranceVal,
                'testbit'             => $testBit,
                'cn_generate'         => $cnGenerate,
                'multi_pickup'        => $multiPickup,

                'products_detail'     => $productsDetail,
            ];

            Log::info('BlueEX CreateBooking payload:', $payload);

            // ---- API Call ----
            $response = Http::timeout(20)
                ->withBasicAuth($username, $password)
                ->acceptJson()
                ->asJson()
                ->post($endpoint, $payload);

            $body = $response->json();
            Log::info('BlueEX API Response:', [
                'status_code' => $response->status(),
                'body'        => $body,
            ]);

            // ---- Handle response ----
            if (!$response->successful()) {
                // Non-2xx – surface as failure
                return [
                    'success' => false,
                    'cnno'    => null,
                    'raw'     => $body,
                ];
            }

            // BlueEX success pattern: status == "1" and cnno present
            if (!empty($body['status']) && (string)$body['status'] === '1' && !empty($body['cnno'])) {
                $order->blueex_tracking_no = $body['cnno']; // or $order->blueex_tracking_no if you prefer
                $order->save();

                return [
                    'success' => true,
                    'cnno'    => $body['cnno'],
                    'raw'     => $body,
                ];
            }

            // Validation or logical failure from BlueEX (e.g., status "0")
            // They often return: { status: "0", message: "...", errors: { ... } }
            Log::warning('BlueEX reported failure:', $body ?? []);
            return [
                'success' => false,
                'cnno'    => null,
                'raw'     => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('BlueEX API Error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return [
                'success' => false,
                'cnno'    => null,
                'raw'     => null,
            ];
        }
    }

    // ----------------- Helpers -----------------

    private function buildFullAddress(array $parts): string
    {
        return trim(collect($parts)->filter()->implode(', '));
    }


    private function cityToBlueExCode(string $city): string
    {
        $c = strtoupper(trim($city));
        $map = [
            'KARACHI' => 'KHI',
            'KHI'     => 'KHI',
            'LAHORE'  => 'LHE',
            'LHE'     => 'LHE',
            'ISLAMABAD' => 'ISB',
            'ISB'     => 'ISB',
            'RAWALPINDI' => 'RWP',
            'RWP'     => 'RWP',
            'FAISALABAD' => 'FSD',
            'FSD'     => 'FSD',
            'MULTAN'  => 'MUX',
            'MUX'     => 'MUX',
            'PESHAWAR'=> 'PEW',
            'PEW'     => 'PEW',
            'QUETTA'  => 'UET', // verify BlueEX code for Quetta; placeholder
            'GUJRANWALA' => 'GUJ', // verify
            'SIALKOT' => 'SKT',    // verify
            // add/adjust as needed
        ];

        return $map[$c] ?? 'KHI'; // safe fallback
    }
}
