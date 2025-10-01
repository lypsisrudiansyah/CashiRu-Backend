<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function summary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|after_or_equal:start_date|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        // $start_date = Carbon::parse($request->start_date)->startOfDay();
        $start_date = date('Y-m-d 00:00:00', strtotime($request->start_date));
        $end_date = date('Y-m-d 23:59:59', strtotime($request->end_date));

        $orders = Order::whereBetween('created_at', [$start_date, $end_date])->get();

        $totalRevenue = $orders->sum('total');

        $totalSellingQuantity = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$start_date, $end_date])
            ->sum('order_items.quantity');

        $data = [
            'total_revenue' => $totalRevenue,
            'total_sold_quantity' => $totalSellingQuantity,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function productSales(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $start_date = date('Y-m-d 00:00:00', strtotime($request->start_date));
        $end_date = date('Y-m-d 23:59:59', strtotime($request->end_date));
        // dd($start_date, $end_date);
        $totalProductSold = OrderItem::select(
            'products.id as product_id',
            'products.name as product_name',
            'products.price as product_price',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.total_item) as total_item')
        )->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween(DB::raw('(order_items.created_at)'), [$start_date, $end_date])
            ->groupBy('products.id', 'products.name', 'products.price')
            ->orderBy('total_quantity', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $totalProductSold
        ]);
    }
}
