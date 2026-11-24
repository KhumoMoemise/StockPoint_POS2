<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import ModalShell from '@/components/StockPoint/ModalShell.vue';

const props = defineProps<{
    suppliers: Array<{
        id: number;
        name: string;
        contact_person: string | null;
        email: string | null;
        phone: int | null;
        address: string | null;
        products_count: number;
        purchases_count: number;
        can_delete: boolean;
    }>;
    filters: { q: string };
    openCreate: boolean;
}>();

const search = ref('');

const filtered = computed(() =>
    props.suppliers.filter((supplier) => {
        const term = search.value.trim().toLowerCase();
        return (
            !term ||
            `${supplier.name} ${supplier.contact_person ?? ''} ${supplier.email ?? ''} ${supplier.phone ?? ''}`
                .toLowerCase()
                .includes(term)
        );
    }),
);

const modal = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({ name: '', contact_person: '', email: '', phone: '', address: '' });

function openCreate() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    modal.value = true;
}

function openEdit(supplier: (typeof props.suppliers)[number]) {
    editingId.value = supplier.id;
    form.clearErrors();
    form.name = supplier.name;
    form.contact_person = supplier.contact_person ?? '';
    form.email = supplier.email ?? '';
    form.phone = supplier.phone ?? '';
    form.address = supplier.address ?? '';
    modal.value = true;
}

function submit() {
    const options = { onSuccess: () => (modal.value = false) };

    if (editingId.value) {
        form.put(route('admin.suppliers.update', editingId.value), options);
    } else {
        form.post(route('admin.suppliers.store'), options);
    }
}

function destroy(supplier: (typeof props.suppliers)[number]) {
    if (window.confirm(`Delete supplier ${supplier.name}?`)) {
        router.delete(route('admin.suppliers.destroy', supplier.id));
    }
}

if (props.openCreate) {
    openCreate();
}
</script>

<template>
    <AdminLayout title="Supplier management" crumb="Suppliers" active="suppliers">
        <Head title="Suppliers" />

        <div class="page-intro">
            <div>
                <h2>Supplier management</h2>
                <p>Keep supplier contact details available for purchasing and replenishment.</p>
            </div>
            <button class="btn btn-primary" @click="openCreate">＋ Add supplier</button>
        </div>

        <div class="panel">
            <div class="toolbar">
                <input v-model="search" class="search" placeholder="Search supplier name, email or phone..." />
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Contact person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Products supplied</th>
                            <th>Purchases</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="supplier in filtered" :key="supplier.id">
                            <td><strong>{{ supplier.name }}</strong></td>
                            <td>{{ supplier.contact_person || '—' }}</td>
                            <td>{{ supplier.email || '—' }}</td>
                            <td>{{ supplier.phone || '—' }}</td>
                            <td>{{ supplier.products_count }}</td>
                            <td>{{ supplier.purchases_count }}</td>
                            <td>
                                <div class="action-row">
                                    <button class="btn btn-small" @click="openEdit(supplier)">Edit</button>
                                    <button class="btn btn-small btn-danger" @click="destroy(supplier)">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!filtered.length">
                            <td colspan="7" class="empty">No suppliers found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ModalShell
            v-if="modal"
            :title="editingId ? 'Edit supplier' : 'Add supplier'"
            subtitle="Save supplier contact information."
            @close="modal = false"
        >
            <form @submit.prevent="submit">
                <div class="form-grid">
                    <div class="field">
                        <label>Supplier name *</label>
                        <input v-model="form.name" required />
                        <div v-if="form.errors.name" class="field-error">{{ form.errors.name }}</div>
                    </div>
                    <div class="field">
                        <label>Contact person</label>
                        <input v-model="form.contact_person" />
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <input v-model="form.email" type="email" />
                        <div v-if="form.errors.email" class="field-error">{{ form.errors.email }}</div>
                    </div>
                    <div class="field">
                        <label>Phone</label>
                        <input v-model="form.phone" />
                    </div>
                    <div class="field full">
                        <label>Address</label>
                        <textarea v-model="form.address"></textarea>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" @click="modal = false">Cancel</button>
                    <button class="btn btn-primary" :disabled="form.processing">Save supplier</button>
                </div>
            </form>
        </ModalShell>
    </AdminLayout>
</template>
