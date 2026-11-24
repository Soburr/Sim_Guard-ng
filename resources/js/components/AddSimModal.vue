<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import type { BankConfig, TelcoSlug } from '@/types/simguard';

const props = defineProps<{
    open: boolean;
    availableBanks: Record<string, BankConfig>;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const telcos: { slug: TelcoSlug; name: string }[] = [
    { slug: 'mtn', name: 'MTN' },
    { slug: 'airtel', name: 'Airtel' },
    { slug: 'glo', name: 'Glo' },
    { slug: '9mobile', name: '9mobile' },
];

const form = useForm({
    label: '',
    phone_number: '',
    telco: 'mtn' as TelcoSlug,
    mode: 'keep_alive' as 'keep_alive' | 'watchdog',
    last_used_days_ago: 0,
    bank_keys: [] as string[],
});

// Auto-detect carrier from Nigerian prefix while typing
watch(
    () => form.phone_number,
    (val) => {
        const digits = val.replace(/\D/g, '');
        const local = digits.startsWith('234') ? '0' + digits.slice(3) : digits;
        const prefix = local.slice(0, 4);

        if (['0803', '0806', '0703', '0706', '0813', '0816', '0810', '0814', '0903', '0906', '0913', '0916'].includes(prefix)) {
            form.telco = 'mtn';
        } else if (['0802', '0808', '0708', '0812', '0701', '0902', '0901', '0904', '0907', '0912', '0911'].includes(prefix)) {
            form.telco = 'airtel';
        } else if (['0805', '0807', '0705', '0815', '0811', '0905', '0915'].includes(prefix)) {
            form.telco = 'glo';
        } else if (['0809', '0818', '0817', '0909', '0908'].includes(prefix)) {
            form.telco = '9mobile';
        }
    },
);

const toggleBank = (key: string) => {
    const idx = form.bank_keys.indexOf(key);
    if (idx === -1) {
        form.bank_keys.push(key);
    } else {
        form.bank_keys.splice(idx, 1);
    }
};

const submit = () => {
    form.post('/sims', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
};
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-xs"
        @click.self="emit('close')"
    >
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Protect a SIM Card</h2>
                    <p class="text-xs text-slate-500">
                        Prevent telco recycling or monitor a dead line for reactivation.
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
                <!-- Phone Number & Nickname -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">
                            Nigerian Phone Number *
                        </label>
                        <input
                            v-model="form.phone_number"
                            type="tel"
                            placeholder="0803 123 4567"
                            required
                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none"
                        />
                        <p v-if="form.errors.phone_number" class="mt-1 text-xs text-red-600">
                            {{ form.errors.phone_number }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">
                            SIM Nickname (Optional)
                        </label>
                        <input
                            v-model="form.label"
                            type="text"
                            placeholder="e.g. Old Zenith Line"
                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Network / Telco Selector -->
                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-slate-700">Network Operator</label>
                        <span class="text-[11px] text-slate-400">Override if ported (MNP)</span>
                    </div>
                    <div class="mt-1.5 grid grid-cols-4 gap-2">
                        <button
                            v-for="t in telcos"
                            :key="t.slug"
                            type="button"
                            class="rounded-xl border py-2 text-xs font-bold transition"
                            :class="
                                form.telco === t.slug
                                    ? 'border-slate-900 bg-slate-900 text-white'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                            "
                            @click="form.telco = t.slug"
                        >
                            {{ t.name }}
                        </button>
                    </div>
                </div>

                <!-- Protection Mode Selector -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Protection Mode</label>
                    <div class="mt-1.5 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        <button
                            type="button"
                            class="rounded-xl border p-3.5 text-left transition"
                            :class="
                                form.mode === 'keep_alive'
                                    ? 'border-emerald-600 bg-emerald-50/60 ring-1 ring-emerald-600'
                                    : 'border-slate-200 hover:bg-slate-50'
                            "
                            @click="form.mode = 'keep_alive'"
                        >
                            <div class="text-xs font-bold text-slate-900">Keep-Alive (₦50 / 120d)</div>
                            <p class="mt-1 text-[11px] leading-relaxed text-slate-600">
                                SIM still works. Auto-buy ₦50 airtime every 4 months so MTN/Airtel never recycles it.
                            </p>
                        </button>

                        <button
                            type="button"
                            class="rounded-xl border p-3.5 text-left transition"
                            :class="
                                form.mode === 'watchdog'
                                    ? 'border-amber-600 bg-amber-50/60 ring-1 ring-amber-600'
                                    : 'border-slate-200 hover:bg-slate-50'
                            "
                            @click="form.mode = 'watchdog'"
                        >
                            <div class="text-xs font-bold text-slate-900">Dormancy Watchdog</div>
                            <p class="mt-1 text-[11px] leading-relaxed text-slate-600">
                                SIM is lost or already dead. Alert me via WhatsApp the exact day a stranger activates it.
                            </p>
                        </button>
                    </div>
                </div>

                <!-- Days Elapsed Buffer (Keep-Alive Only) -->
                <div v-if="form.mode === 'keep_alive'" class="rounded-xl bg-slate-50 p-3.5">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-slate-700">
                            Days since you last used this SIM
                        </label>
                        <span class="font-mono text-xs font-bold text-emerald-700">
                            {{ form.last_used_days_ago }} days ago
                        </span>
                    </div>
                    <input
                        v-model.number="form.last_used_days_ago"
                        type="range"
                        min="0"
                        max="115"
                        step="5"
                        class="mt-2 w-full accent-emerald-600"
                    />
                    <p class="mt-1 text-[11px] text-slate-500">
                        First ₦50 top-up will run in
                        <strong>{{ Math.max(1, 120 - form.last_used_days_ago) }} days</strong>.
                    </p>
                </div>

                <!-- Multi-Select Linked Banks -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700">
                        Banks Linked to This SIM *
                    </label>
                    <p class="text-[11px] text-slate-500">
                        Select every bank that has this phone number on file.
                    </p>

                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <button
                            v-for="(bank, key) in availableBanks"
                            :key="key"
                            type="button"
                            class="flex items-center justify-between rounded-xl border px-3 py-2 text-left text-xs transition"
                            :class="
                                form.bank_keys.includes(String(key))
                                    ? 'border-slate-900 bg-slate-900 text-white'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                            "
                            @click="toggleBank(String(key))"
                        >
                            <span class="font-semibold">{{ bank.short_name }}</span>
                            <span class="font-mono text-[10px] opacity-75">{{ bank.ussd_block_code }}</span>
                        </button>
                    </div>
                    <p v-if="form.errors.bank_keys" class="mt-1 text-xs text-red-600">
                        {{ form.errors.bank_keys }}
                    </p>
                </div>

                <!-- Submit -->
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
                        {{ form.processing ? 'Saving...' : 'Save to SIM Vault' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>