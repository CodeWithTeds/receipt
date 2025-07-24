<?php

namespace App\Exports;

class SalesExport
{
    protected $sales;

    public function __construct($sales)
    {
        $this->sales = $sales;
    }

    public function collection()
    {
        return $this->sales;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Customer',
            'Product',
            'Quantity',
            'Amount',
            'Payment Method',
            'Status'
        ];
    }

    public function map($sale): array
    {
        $rows = [];
        if ($sale->items->isNotEmpty()) {
            foreach ($sale->items as $item) {
                $rows[] = [
                    $sale->created_at->format('Y-m-d'),
                    $sale->getCustomerNameAttribute(),
                    $item->product ? $item->product->name : 'Unknown Product',
                    $item->quantity,
                    $item->subtotal,
                    $sale->payment_method,
                    $sale->status
                ];
            }
        } else {
            $rows[] = [
                $sale->created_at->format('Y-m-d'),
                $sale->getCustomerNameAttribute(),
                'No items',
                0,
                0,
                $sale->payment_method,
                $sale->status
            ];
        }
        
        // This is a bit of a hack since map is expected to return a single row.
        // We'll return the first row, but this class should be re-evaluated
        // if a sale can ever be represented by more than one row in the export.
        return $rows[0] ?? [];
    }
    
    public function title(): string
    {
        return 'Sales Report';
    }
} 
