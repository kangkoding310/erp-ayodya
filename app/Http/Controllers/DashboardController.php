<?php

namespace App\Http\Controllers;

use App\Models\AccountingBill;
use App\Models\ExpenseReport;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $userId = Auth::id();

        return Inertia::render('Dashboard/Index', [
            'summary' => [
                'my_purchase_requests' => PurchaseRequest::where('requested_by', $userId)->count(),
                'pending_my_approval' => PurchaseRequest::whereHas(
                    'approvals',
                    fn ($q) => $q->where('approver_id', $userId)->where('status', 'pending')
                )->count(),
                'total_outstanding_bills' => AccountingBill::where('status', 'unpaid')->sum('amount'),
                'purchase_requests_in_rfq' => PurchaseRequest::where('status', 'in_rfq')->count(),
            ],
            'monthlyPurchaseTotals' => PurchaseRequest::query()
                ->selectRaw("to_char(created_at, 'YYYY-MM') as month, sum(total_amount) as total")
                ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
            'monthlyExpenseTotals' => ExpenseReport::query()
                ->selectRaw("to_char(created_at, 'YYYY-MM') as month, sum(total_expense) as total")
                ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
        ]);
    }
}
