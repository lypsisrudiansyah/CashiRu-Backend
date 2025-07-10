<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
}
