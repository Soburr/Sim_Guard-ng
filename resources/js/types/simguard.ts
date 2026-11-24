export type TelcoSlug = 'mtn' | 'airtel' | 'glo' | '9mobile';
export type SimMode = 'keep_alive' | 'watchdog';
export type SimStatus = 'active' | 'dormant' | 'compromised_woken_up';

export interface BankConfig {
    name: string;
    short_name: string;
    ussd_base: string;
    ussd_block_code: string;
    dial_uri: string;
    instructions: string;
    brand_hex: string;
}

export interface SimBankLink {
    id: number;
    monitored_sim_id: number;
    bank_key: string;
    bank_details: BankConfig;
}

export interface MonitoredSim {
    id: number;
    label: string | null;
    phone_number: string;
    local_number: string;
    telco: TelcoSlug;
    mode: SimMode;
    status: SimStatus;
    days_remaining: number | null;
    next_rga_due_at: string | null;
    last_probe_at: string | null;
    bank_links: SimBankLink[];
}

export interface WalletSummary {
    balance_kobo: number;
    topup_cost_kobo: number;
}

export interface WalletTransactionItem {
    id: number;
    reference: string;
    type: 'credit' | 'debit';
    amount_kobo: number;
    description: string;
    status: 'pending' | 'success' | 'failed';
    created_at: string;
}