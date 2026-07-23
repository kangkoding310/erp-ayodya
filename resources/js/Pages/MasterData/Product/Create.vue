<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import Card from '@/Components/ui/Card.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { AccountType, Currency, ProductCategory } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    categories: ProductCategory[];
    accountType: AccountType[];
    currency: Currency[];
    
}>();

const categoryOptions = computed(() => props.categories.map((category) => ({ id: category.id, text: category.name })));
const accountTypeOptions = computed(() => props.accountType.map((account_type) => ({ id: account_type.id, text: account_type.name })));
const currencyOptions = computed(() => props.currency.map((currency) => ({ id: currency.id, text: currency.name })));

const form = useForm({
    name: '',
    price: '',
    user_price: '',
    partner_price: '',
    tax_percentage: '11',
    type: '',
    product_category_id: '' as number | '',
    account_type: '',
    coa: '',
    currency: ''
});

const submit = () => {
    form.post(route('master-data.products.store'));
};
</script>

<template>
    <Head title="Add Product" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Add Product</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <Card>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput id="name" v-model="form.name" placeholder="Enter product name" class="mt-1 block w-full" />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="product_category_id" value="Category" />
                            <SelectInput
                                id="product_category_id"
                                v-model="form.product_category_id"
                                :options="categoryOptions"
                                placeholder="Select category"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.product_category_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="account_type" value="Account Type" />
                            <SelectInput
                                id="account_type"
                                v-model="form.account_type"
                                :options="accountTypeOptions"
                                placeholder="Select Account Type"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.account_type" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="coa" value="Chart of Accounts" />
                            <TextInput id="coa" v-model="form.coa" type="number" placeholder="1101" class="mt-1 block w-full" />
                            <InputError :message="form.errors.coa" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="currency" value="Currency" />
                            <SelectInput
                                id="currency"
                                v-model="form.currency"
                                :options="currencyOptions"
                                placeholder="Select Account Type"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.currency" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="price" value="Price" />
                            <TextInput id="price" v-model="form.price" type="number" step="0.01" placeholder="0.00" class="mt-1 block w-full" />
                            <InputError :message="form.errors.price" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="user_price" value="User Price" />
                            <TextInput id="user_price" v-model="form.user_price" type="number" step="0.01" placeholder="0.00" class="mt-1 block w-full" />
                            <InputError :message="form.errors.user_price" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="partner_price" value="Partner Price" />
                            <TextInput id="partner_price" v-model="form.partner_price" type="number" step="0.01" placeholder="0.00" class="mt-1 block w-full" />
                            <InputError :message="form.errors.partner_price" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="tax_percentage" value="Tax %" />
                            <TextInput id="tax_percentage" v-model="form.tax_percentage" type="number" step="0.01" placeholder="0.00" class="mt-1 block w-full" />
                            <InputError :message="form.errors.tax_percentage" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="type" value="Type (optional)" />
                            <TextInput id="type" v-model="form.type" placeholder="Enter product type" class="mt-1 block w-full" />
                            <InputError :message="form.errors.type" class="mt-2" />
                        </div>

                        <div class="flex justify-end gap-3">
                            <Link :href="route('master-data.products.index')">
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
