<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentController extends Controller
{
    public function receipt(Payment $payment)
    {
        $payment->load(['member', 'branch', 'allocations.invoice']);

        return Pdf::loadView('pdf.receipt', [
            'payment' => $payment,
            'gym' => $this->gym($payment->branch),
        ])->stream("receipt-{$payment->number}.pdf");
    }

    public function invoice(Invoice $invoice)
    {
        $invoice->load(['member', 'branch', 'items']);

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'gym' => $this->gym($invoice->branch),
        ])->stream("invoice-{$invoice->number}.pdf");
    }

    private function gym(?\App\Models\Branch $branch): array
    {
        return [
            'name' => config('gym.name'),
            'branch' => $branch?->name,
            'address' => $branch?->address,
            'phone' => $branch?->phone,
            'currency_symbol' => config('gym.currency_symbol', 'Rs'),
            'tax_label' => config('gym.tax.label', 'VAT'),
        ];
    }
}
