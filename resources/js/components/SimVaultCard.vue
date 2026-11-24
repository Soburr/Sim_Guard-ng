<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { MonitoredSim, TelcoSlug } from '@/types/simguard';

const props = defineProps<{
    sim: MonitoredSim;
}>();

const processing = ref(false);

const telcoBadge: Record<TelcoSlug, { label: string; classes: string }> = {
    mtn: { label: 'MTN', classes: 'bg-amber-400 text-slate-950' },
    airtel: { label: 'Airtel', classes: 'bg-red-600 text-white' },
    glo: { label: 'Glo', classes: 'bg-emerald-600 text-white' },
    '9mobile': { label: '9mobile', classes: 'bg-lime-700 text-white' },
};

const isCompromised = computed(() => props.sim.status === 'compromised_woken_up');

const progressPercent = computed(() => {
    if (props.sim.mode !== 'keep_alive' || props.sim.days_remaining === null) return 0;
    return Math.min(100, Math.max(5, Math.round((props.sim.days_remaining / 120) * 100)));
});

const switchMode = () => {
    const nextMode = props.sim.mode === 'keep_alive' ? 'watchdog' : 'keep_alive';
    processing.value = true;

    router.patch(
        `/sims/${props.sim.id}/mode`,
        { mode: nextMode },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

const removeSim = () => {
    if (!confirm(`Remove ${props.sim.local_number} from your SIM Vault?`)) return;

    processing.value = true;
    router.delete(`/sims/${props.sim.id}`, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <div
        class="flex flex-col justify-between rounded-2xl border p-5 shadow-sm transition"
        :class="
            isCompromised
                ? 'border-red-500 bg-red-50/70 ring-2 ring-red-500/20'
                : 'border-slate-200 bg-white hover:border-slate-300'
        "
    >
        <div>
            <!-- Top Header: Telco Pill + Number + Status -->
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span
                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-bold tracking-wide uppercase"
                        :class="telcoBadge[sim.telco].classes"
                    >
                        {{ telcoBadge[sim.telco].label }}
                    </span>
                    <div>
                        <h3 class="font-mono text-lg font-bold tracking-tight text-slate-900">
                            {{ sim.local_number }}
                        </h3>
                        <p v-if="sim.label" class="text-xs font-medium text-slate-500">
                            {{ sim.label }}
                        </p>
                    </div>
                </div>

                <span
                    v-if="isCompromised"
                    class="inline-flex items-center gap-1.5 rounded-full bg-red-600 px-2.5 py-1 text-xs font-semibold text-white"
                >
                    <span class="h-1.5 w-1.5 animate-ping rounded-full bg-white" />
                    SIM Woken Up!
                </span>
                <span
                    v-else-if="sim.mode === 'keep_alive'"
                    class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20"
                >
                    Keep-Alive Active
                </span>
                <span
                    v-else
                    class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-600/20"
                >
                    Dormant Watchdog
                </span>
            </div>

            <!-- Emergency Alert Banner (Only when compromised) -->
            <div
                v-if="isCompromised"
                class="mt-4 rounded-xl border border-red-200 bg-white p-3 text-xs text-red-900"
            >
                <p class="font-semibold">
                    This line reconnected to the mobile network. Tap a bank below to block USSD immediately:
                </p>
            </div>

            <!-- Lifecycle Countdown or Watchdog Probe Status -->
            <div class="mt-4 rounded-xl bg-slate-50 p-3.5">
                <template v-if="sim.mode === 'keep_alive'">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-medium text-slate-600">Next ₦50 top-up</span>
                        <span class="font-bold text-slate-900">
                            In {{ sim.days_remaining }} days ({{ sim.next_rga_due_at }})
                        </span>
                    </div>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-200">
                        <div
                            class="h-full rounded-full bg-emerald-600 transition-all"
                            :style="{ width: `${progressPercent}%` }"
                        />
                    </div>
                    <p class="mt-1.5 text-[11px] text-slate-500">
                        Resets telco inactivity clock 60 days before NCC barring.
                    </p>
                </template>

                <template v-else>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-medium text-slate-600">Network probe status</span>
                        <span class="font-semibold text-slate-800">
                            {{ sim.last_probe_at ? `Checked ${sim.last_probe_at}` : 'Queued for first check' }}
                        </span>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-500">
                        Probed every 6 hours. Instant WhatsApp &amp; SMS alert triggers if activated.
                    </p>
                </template>
            </div>

            <!-- Linked Banks & One-Tap USSD Kill-Switches -->
            <div class="mt-4">
                <p class="text-[11px] font-semibold tracking-wider text-slate-400 uppercase">
                    Linked Banks (Tap to Dial Kill-Switch)
                </p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <a
                        v-for="link in sim.bank_links"
                        :key="link.id"
                        :href="link.bank_details.dial_uri"
                        :title="link.bank_details.instructions"
                        class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition hover:opacity-90"
                        :class="
                            isCompromised
                                ? 'border-red-600 bg-red-600 text-white shadow-sm'
                                : 'border-slate-200 bg-white text-slate-800 hover:border-slate-300'
                        "
                    >
                        <span
                            class="h-2 w-2 rounded-full"
                            :style="{ backgroundColor: isCompromised ? '#ffffff' : link.bank_details.brand_hex }"
                        />
                        <span>{{ link.bank_details.short_name }}</span>
                        <span class="font-mono text-[11px] opacity-80">
                            {{ link.bank_details.ussd_block_code }}
                        </span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Card Footer Actions -->
        <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-3.5 text-xs">
            <button
                type="button"
                :disabled="processing"
                class="font-medium text-slate-600 transition hover:text-slate-900 disabled:opacity-50"
                @click="switchMode"
            >
                Switch to {{ sim.mode === 'keep_alive' ? 'Watchdog' : 'Keep-Alive' }}
            </button>

            <button
                type="button"
                :disabled="processing"
                class="font-medium text-red-600 transition hover:text-red-700 disabled:opacity-50"
                @click="removeSim"
            >
                Remove
            </button>
        </div>
    </div>
</template>