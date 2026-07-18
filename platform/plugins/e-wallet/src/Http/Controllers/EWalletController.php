<?php

namespace Botble\EWallet\Http\Controllers;

use Botble\EWallet\Enums\WithdrawalStatusEnum;
use Botble\EWallet\Helpers\WalletHelper;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTransaction;
use Botble\EWallet\Models\Withdrawal;
use Illuminate\Support\Facades\DB;

class EWalletController extends BaseWalletController
{
    public function index(WalletHelper $walletHelper)
    {
        $this->pageTitle(trans('plugins/e-wallet::e-wallet.reports.page_title'));

        $defaultCurrencyCode = $walletHelper->getDefaultCurrency();
        $defaultCurrency = get_all_currencies()->firstWhere('title', $defaultCurrencyCode);

        $totalWallets = Wallet::query()->count();
        $activeWallets = Wallet::query()->where('balance', '>', 0)->count();

        $totalCredits = WalletTransaction::query()
            ->where('amount', '>', 0)
            ->sum('amount');

        $totalDebits = abs(WalletTransaction::query()
            ->where('amount', '<', 0)
            ->sum('amount'));

        $totalBalanceInCirculation = Wallet::query()->sum('balance');

        $topWallets = Wallet::query()
            ->with('customer:id,name,email')
            ->where('balance', '>', 0)
            ->orderByDesc('balance')
            ->limit(10)
            ->get();

        $recentTransactions = WalletTransaction::query()
            ->with(['customer:id,name,email'])
            ->latest('created_at')
            ->limit(20)
            ->get();

        $transactionsByType = WalletTransaction::query()
            ->select('type', DB::raw('SUM(ABS(amount)) as total_amount'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $dailyActivity = WalletTransaction::query()
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as credited'),
                DB::raw('SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as debited')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        $pendingWithdrawals = Withdrawal::query()
            ->where('status', WithdrawalStatusEnum::PENDING)
            ->count();

        $pendingWithdrawalsAmount = Withdrawal::query()
            ->where('status', WithdrawalStatusEnum::PENDING)
            ->sum('amount');

        $completedWithdrawalsAmount = Withdrawal::query()
            ->where('status', WithdrawalStatusEnum::COMPLETED)
            ->sum('amount');

        $recentPendingWithdrawals = Withdrawal::query()
            ->with('customer:id,name,email')
            ->where('status', WithdrawalStatusEnum::PENDING)
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('plugins/e-wallet::reports.index', compact(
            'totalWallets',
            'activeWallets',
            'totalCredits',
            'totalDebits',
            'totalBalanceInCirculation',
            'topWallets',
            'recentTransactions',
            'transactionsByType',
            'dailyActivity',
            'pendingWithdrawals',
            'pendingWithdrawalsAmount',
            'completedWithdrawalsAmount',
            'recentPendingWithdrawals',
            'defaultCurrency'
        ));
    }
}
