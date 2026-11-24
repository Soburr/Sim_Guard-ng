<script setup lang="ts">
import { computed, ref } from 'vue';
import type { WalletSummary, WalletTransactionItem } from '@/types/simguard';

const props = defineProps<{
    wallet: WalletSummary;
    transactions: WalletTransactionItem[];
    keepAliveCount: number;
}>();

const emit = defineEmits<{
    (e: 'fund'): void;
}>();

const showLedger = ref(false);

const formatNaira = (kobo: number): string =>
    new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(kobo / 100);

const requiredNextCycleKobo = computed(() => props.keepAliveCount * props.wallet.topup_cost_kobo);

const isLowBalance = computed(
    () => props.keepAliveCount > 0 && props.wallet.balance_kobo < requiredNextCycleKobo.value,
);

const cyclesAvailable = computed(() =>
    Math.floor(props.wallet.balance_kobo / (props.wallet.topup_cost_kobo || 10000)),
);
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold tracking-wide text-slate-500 uppercase">
                        Keep-Alive Wallet Balance
                    </span>
                    <span
                        v-if="isLowBalance"
                        class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-800"
                    >
                        Low Balance
                    </span>
                </div>

                <div class="mt-1 flex items-baseline gap-3">
                    <span class="font-mono text-3xl font-extrabold tracking-tight text-slate-900">
                        {{ formatNaira(wallet.balance_kobo) }}
                    </span>
                    <span class="text-xs text-slate-500">
                        ({{ cyclesAvailable }} top-up {{ cyclesAvailable === 1 ? 'cycle' : 'cycles' }} ready)
                    </span>
                </div>

                <p v-if="isLowBalance" class="mt-1.5 text-xs text-amber-800">
                    You have {{ keepAliveCount }} active SIM(s) requiring
                    <strong>{{ formatNaira(requiredNextCycleKobo) }}</strong> for the next renewal cycle.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <button
                    v-if="transactions.length > 0"
                    type="button"
                    class="rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                    @click="showLedger = !showLedger"
                >
                    {{ showLedger ? 'Hide History' : `History (${transactions.length})` }}
                </button>

                <button
                    type="button"
                    class="rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-slate-800"
                    @click="emit('fund')"
                >
                    + Fund Wallet
                </button>
            </div>
        </div>

        <!-- Expandable Transaction Ledger -->
        <div v-if="showLedger && transactions.length > 0" class="mt-5 border-t border-slate-100 pt-4">
            <h4 class="text-xs font-bold tracking-wide text-slate-400 uppercase">
                Recent Wallet Activity
            </h4>

            <div class="mt-3 divide-y divide-slate-100">
                <div
                    v-for="tx in transactions"
                    :key="tx.id"
                    class="flex items-center justify-between gap-3 py-2.5 text-xs"
                >
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-800">{{ tx.description }}</p>
                        <p class="mt-0.5 font-mono text-[11px] text-slate-400">
                            {{ tx.created_at }} · {{ tx.reference }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-2.5">
                        <span
                            class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                            :class="{
                                'bg-emerald-50 text-emerald-700': tx.status === 'success',
                                'bg-amber-50 text-amber-700': tx.status === 'pending',
                                'bg-red-50 text-red-700': tx.status === 'failed',
                            }"
                        >
                            {{ tx.status }}
                        </span>

                        <span
                            class="font-mono font-bold"
                            :class="tx.type === 'credit' ? 'text-emerald-700' : 'text-slate-900'"
                        >
                            {{ tx.type === 'credit' ? '+' : '-' }}{{ formatNaira(tx.amount_kobo) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>