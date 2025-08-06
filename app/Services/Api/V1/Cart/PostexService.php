<?php

namespace App\Services\Api\V1\Cart;

use App\Models\Address;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PostexService
{
    public function sendOrderToPostex($order, $itemsData, $products, $buyerTotal)
    {
        try {

            $address = \App\Models\Address::find($order->delivery_address_id);

            $fullAddress = trim(
                collect([
                    $address->address_line_1,
                    $address->address_line_2,
                    $address->city,
                    $address->state_province_or_region,
                    $address->zip_or_postal_code
                ])
                    ->filter() // Remove null/empty values
                    ->implode(', ')
            );

            $guestInfo = [
                'city'     => $order->address->city ?? 'Karachi',
                'first_name' => $order->buyer->first_name ?? $order->guest_name,
                'last_name'  => $order->buyer->last_name ?? '',
                'address'  => $fullAddress ?? '',
                'phone'    => $order->buyer->phone ?? $order->guest_phone,
            ];


            $payload = [
                "cityName"          => $guestInfo['city'],
                "customerName"      => trim($guestInfo['first_name'].' '.$guestInfo['last_name']) ??  $order->guest_name,
                "customerPhone"     => $guestInfo['phone'] ??  $order->guest_phone,
                "deliveryAddress"   => $guestInfo['address'],
                "invoiceDivision"   => 1,
                "invoicePayment"    => (string)$buyerTotal,
                "items"             => count($itemsData),
                "orderDetail"       => "Items: " . implode(', ', array_map(function($item) use ($products) {
                        return $products[$item['product_id']]->title . " x " . $item['quantity'];
                    }, $itemsData)),
                "orderRefNumber"    => $order->tracking_no,
                "orderType"         => "Normal",
                "transactionNotes"  => "Order from Closyyyy",
                "pickupAddressCode" => "001",
            ];


            $client = new Client();
            $response = $client->post(
                'https://api.postex.pk/services/integration/api/order/v3/create-order',
                [
                    'headers' => [
                        'token' => config('services.postex.token'),
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                    'timeout' => 15,
                ]
            );

            $body = json_decode($response->getBody(), true);
            Log::info('PostEx API Response: ', $body);

            // If you want to store tracking number returned by PostEx:
            if (!empty($body['dist']['trackingNumber'])) {
                $order->postex_tracking_no = $body['dist']['trackingNumber'];
                $order->save();
            }

            // Optionally store the whole response for troubleshooting
            // $order->postex_response = $body;
            // $order->save();

            return $body;
        } catch (\Exception $e) {
            Log::error('PostEx API Error: ' . $e->getMessage());

            return false;
        }
    }}
