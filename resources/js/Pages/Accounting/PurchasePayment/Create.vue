<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import Card from '@/Components/ui/Card.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Bank, PurchaseInvoice } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    purchaseInvoices: PurchaseInvoice[];
    banks: Bank[];
}>();

const purchaseInvoiceOptions = computed(() =>
    props.purchaseInvoices.map((invoice) => ({
        id: invoice.id,
        text: `${invoice.invoice_number} (${invoice.purchase_rfq?.purchase_request?.code ?? ''})`,
    })),
);

const bankOptions = computed(() => props.banks.map((bank) => ({ id: bank.id, text: bank.bank_name })));

const form = useForm({
    purchase_invoice_id: '' as number | '',
    bank_id: '' as number | '',
    payment_date: '',
    amount: '',
});

const submit = () => {
    form.post(route('accounting.purchase-payments.store'));
};
</script>

<template>
    <Head title="Record Payment" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Record Payment</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <Card>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div>
                            <InputLabel for="purchase_invoice_id" value="Purchase Invoice" />
                            <SelectInput
                                id="purchase_invoice_id"
                                v-model="form.purchase_invoice_id"
                                :options="purchaseInvoiceOptions"
                                placeholder="Select invoice"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.purchase_invoice_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="bank_id" value="Bank" />
                            <SelectInput
                                id="bank_id"
                                v-model="form.bank_id"
                                :options="bankOptions"
                                placeholder="Select bank"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.bank_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="payment_date" value="Payment Date" />
                            <TextInput id="payment_date" v-model="form.payment_date" type="date" class="mt-1 block w-full" />
                            <InputError :message="form.errors.payment_date" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="amount" value="Amount" />
                            <TextInput id="amount" v-model="form.amount" type="number" step="0.01" class="mt-1 block w-full" />
                            <InputError :message="form.errors.amount" class="mt-2" />
                        </div>

                        <div class="flex justify-end gap-3">
                            <Link :href="route('accounting.purchase-payments.index')">
                                <SecondaryButton type="button">Cancel</SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
