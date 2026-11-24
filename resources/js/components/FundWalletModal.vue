<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const presets = [
    { amount: 500, label: '₦500', note: '5 top-up cycles' },
    { amount: 1000, label: '₦1,000', note: '10 cycles (~3 yrs for 1 SIM)' },
    { amount: 2500, label: '₦2,500', note: '25 cycles' },
    { amount: 5000, label: '₦5,000', note: '50 cycles (Multi-SIM)' },
];

const form = useForm({
    amount_naira: 1000,
});

const estimatedCycles = computed(() => Math.floor((form.amount_naira || 0) / 100));

const submit = () => {
    form.post('/wallet/fund');
};
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs"
        @click.self="emit('close')"
    >
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Fund SIM Vault Wallet</h2>
                    <p class="text-xs text-slate-500">
                        Pre-fund automated ₦50 airtime keep-alive top-ups via Paystack.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    @click="emit('close')"
                >
                    ✕
                </button>
            </div>

            <form class="mt-5 space-y-5" @submit.prevent="submit">
                <!-- Quick Amount Chips -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Quick Select</label>
                    <div class="mt-2 grid grid-cols-2 gap-2.5">
                        <button
                            v-for="preset in presets"
                            :key="preset.amount"
                            type="button"
                            class="rounded-xl border p-3 text-left transition"
                            :class="
                                form.amount_naira === preset.amount
                                    ? 'border-emerald-600 bg-emerald-50/70 ring-1 ring-emerald-600'
                                    : 'border-slate-200 hover:bg-slate-50'
                            "
                            @click="form.amount_naira = preset.amount"
                        >
                            <div class="font-mono text-sm font-extrabold text-slate-900">
                                {{ preset.label }}
                            </div>
                            <div class="mt-0.5 text-[11px] text-slate-500">{{ preset.note }}</div>
                        </button>
                    </div>
                </div>

                <!-- Custom Naira Input -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700">
                        Amount in Naira (Minimum ₦500)
                    </label>
                    <div class="relative mt-1.5">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center font-mono text-sm font-bold text-slate-400"
                        >
                            ₦
                        </span>
                        <input
                            v-model.number="form.amount_naira"
                            type="number"
                            min="500"
                            max="500000"
                            step="100"
                            required
                            class="w-full rounded-xl border border-slate-300 py-2.5 pr-4 pl-8 font-mono text-sm font-bold text-slate-900 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none"
                        />
                    </div>
                    <p v-if="form.errors.amount_naira" class="mt-1 text-xs text-red-600">
                        {{ form.errors.amount_naira }}
                    </p>
                    <p class="mt-1.5 text-[11px] text-slate-500">
                        Covers <strong>{{ estimatedCycles }}</strong> automated keep-alive top-ups (₦50 airtime + ₦50 service fee each).
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                        @click="emit('close')"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Connecting to Paystack...' : 'Continue to Paystack' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>