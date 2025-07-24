<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCreateSaleRequest;
use App\Http\Requests\AdminEditSaleRequest;
use App\Http\Requests\SalesReportRequest;
use App\Services\AdminSalesService;
use App\Services\DocumentExportService;
use App\Exports\SalesExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SalesController extends Controller
{
    protected AdminSalesService $salesService;
    protected DocumentExportService $exportService;

    public function __construct(AdminSalesService $salesService, DocumentExportService $exportService)
    {
        $this->salesService = $salesService;
        $this->exportService = $exportService;
    }

    public function create()
    {
        return view('admin.sales.create', [
            'products' => $this->salesService->getProducts()
        ]);
    }

    public function store(AdminCreateSaleRequest $request)
    {
        try {
            $validatedData = $request->validated();
            
            // Process items from the form
            $items = [];
            $formItems = $request->input('items', []);
            
            foreach ($formItems as $productId => $itemData) {
                $quantity = (int) ($itemData['quantity'] ?? 0);
                if ($quantity > 0) {
                    $items[] = [
                        'product_id' => (int) $itemData['product_id'],
                        'quantity' => $quantity
                    ];
                }
            }
            
            // Validate that at least one item is selected
            if (empty($items)) {
                return redirect()->back()
                    ->with('error', 'Please select at least one product.')
                    ->withInput();
            }
            
            $validatedData['items'] = $items;
            
            $order = $this->salesService->createSale($validatedData);
            return redirect()->route('admin.orders.receipt', $order->id)->with('success', 'Sale recorded successfully!');
        } catch (\Exception $e) {
            Log::error('Error creating sale: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error creating sale: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function index()
    {
        return view('admin.sales.index', [
            'sales' => $this->salesService->getAllSales()
        ]);
    }
    
    public function show($id)
    {
        return view('admin.sales.show', [
            'sale' => $this->salesService->getSale($id)
        ]);
    }
    
    public function edit($id)
    {
        $sale = $this->salesService->getSale($id);
        $products = $this->salesService->getProducts();
        
        // Prepare items data for JavaScript
        $itemsData = $sale->items->map(function($item) {
            return [
                'productId' => (int) $item->product_id,
                'name' => $item->product->name ?? '',
                'price' => (float) $item->unit_price,
                'quantity' => (int) $item->quantity,
                'subtotal' => (float) $item->subtotal,
            ];
        });
        
        return view('admin.sales.edit', [
            'sale' => $sale,
            'products' => $products,
            'itemsData' => $itemsData
        ]);
    }
    
    public function update(AdminEditSaleRequest $request, $id)
    {
        try {
            $validatedData = $request->validated();
            
            // Filter out items with quantity 0
            $items = [];
            foreach ($validatedData['items'] as $itemData) {
                if ((int) $itemData['quantity'] > 0) {
                    $items[] = [
                        'product_id' => (int) $itemData['product_id'],
                        'quantity' => (int) $itemData['quantity']
                    ];
                }
            }
            
            $validatedData['items'] = $items;
            
            $this->salesService->updateSale($id, $validatedData);
            return redirect()->route('admin.sales.index')->with('success', 'Sale updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating sale: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error updating sale: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    public function destroy($id)
    {
        $this->salesService->deleteSale($id);
        return redirect()->route('admin.sales.index')->with('success', 'Sale deleted!');
    }
    
    public function report(SalesReportRequest $request)
    {
        $validatedData = $request->validated();
        
        $type = $validatedData['type'] ?? 'all';
        $dateStart = $validatedData['date_start'] ?? null;
        $dateEnd = $validatedData['date_end'] ?? null;
        $productId = $validatedData['product_id'] ?? null;

        if ($request->has('export')) {
            $format = $request->input('export');
            $allReports = $this->salesService->getSalesReport($type, $dateStart, $dateEnd, $productId, false);

            if ($format === 'pdf') {
                $export = new SalesExport($allReports);
                return $this->exportService->download($export, 'sales-report.pdf');
            } elseif ($format === 'excel') {
                $export = new SalesExport($allReports);
                return $this->exportService->download($export, 'sales-report.xlsx');
            }

            return $this->exportCsv($allReports);
        }
        
        $paginatedReports = $this->salesService->getSalesReport($type, $dateStart, $dateEnd, $productId, true);
        $totalAmount = $this->salesService->getSalesReportTotal($type, $dateStart, $dateEnd, $productId);
        $products = $this->salesService->getProducts();

        return view('admin.sales.report', [
            'reports' => $paginatedReports,
            'type' => $type,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
            'totalAmount' => $totalAmount,
            'products' => $products,
            'productId' => $productId,
        ]);
    }
    
    private function exportCsv($reports)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sales-report.csv"',
        ];
        
        $callback = function() use ($reports) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Date', 'Customer', 'Product', 'Quantity', 'Amount', 'Payment Method', 'Status']);
            
            foreach ($reports as $report) {
                foreach ($report->items as $item) {
                    fputcsv($file, [
                        $report->created_at->format('Y-m-d'),
                        $report->getCustomerNameAttribute(),
                        $item->product ? $item->product->name : 'Unknown Product',
                        $item->quantity,
                        $item->subtotal,
                        $report->payment_method,
                        $report->status
                    ]);
                }
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
} 
