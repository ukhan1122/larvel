<?php

namespace App\Http\Requests\Api\V1\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\GuestCart;
use App\Models\Product;

class CheckoutRequestGuest extends FormRequest
{
    protected function prepareForValidation()
    {
        // Lift guest_id out of the nested guest_info payload
        if ($this->has('guest_info.guest_id')) {
            $this->merge([
                'guest_id' => $this->input('guest_info.guest_id'),
            ]);
        }
    }

    public function rules()
    {
        // Ensure there's a guest cart for validation of cart_items
        $guestId = $this->input('guest_id');
        $cart = GuestCart::firstOrCreate(['guest_id' => $guestId]);
        $cartId = $cart->id;

        return [
            // Validate the guest_info block itself
            'guest_info'                  => ['required', 'array'],
            'guest_info.guest_id'         => ['required', 'uuid', 'exists:guest_carts,guest_id'],
            'guest_info.email'            => ['required', 'email'],
            'guest_info.first_name'       => ['required', 'string', 'max:50'],
            'guest_info.last_name'        => ['required', 'string', 'max:50'],
            'guest_info.city'             => ['required', 'string', 'max:100'],
            'guest_info.address'          => ['required', 'string', 'max:255'],
            'guest_info.postal_code'      => ['string', 'max:20'],
            'guest_info.phone'            => ['required', 'string', 'max:20'],
            'guest_info.subscribe'        => ['boolean'],
            'guest_info.save_info'        => ['boolean'],
            'guest_info.text_offers'      => ['boolean'],

            // Lifted top-level guest_id for convenience
            'guest_id'                    => ['required', 'uuid', 'exists:guest_carts,guest_id'],

            // Seller and cart items
            'seller_id'                   => [
                'required',
                'exists:users,id',
                function ($attr, $value, $fail) use ($guestId) {
                    // Prevent self-checkout if guest_id clashes with a user ID
                    if ((string) $value === (string) $guestId) {
                        $fail('You cannot checkout your own products.');
                    }
                },
            ],
            'cart_items'                  => ['required', 'array', 'min:1'],
            'cart_items.*.product_id'     => [
                'required',
                'integer',
                'exists:products,id',
                Rule::exists('guest_cart_items', 'product_id')
                    ->where('guest_cart_id', $cartId),
            ],
            'cart_items.*.quantity'       => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $sellerId  = $this->input('seller_id');
            $cartItems = $this->input('cart_items', []);

            foreach ($cartItems as $i => $item) {
                $product = Product::find($item['product_id']);

                // 1) Product must belong to seller
                if ($product->user_id !== (int) $sellerId) {
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

                // 3) Optional: prevent overly large line total
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
            'guest_info.required'                => 'Guest information is required.',
            'guest_info.array'                   => 'Guest information must be a valid object.',
            'guest_info.*.required'              => 'This guest info field is required.',
            'guest_info.email.email'             => 'Please provide a valid email address.',
            'cart_items.*.product_id.exists'     => 'The product must exist in your cart.',
            'cart_items.*.quantity.min'          => 'You must request at least 1 unit.',
            'seller_id.exists'                   => 'The selected seller is invalid.',
        ];
    }
}
