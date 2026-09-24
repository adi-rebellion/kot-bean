<?php

namespace App\Http\Controllers;

use App\Models\Kot;
use App\Models\Order;
use Illuminate\View\View;

class PrintController extends Controller
{
    public function invoice(Order $order): View
    {
        $order->load(['items', 'payments', 'restaurant', 'table', 'promotion']);

        return view('print.invoice', compact('order'));
    }

    public function kot(Kot $kot): View
    {
        $kot->load(['items', 'table', 'order']);

        return view('print.kot', compact('kot'));
    }
}
