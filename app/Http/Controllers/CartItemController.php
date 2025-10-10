<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartItemController extends Controller
{
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe iniciar sesión para modificar el carrito'
                ], 401);
            }

            $request->validate([
                'quantity' => 'required|integer|min:1'
            ]);

            DB::beginTransaction();

            $cart = Cart::where('user_id', $user->id)->first();
            
            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrito no encontrado'
                ], 404);
            }

            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('id', $id)
                ->first();

            if (!$cartItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado en el carrito'
                ], 404);
            }

            $cartItem->quantity = $request->quantity;
            $cartItem->save();

            // Recalcular total del carrito
            $this->updateCartTotal($cart);

            DB::commit();

            $cart->load('items.product');
            $cartData = $this->calculateCartTotals($cart);

            return response()->json([
                'success' => true,
                'message' => 'Cantidad actualizada correctamente',
                'cart' => $cartData
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating cart item: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar cantidad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe iniciar sesión para modificar el carrito'
                ], 401);
            }

            DB::beginTransaction();

            $cart = Cart::where('user_id', $user->id)->first();
            
            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrito no encontrado'
                ], 404);
            }

            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('id', $id)
                ->first();

            if (!$cartItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado en el carrito'
                ], 404);
            }

            $cartItem->delete();

            // Recalcular total del carrito
            $this->updateCartTotal($cart);

            DB::commit();

            $cart->load('items.product');
            $cartData = $this->calculateCartTotals($cart);

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado del carrito',
                'cart' => $cartData
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting cart item: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar producto del carrito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function updateCartTotal($cart)
    {
        $cart->load('items');
        $cartData = $this->calculateCartTotals($cart);
        $cart->total = $cartData['total'];
        $cart->save();
    }

    private function calculateCartTotals($cart)
    {
        $subtotal = 0;
        $totalDiscount = 0;

        $items = $cart->items->map(function ($item) use (&$subtotal, &$totalDiscount) {
            $product = $item->product;
            $originalPrice = $product->old_price ?: $product->price;
            $itemSubtotal = $originalPrice * $item->quantity;
            $itemTotal = $item->unit_price * $item->quantity;
            $itemDiscount = $itemSubtotal - $itemTotal;

            $subtotal += $itemSubtotal;
            $totalDiscount += $itemDiscount;

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $itemTotal,
                'discount' => (float) $itemDiscount,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => (float) $product->price,
                    'discount_percentage' => (float) $product->discount_percentage,
                    'old_price' => $product->old_price ? (float) $product->old_price : null,
                    'brand' => $product->brand,
                    'image_url' => $product->image_url,
                    'category_id' => $product->category_id
                ]
            ];
        });

        return [
            'id' => $cart->id,
            'user_id' => $cart->user_id,
            'items' => $items,
            'subtotal' => (float) $subtotal,
            'discount' => (float) $totalDiscount,
            'total' => (float) ($subtotal - $totalDiscount)
        ];
    }
}