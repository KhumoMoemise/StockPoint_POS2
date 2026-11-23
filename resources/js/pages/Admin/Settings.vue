<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import ModalShell from '@/components/StockPoint/ModalShell.vue';
import { dateOnly } from '@/lib/format';

const props = defineProps<{
    settings: {
        business_name: string;
        vat_number: string;
        phone: string;
        email: string;
        address: string;
        vat_rate: number;
    };
    cashiers: Array<{ id: number; name: string; email: string; sales_count: number; created_at: string }>;
}>();

const businessForm = useForm({
    business_name: props.settings.business_name,
    vat_number: props.settings.vat_number,
    phone: props.settings.phone,
    email: props.settings.email,
    address: props.settings.address,
    vat_rate: props.settings.vat_rate,
});

function saveBusiness() {
    businessForm.post(route('admin.settings.update'), { preserveScroll: true });
}

const cashierModal = ref(false);
const cashierForm = useForm({ name: '', email: '', password: '', password_confirmation: '' });

function openCashierModal() {
    cashierForm.reset();
    cashierForm.clearErrors();
    cashierModal.value = true;
}

function submitCashier() {
    cashierForm.post(route('admin.cashiers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            cashierModal.value = false;
            cashierForm.reset();
        },
    });
}

function removeCashier(cashier: (typeof props.cashiers)[number]) {
    if (window.confirm(`Remove cashier ${cashier.name}?`)) {
        router.delete(route('admin.cashiers.destroy', cashier.id), { preserveScroll: true });
    }
}
</script>

<template>
    <AdminLayout title="Store settings" crumb="Store settings" active="settings">
        <Head title="Store settings" />

        <div class="page-intro">
            <div>
                <h2>Store settings</h2>
                <p>Business information displayed on invoices and reports, plus cashier accounts.</p>
            </div>
        </div>

        <div class="panel" style="margin-bottom: 16px">
            <div class="panel-head"><h3>Business details</h3></div>
            <div class="panel-body">
                <form @submit.prevent="saveBusiness">
                    <div class="form-grid">
                        <div class="field">
                            <label>Business name *</label>
                            <input v-model="businessForm.business_name" required />
                            <div v-if="businessForm.errors.business_name" class="field-error">{{ businessForm.errors.business_name }}</div>
                        </div>
                        <div class="field">
                            <label>VAT number (optional)</label>
                            <input v-model="businessForm.vat_number" placeholder="Enter VAT number" />
                        </div>
                        <div class="field">
                            <label>Phone number</label>
                            <input v-model="businessForm.phone" placeholder="+27 ..." />
                        </div>
                        <div class="field">
                            <label>Email address</label>
                            <input v-model="businessForm.email" type="email" placeholder="store@example.co.za" />
                            <div v-if="businessForm.errors.email" class="field-error">{{ businessForm.errors.email }}</div>
                        </div>
                        <div class="field">
                            <label>VAT rate (%) *</label>
                            <input v-model.number="businessForm.vat_rate" type="number" min="0" max="100" step="0.01" required />
                            <div v-if="businessForm.errors.vat_rate" class="field-error">{{ businessForm.errors.vat_rate }}</div>
                        </div>
                        <div class="field full">
                            <label>Business address</label>
                            <textarea v-model="businessForm.address" placeholder="Street, suburb, city, postal code"></textarea>
                        </div>
                    </div>
                    <div style="margin-top: 15px">
                        <button class="btn btn-primary" :disabled="businessForm.processing">Save settings</button>
                    </div>
                </form>
                <div class="notice">These details are stored in the settings table and printed on every customer receipt.</div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <div>
                    <h3>Cashier accounts</h3>
                    <p>Create and manage the staff who can use the point of sale.</p>
                </div>
                <button class="btn btn-primary" @click="openCashierModal">＋ Add cashier</button>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Sales recorded</th><th>Created</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="cashier in cashiers" :key="cashier.id">
                            <td><strong>{{ cashier.name }}</strong></td>
                            <td>{{ cashier.email }}</td>
                            <td>{{ cashier.sales_count }}</td>
                            <td>{{ dateOnly(cashier.created_at) }}</td>
                            <td>
                                <button class="btn btn-small btn-danger" :disabled="cashier.sales_count > 0" @click="removeCashier(cashier)">
                                    Remove
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!cashiers.length">
                            <td colspan="5" class="empty">No cashier accounts yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ModalShell v-if="cashierModal" title="Add cashier" subtitle="Cashiers can only access the point of sale and their own sales." @close="cashierModal = false">
            <form @submit.prevent="submitCashier">
                <div class="form-grid">
                    <div class="field full">
                        <label>Full name *</label>
                        <input v-model="cashierForm.name" required />
                        <div v-if="cashierForm.errors.name" class="field-error">{{ cashierForm.errors.name }}</div>
                    </div>
                    <div class="field full">
                        <label>Email address *</label>
                        <input v-model="cashierForm.email" type="email" required />
                        <div v-if="cashierForm.errors.email" class="field-error">{{ cashierForm.errors.email }}</div>
                    </div>
                    <div class="field">
                        <label>Password *</label>
                        <input v-model="cashierForm.password" type="password" required />
                        <div v-if="cashierForm.errors.password" class="field-error">{{ cashierForm.errors.password }}</div>
                    </div>
                    <div class="field">
                        <label>Confirm password *</label>
                        <input v-model="cashierForm.password_confirmation" type="password" required />
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" @click="cashierModal = false">Cancel</button>
                    <button class="btn btn-primary" :disabled="cashierForm.processing">Create cashier</button>
                </div>
            </form>
        </ModalShell>
    </AdminLayout>
</template>
