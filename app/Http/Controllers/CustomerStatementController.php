<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerStatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CustomerStatementController extends Controller
{
    /**
     * Display a printer-friendly version of the customer statement.
     */
    public function printStatement(Request $request, Customer $customer, CustomerStatementService $statementService)
    {
        Gate::authorize('view', $customer);

        $lines = $statementService->getStatement($customer);
        $summary = $statementService->getSummary($customer);

        return view('customers.print-statement', [
            'customer' => $customer,
            'lines' => $lines,
            'summary' => $summary,
        ]);
    }
}
