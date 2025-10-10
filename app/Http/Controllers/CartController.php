<?php
namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /* ---- carrito del usuario ---- */
    public function index()
    {
        $cart = Cart::with('items.product')
                    ->where('user_id', auth()->id())
                    ->first();
        if (!$cart) {
            return response()->json(['success' => true, 'cart' => ['items' => [], 'subtotal' => 0, 'discount' => 0, 'total' => 0]]);
        }
        return response()->json(['success' => true, 'cart' => $this->calculateTotals($cart)]);
    }

    /* ---- agregar / incrementar ---- */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1'
        ]);
        $product = Product::findOrFail($request->product_id);
        if ($product->active === false) {
            return response()->json(['success' => false, 'message' => 'Producto no disponible'], 422);
        }

        $cart = Cart::firstOrCreate(['user_id' => auth()->id()]);

        $item = CartItem::where('cart_id', $cart->id)
                        ->where('product_id', $request->product_id)
                        ->first();

        $newQty = $item ? $item->quantity + $request->quantity : $request->quantity;

        // stock check
        if (($product->stock ?? 0) < $newQty) {
            return response()->json(['success' => false, 'message' => 'Stock insuficiente'], 422);
        }

        $unitPrice = $product->getFinalPriceAttribute(); // precio con descuento
        $lineDisc  = $product->old_price && $product->discount_percentage > 0
            ? ($product->old_price - $unitPrice) * $newQty
            : 0;

        if ($item) {
            $item->quantity   = $newQty;
            $item->discount   = $lineDisc;
            $item->unit_price = $unitPrice;
            $item->save();
        } else {
            CartItem::create([
                'cart_id'    => $cart->id,
                'product_id' => $request->product_id,
                'quantity'   => $request->quantity,
                'unit_price' => $unitPrice,
                'discount'   => $lineDisc
            ]);
        }

        $this->updateCartTotal($cart);
        return response()->json([
            'success' => true,
            'message' => 'Producto agregado al carrito',
            'cart'    => $this->calculateTotals($cart->fresh(['items.product']))
        ]);
    }

    /* ---- cambiar cantidad ---- */
    public function update(Request $request, $id)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);
        $item = CartItem::whereHas('cart', fn($q) => $q->where('user_id', auth()->id()))
                        ->findOrFail($id);

        $product = $item->product;
        if (($product->stock ?? 0) < $request->quantity) {
            return response()->json(['success' => false, 'message' => 'Stock insuficiente'], 422);
        }

        $unitPrice = $product->getFinalPriceAttribute();
        $lineDisc  = $product->old_price && $product->discount_percentage > 0
            ? ($product->old_price - $unitPrice) * $request->quantity
            : 0;

        $item->quantity   = $request->quantity;
        $item->unit_price = $unitPrice;
        $item->discount   = $lineDisc;
        $item->save();

        $this->updateCartTotal($item->cart);
        return response()->json([
            'success' => true,
            'message' => 'Cantidad actualizada',
            'cart'    => $this->calculateTotals($item->cart->fresh(['items.product']))
        ]);
    }

    /* ---- eliminar línea ---- */
    public function remove($id)
    {
        $item = CartItem::whereHas('cart', fn($q) => $q->where('user_id', auth()->id()))
                        ->findOrFail($id);
        $cart = $item->cart;
        $item->delete();
        $this->updateCartTotal($cart);
        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado',
            'cart'    => $this->calculateTotals($cart->fresh(['items.product']))
        ]);
    }

    /* ---- vaciar carrito ---- */
    public function clear()
    {
        $cart = Cart::where('user_id', auth()->id())->first();
        if ($cart) {
            $cart->items()->delete();
            $cart->update(['total' => 0]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Carrito vaciado',
            'cart'    => ['items' => [], 'subtotal' => 0, 'discount' => 0, 'total' => 0]
        ]);
    }

    /* ---- finalizar compra ---- */
    public function checkout(Request $request)
    {
        $cart = Cart::with('items.product')
                    ->where('user_id', auth()->id())
                    ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Debe agregar productos antes de finalizar la compra'
            ], 422);
        }

        $request->validate([
            'shipping_address' => 'required|array',
            'payment_method'   => 'required|string|max:50'
        ]);

        DB::beginTransaction();
        try {
            $totals = $this->calculateTotals($cart);

            $order = Order::create([
                'user_id'           => auth()->id(),
                'subtotal'          => $totals['subtotal'],
                'discount_total'    => $totals['discount'],
                'total'             => $totals['total'],
                'status'            => 'paid',
                'shipping_address'  => $request->shipping_address
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id'    => $order->id,
                    'product_id'  => $item->product_id,
                    'quantity'    => $item->quantity,
                    'unit_price'  => $item->unit_price,
                    'discount'    => $item->discount
                ]);
                // descontar stock
                $item->product->decrement('stock', $item->quantity);
            }

            $cart->items()->delete();
            $cart->update(['total' => 0]);
            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Compra realizada con éxito',
                'order_id' => $order->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Checkout: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la compra',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /* -------- helpers -------- */
    private function calculateTotals($cart)
    {
        $sub = 0;
        $disc = 0;
        foreach ($cart->items as $it) {
            $sub += $it->unit_price * $it->quantity;
            $disc += $it->discount;
        }
        return [
            'items'    => $cart->items,
            'subtotal' => round($sub, 2),
            'discount' => round($disc, 2),
            'total'    => round($sub - $disc, 2)
        ];
    }

    private function updateCartTotal($cart)
    {
        $t = $this->calculateTotals($cart);
        $cart->update(['total' => $t['total']]);
    }
}