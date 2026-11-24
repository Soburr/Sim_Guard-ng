<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AddSimModal from '@/components/AddSimModal.vue';
import FundWalletModal from '@/components/FundWalletModal.vue';
import SimVaultCard from '@/components/SimVaultCard.vue';
import WalletOverviewCard from '@/components/WalletOverviewCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    BankConfig,
    MonitoredSim,
    WalletSummary,
    WalletTransactionItem,
} from '@/types/simguard';

const props = withDefaults(
    defineProps<{
        sims?: MonitoredSim[];
        availableBanks?: Record<string, BankConfig>;
        hasEmergencyPhone?: boolean;
        wallet?: WalletSummary;
        transactions?: WalletTransactionItem[];
    }>(),
    {
        sims: () => [],
        availableBanks: () => ({}),
        hasEmergencyPhone: false,
        wallet: () => ({ balance_kobo: 0, topup_cost_kobo: 10000 }),
        transactions: () => [],
    },
);

const page = usePage();
const flash = computed(
    () => (page.props.flash as { success?: string; error?: string } | undefined) ?? {},
);

const showAddModal = ref(false);
const showFundModal = ref(false);

const keepAliveCount = computed(() => props.sims.filter((s) => s.mode === 'keep_alive').length);
const watchdogCount = computed(() => props.sims.filter((s) => s.mode === 'watchdog').length);
const compromisedCount = computed(
    () => props.sims.filter((s) => s.status === 'compromised_woken_up').length,
);
</script>

<template>
    <Head title="SIM Vault Dashboard" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6">
            <!-- Flash Notifications (Paystack Callback / SIM Actions) -->
            <div
                v-if="flash.success"
                class="rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-900"
            >
                {{ flash.success }}
            </div>
            <div
                v-if="flash.error"
                class="rounded-2xl border border-red-300 bg-red-50 px-4 py-3 text-xs font-semibold text-red-900"
            >
                {{ flash.error }}
            </div>

            <!-- Missing Emergency Phone Warning -->
            <div
                v-if="!hasEmergencyPhone"
                class="flex flex-col items-start justify-between gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-4 sm:flex-row sm:items-center"
            >
                <div class="text-xs text-amber-950">
                    <span class="font-bold">Action Required:</span> You haven't set an active emergency
                    WhatsApp/SMS number yet. We need a working line to alert you if a monitored SIM wakes up.
                </div>
                <Link
                    href="/settings/profile"
                    class="shrink-0 rounded-xl bg-amber-900 px-3.5 py-2 text-xs font-bold text-white hover:bg-amber-950"
                >
                    Set Alert Number
                </Link>
            </div>

            <!-- Page Header -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">SIM Vault</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Keep spare Nigerian SIMs alive and arm one-tap USSD kill-switches for linked banks.
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700"
                    @click="showAddModal = true"
                >
                    + Add SIM Card
                </button>
            </div>

            <!-- Wallet Overview & Ledger -->
            <WalletOverviewCard
                :wallet="wallet"
                :transactions="transactions"
                :keep-alive-count="keepAliveCount"
                @fund="showFundModal = true"
            />

            <!-- KPI Summary Strip -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-medium text-slate-500">Keep-Alive Protected</p>
                    <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ keepAliveCount }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-medium text-slate-500">Dormancy Watchdogs</p>
                    <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ watchdogCount }}</p>
                </div>
                <div
                    class="rounded-2xl border p-4"
                    :class="
                        compromisedCount > 0
                            ? 'border-red-300 bg-red-50'
                            : 'border-slate-200 bg-white'
                    "
                >
                    <p
                        class="text-xs font-medium"
                        :class="compromisedCount > 0 ? 'text-red-700' : 'text-slate-500'"
                    >
                        Wake-Up Alerts
                    </p>
                    <p
                        class="mt-1 text-2xl font-extrabold"
                        :class="compromisedCount > 0 ? 'text-red-700' : 'text-slate-900'"
                    >
                        {{ compromisedCount }}
                    </p>
                </div>
            </div>

            <!-- SIM Grid or Empty State -->
            <div v-if="sims.length > 0" class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                <SimVaultCard v-for="sim in sims" :key="sim.id" :sim="sim" />
            </div>

            <div
                v-else
                class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center"
            >
                <h3 class="text-base font-bold text-slate-900">No SIMs in your vault yet</h3>
                <p class="mx-auto mt-1 max-w-md text-xs leading-relaxed text-slate-500">
                    Add any spare MTN, Airtel, Glo, or 9mobile line tied to your bank accounts. We will
                    keep it from expiring or watch it for unauthorized reactivation.
                </p>
                <button
                    type="button"
                    class="mt-5 rounded-xl bg-slate-900 px-5 py-2.5 text-xs font-bold text-white hover:bg-slate-800"
                    @click="showAddModal = true"
                >
                    Add Your First SIM
                </button>
            </div>
        </div>

        <AddSimModal
            :open="showAddModal"
            :available-banks="availableBanks"
            @close="showAddModal = false"
        />

        <FundWalletModal :open="showFundModal" @close="showFundModal = false" />
    </AppLayout>
</template>