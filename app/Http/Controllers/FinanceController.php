<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\Accounting\AccountingService;
use Exception;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    // Chart of Accounts
    public function chartOfAccounts()
    {
        $accounts = Account::with('children.children')->whereNull('parent_id')->orderBy('code')->get();

        return view('finance.chart-of-accounts', compact('accounts'));
    }

    public function storeAccount(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:accounts,code|max:50',
            'name' => 'required|string|max:150',
            'type' => 'required|in:asset,liability,equity,income,expense',
            'level' => 'required|integer|in:1,2,3',
            'debit_credit_nature' => 'required|in:debit,credit',
        ]);

        Account::create(array_merge($request->only('code', 'name', 'type', 'parent_id', 'level', 'debit_credit_nature'), [
            'is_system' => false,
            'is_active' => true,
        ]));

        return back()->with('success', 'Account head added to Chart of Accounts!');
    }

    // Cash Receipts
    public function receipts()
    {
        $entries = JournalEntry::with('lines.account')
            ->where('voucher_type', 'receipt')
            ->latest()
            ->paginate(15);

        $accounts = Account::where('level', 3)->orderBy('name')->get();

        return view('finance.receipts', compact('entries', 'accounts'));
    }

    public function storeReceipt(Request $request, AccountingService $accountingService)
    {
        $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        try {
            $cashAcc = Account::where('code', '1110')->firstOrFail();
            $amount = (float) $request->amount;

            $accountingService->createEntry([
                'voucher_type' => 'receipt',
                'entry_date' => $request->entry_date ?? now()->toDateString(),
                'notes' => $request->description,
            ], [
                // Debit Cash
                ['account_id' => $cashAcc->id, 'debit' => $amount, 'credit' => 0.00, 'description' => $request->description],
                // Credit Source Account
                ['account_id' => $request->from_account_id, 'debit' => 0.00, 'credit' => $amount, 'description' => $request->description],
            ]);

            return back()->with('success', 'Cash Receipt Voucher created successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to create receipt: '.$e->getMessage());
        }
    }

    // Cash Payments
    public function payments()
    {
        $entries = JournalEntry::with('lines.account')
            ->where('voucher_type', 'payment')
            ->latest()
            ->paginate(15);

        $accounts = Account::where('level', 3)->orderBy('name')->get();

        return view('finance.payments', compact('entries', 'accounts'));
    }

    public function storePayment(Request $request, AccountingService $accountingService)
    {
        $request->validate([
            'expense_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        try {
            $cashAcc = Account::where('code', '1110')->firstOrFail();
            $amount = (float) $request->amount;

            $accountingService->createEntry([
                'voucher_type' => 'payment',
                'entry_date' => $request->entry_date ?? now()->toDateString(),
                'notes' => $request->description,
            ], [
                // Debit Expense Account
                ['account_id' => $request->expense_account_id, 'debit' => $amount, 'credit' => 0.00, 'description' => $request->description],
                // Credit Cash
                ['account_id' => $cashAcc->id, 'debit' => 0.00, 'credit' => $amount, 'description' => $request->description],
            ]);

            return back()->with('success', 'Cash Payment Voucher created successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to create payment: '.$e->getMessage());
        }
    }

    // Journal Vouchers
    public function vouchers()
    {
        $entries = JournalEntry::with('lines.account')
            ->latest()
            ->paginate(15);

        $accounts = Account::where('level', 3)->orderBy('name')->get();

        return view('finance.vouchers', compact('entries', 'accounts'));
    }

    public function storeVoucher(Request $request, AccountingService $accountingService)
    {
        $request->validate([
            'entry_date' => 'required|date',
            'notes' => 'required|string|max:255',
            'debit_account_id' => 'required|exists:accounts,id',
            'credit_account_id' => 'required|exists:accounts,id|different:debit_account_id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $amount = (float) $request->amount;
            $accountingService->createEntry([
                'voucher_type' => 'journal',
                'entry_date' => $request->entry_date,
                'notes' => $request->notes,
            ], [
                ['account_id' => $request->debit_account_id, 'debit' => $amount, 'credit' => 0.00, 'description' => $request->notes],
                ['account_id' => $request->credit_account_id, 'debit' => 0.00, 'credit' => $amount, 'description' => $request->notes],
            ]);

            return back()->with('success', 'Journal Voucher created successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to create journal voucher: '.$e->getMessage());
        }
    }

    // General Ledger / Account Ledger
    public function ledger(Request $request)
    {
        $accounts = Account::where('level', 3)->orderBy('code')->get();
        $selectedAccountId = $request->account_id ?? $accounts->first()?->id;

        $selectedAccount = $selectedAccountId ? Account::find($selectedAccountId) : null;

        $linesQuery = JournalEntryLine::with(['entry', 'account'])
            ->where('account_id', $selectedAccountId)
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->select('journal_entry_lines.*');

        if ($request->filled('from_date')) {
            $linesQuery->where('journal_entries.entry_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $linesQuery->where('journal_entries.entry_date', '<=', $request->to_date);
        }

        $lines = $linesQuery->get();

        return view('finance.ledger', compact('accounts', 'selectedAccount', 'lines'));
    }

    // Trial Balance
    public function trialBalance()
    {
        $accounts = Account::where('level', 3)->orderBy('code')->get();
        $totalDebit = 0.00;
        $totalCredit = 0.00;

        foreach ($accounts as $acc) {
            $debitSum = (float) $acc->journalLines()->sum('debit');
            $creditSum = (float) $acc->journalLines()->sum('credit');

            if ($acc->debit_credit_nature === 'debit') {
                $bal = $debitSum - $creditSum;
                $acc->calc_debit = max(0, $bal);
                $acc->calc_credit = max(0, -$bal);
            } else {
                $bal = $creditSum - $debitSum;
                $acc->calc_credit = max(0, $bal);
                $acc->calc_debit = max(0, -$bal);
            }

            $totalDebit += $acc->calc_debit;
            $totalCredit += $acc->calc_credit;
        }

        return view('finance.trial-balance', compact('accounts', 'totalDebit', 'totalCredit'));
    }
}
