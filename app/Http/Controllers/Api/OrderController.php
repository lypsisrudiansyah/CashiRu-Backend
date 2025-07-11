<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('order_items.product')->get();
        return response()->json([
            'data' => $orders,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'cashier_id' => 'required',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = Order::create([
            'transaction_number' => 'TRX-' . strtoupper(uniqid()),
            'cashier_id' => $validatedData['cashier_id'],
            'total' => collect($validatedData['items'])->sum(function ($item) {
                return Product::find($item['product_id'])->price * $item['quantity'];
            }),
            'total_quantity' => collect($validatedData['items'])->sum('quantity'),
            'payment_method' => $request->input('payment_method', 'cash'), // Default set to 'cash'
        ]);

        foreach ($validatedData['items'] as $item) {
            $product = Product::find($item['product_id']);
            $order->order_items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'total_item' => $product->price * $item['quantity'],
                'product_price' => $product->price,
            ]);
        }

        return response()->json([
            'message' => 'Order created successfully',
            'data' => $order->load('order_items.product'),
        ], 201);
    }
}
