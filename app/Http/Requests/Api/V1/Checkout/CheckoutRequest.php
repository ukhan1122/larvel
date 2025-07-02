<?php

namespace App\Http\Requests\Api\V1\Checkout;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;
use App\Models\Cart;
use App\Models\Product;
class CheckoutRequest extends FormRequest
{
    public function authorize()
    {
        // Buyer must be authenticated
        return $this->user() !== null;
    }

    public function rules()
    {
        $cartId = optional(Cart::firstOrCreate(['user_id' => $this->user()->id]))->id;

        return [
            'seller_id' => ['required', 'exists:users,id',
                // Seller cannot be the buyer
                function($attr, $value, $fail) {
                    if ($value == $this->user()->id) {
                        $fail('You cannot checkout your own products.');
                    }
                }
            ],
            'cart_items'             => ['required', 'array', 'min:1'],
            'cart_items.*.product_id'=> [
                'required',
                'integer',
                'exists:products,id',
                // Must exist in this buyer's cart
                Rule::exists('cart_items', 'product_id')
                    ->where('cart_id', $cartId)
            ],
            'cart_items.*.quantity'  => ['required', 'integer', 'min:1'],
            'delivery_address_id' => [
                'required',
                Rule::exists('addresses', 'id')->where(function ($query) {
                    $query->where('address_type', 'shipping')
                        ->where('user_id', auth()->id());
                }),
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function($validator) {
            $sellerId = $this->input('seller_id');
            $cartItems = $this->input('cart_items', []);
            $threshold = 3000;

            foreach ($cartItems as $i => $item) {
                $product = Product::find($item['product_id']);

                // 1) Product must belong to seller
                if ($product->user_id !== (int)$sellerId) {
                    $validator->errors()->add(
                        "cart_items.{$i}.product_id",
                        "Product ID {$product->id} does not belong to seller {$sellerId}."
                    );
                    continue;
                }

                // 2) Requested quantity <= quantity_left
                if ($item['quantity'] > $product->quantity_left) {
                    $validator->errors()->add(
                        "cart_items.{$i}.quantity",
                        "Only {$product->quantity_left} unit(s) left in stock for product ID {$product->id}."
                    );
                }

                // (Optional) 3) Prevent over‐large single‐order
                if ($product->price * $item['quantity'] > 1000000) {
                    $validator->errors()->add(
                        "cart_items.{$i}.quantity",
                        "Line total for product ID {$product->id} exceeds allowed maximum."
                    );
                }
            }
        });
    }

    public function messages()
    {
        return [
            'cart_items.*.product_id.exists'  => 'The product must exist in your cart.',
            'cart_items.*.quantity.min'       => 'You must request at least 1 unit.',
            'delivery_address_id.required'    => 'Please add an address to confirm order.',
            'delivery_address_id.exists'      => 'The selected delivery address is invalid or does not belong to you.',
        ];
    }
}
