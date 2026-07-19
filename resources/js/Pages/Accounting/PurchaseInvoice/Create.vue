<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Card from '@/Components/ui/Card.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { PurchaseRfq } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    purchaseRfqs: PurchaseRfq[];
}>();

const form = useForm({
    purchase_rfq_id: '' as number | '',
    invoice_number: '',
    invoice_date: '',
    amount: '',
});

const submit = () => {
    form.post(route('accounting.purchase-invoices.store'));
};
</script>

<template>
    <Head title="Add Purchase Invoice" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Add Purchase Invoice</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <Card>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div>
                            <InputLabel for="purchase_rfq_id" value="Purchase Request (RFQ)" />
                            <select
                                id="purchase_rfq_id"
                                v-model="form.purchase_rfq_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="" disabled>Select RFQ</option>
                                <option v-for="rfq in purchaseRfqs" :key="rfq.id" :value="rfq.id">
                                    {{ rfq.purchase_request?.code }}
                                </option>
                            </select>
                            <InputError :message="form.errors.purchase_rfq_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="invoice_number" value="Invoice Number" />
                            <TextInput id="invoice_number" v-model="form.invoice_number" class="mt-1 block w-full" />
                            <InputError :message="form.errors.invoice_number" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="invoice_date" value="Invoice Date" />
                            <TextInput id="invoice_date" v-model="form.invoice_date" type="date" class="mt-1 block w-full" />
                            <InputError :message="form.errors.invoice_date" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="amount" value="Amount" />
                            <TextInput id="amount" v-model="form.amount" type="number" step="0.01" class="mt-1 block w-full" />
                            <InputError :message="form.errors.amount" class="mt-2" />
                        </div>

                        <div class="flex justify-end gap-3">
                            <Link :href="route('accounting.purchase-invoices.index')">
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
