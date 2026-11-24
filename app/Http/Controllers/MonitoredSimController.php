<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMonitoredSimRequest;
use App\Models\MonitoredSim;
use App\Models\WalletTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MonitoredSimController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        $sims = $user->monitoredSims()
            ->with('bankLinks')
            ->latest()
            ->get()
            ->map(fn (MonitoredSim $sim) => [
                'id' => $sim->id,
                'label' => $sim->label,
                'phone_number' => $sim->phone_number,
                'local_number' => $sim->local_number,
                'telco' => $sim->telco,
                'mode' => $sim->mode,
                'status' => $sim->status,
                'days_remaining' => $sim->days_remaining,
                'next_rga_due_at' => $sim->next_rga_due_at?->format('M j, Y'),
                'last_probe_at' => $sim->last_probe_at?->diffForHumans(),
                'bank_links' => $sim->bankLinks,
            ]);

        $transactions = $wallet->transactions()
            ->latest()
            ->take(10)
            ->get()
            ->map(fn (WalletTransaction $tx) => [
                'id' => $tx->id,
                'reference' => $tx->reference,
                'type' => $tx->type,
                'amount_kobo' => $tx->amount_kobo,
                'description' => $tx->description,
                'status' => $tx->status,
                'created_at' => $tx->created_at->format('M j, Y · g:i A'),
            ]);

        return Inertia::render('Dashboard', [
            'sims' => $sims,
            'availableBanks' => config('nigerian_banks'),
            'hasEmergencyPhone' => filled($user->emergency_phone),
            'wallet' => [
                'balance_kobo' => $wallet->balance_kobo,
                'topup_cost_kobo' => 10000,
            ],
            'transactions' => $transactions,
        ]);
    }

    public function store(StoreMonitoredSimRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated) {
            $isKeepAlive = $validated['mode'] === 'keep_alive';
            $daysElapsed = (int) ($validated['last_used_days_ago'] ?? 0);
            $daysUntilDue = max(1, 120 - $daysElapsed);

            $sim = $request->user()->monitoredSims()->create([
                'label' => $validated['label'] ?? null,
                'phone_number' => $validated['phone_number'],
                'telco' => $validated['telco'],
                'mode' => $validated['mode'],
                'status' => $isKeepAlive ? 'active' : 'dormant',
                'next_rga_due_at' => $isKeepAlive ? now()->addDays($daysUntilDue) : null,
            ]);

            $links = collect($validated['bank_keys'])
                ->unique()
                ->map(fn (string $key) => ['bank_key' => $key])
                ->all();

            $sim->bankLinks()->createMany($links);
        });

        return back()->with('success', 'SIM added to your vault.');
    }

    public function toggleMode(Request $request, MonitoredSim $sim): RedirectResponse
    {
        abort_unless($sim->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'mode' => ['required', Rule::in(['keep_alive', 'watchdog'])],
        ]);

        $isKeepAlive = $data['mode'] === 'keep_alive';

        $sim->update([
            'mode' => $data['mode'],
            'status' => $isKeepAlive ? 'active' : 'dormant',
            'next_rga_due_at' => $isKeepAlive ? now()->addDays(120) : null,
        ]);

        return back()->with('success', 'Monitoring mode updated.');
    }

    public function destroy(Request $request, MonitoredSim $sim): RedirectResponse
    {
        abort_unless($sim->user_id === $request->user()->id, 403);

        $sim->delete();

        return back()->with('success', 'SIM removed from vault.');
    }
}