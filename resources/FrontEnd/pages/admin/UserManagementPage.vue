<template>
    <section class="admin-workspace">
        <p v-if="serverError && !showAddUserDialog && !editingUser && !userToDelete" class="form-error page-error">
            {{ serverError }}
        </p>
        <header class="admin-page-header user-page-header">
            <div class="user-heading">
                <h1>User Management</h1>
                <button
                    type="button"
                    class="add-user-button"
                    @click="openAddUserDialog"
                >
                    <i class="pi pi-plus" aria-hidden="true"></i>
                    Add New User
                </button>
            </div>
            <div class="admin-tools">
                <label class="search-control">
                    <i class="pi pi-search" aria-hidden="true"></i>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search"
                        aria-label="Search users"
                    />
                </label>
                <div class="filter-control" ref="filterControl">
                    <button
                        type="button"
                        class="filter-trigger"
                        :aria-expanded="filterOpen"
                        @click="filterOpen = !filterOpen"
                    >
                        <i class="pi pi-filter" aria-hidden="true"></i>
                        <span>Filter</span>
                        <i class="pi pi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div v-if="filterOpen" class="filter-panel">
                        <section class="filter-group">
                            <h2>Assigned Section</h2>
                            <label v-for="section in sectionOptions" :key="section">
                                <input
                                    v-model="draftSections"
                                    type="checkbox"
                                    :value="section"
                                />
                                {{ section }}
                            </label>
                        </section>
                        <section class="filter-group">
                            <h2>User Role</h2>
                            <label v-for="role in roleOptions" :key="role.value">
                                <input
                                    v-model="draftRoles"
                                    type="checkbox"
                                    :value="role.value"
                                />
                                {{ role.label }}
                            </label>
                        </section>
                        <div class="filter-actions">
                            <button type="button" class="clear-filters" @click="clearFilters">
                                Clear All
                            </button>
                            <button type="button" class="apply-filters" @click="applyFilters">
                                Apply
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="table-wrap">
            <table class="admin-table users-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Assigned Section</th>
                        <th>User Role</th>
                        <th>Status</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="isLoading" class="table-note">
                        <td colspan="6">Loading users…</td>
                    </tr>
                    <tr v-for="user in visibleUsers" :key="user.id">
                        <td>{{ user.name }}</td>
                        <td>{{ user.email }}</td>
                        <td>{{ user.section }}</td>
                        <td>{{ user.role }}</td>
                        <td>
                            <span :class="['status-label', user.status.toLowerCase()]">
                                <span class="status-dot"></span>
                                {{ user.status }}
                            </span>
                        </td>
                        <td class="action-cell">
                            <button
                                type="button"
                                class="row-action"
                                :aria-label="`More actions for ${user.name}`"
                                title="More actions"
                                @click="toggleUserActions(user.id)"
                            >
                                <i class="pi pi-ellipsis-v" aria-hidden="true"></i>
                            </button>
                            <div v-if="openUserActions === user.id" class="user-action-menu">
                                <button type="button" @click="beginEditUser(user)">
                                    Edit Credentials
                                </button>
                                <button type="button" @click="toggleUserStatus(user)">
                                    {{ user.status === 'Active' ? 'Deactivate' : 'Activate' }}
                                </button>
                                <button
                                    type="button"
                                    class="delete-action"
                                    @click="requestDeleteUser(user)"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="visibleUsers.length === 0">
                        <td class="empty-row" colspan="6">
                            No users found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <div class="page-controls" aria-label="User management pagination">
                <button
                    type="button"
                    aria-label="First page"
                    :disabled="page === 1"
                    @click="page = 1"
                >
                    «
                </button>
                <button
                    type="button"
                    aria-label="Previous page"
                    :disabled="page === 1"
                    @click="page--"
                >
                    ‹
                </button>
                <button
                    v-for="pageNumber in pageCount"
                    :key="pageNumber"
                    type="button"
                    :class="{ current: pageNumber === page }"
                    :aria-current="pageNumber === page ? 'page' : undefined"
                    @click="page = pageNumber"
                >
                    {{ pageNumber }}
                </button>
                <button
                    type="button"
                    aria-label="Next page"
                    :disabled="page === pageCount"
                    @click="page++"
                >
                    ›
                </button>
                <button
                    type="button"
                    aria-label="Last page"
                    :disabled="page === pageCount"
                    @click="page = pageCount"
                >
                    »
                </button>
            </div>
            <select v-model.number="pageSize" aria-label="Users per page">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
            </select>
        </div>

        <div
            v-if="showAddUserDialog"
            class="dialog-backdrop"
            @click.self="showAddUserDialog = false"
        >
            <form class="add-user-dialog" @submit.prevent="addUser">
                <h2>Add New User</h2>
                <label>
                    Name
                    <input v-model="newUser.name" required />
                </label>
                <label>
                    Email
                    <input v-model="newUser.email" type="email" required />
                </label>
                <label>
                    Assigned Section
                    <select v-model="newUser.section">
                        <option>CIS</option>
                        <option>DRMD</option>
                    </select>
                </label>
                <label>
                    User Role
                    <select v-model="newUser.role">
                        <option>MANCOM</option>
                        <option>RDV Focal</option>
                        <option>ADMIN</option>
                    </select>
                </label>
                <label>
                    Password
                    <input v-model="newUser.password" type="password" required />
                </label>
                <label>
                    Confirm Password
                    <input v-model="newUser.confirmPassword" type="password" required />
                </label>
                <p v-if="passwordMismatch" class="form-error">Passwords do not match.</p>
                <p v-if="serverError" class="form-error">{{ serverError }}</p>
                <div class="dialog-actions">
                    <button
                        type="button"
                        class="cancel-button"
                        @click="showAddUserDialog = false"
                    >
                        Cancel
                    </button>
                    <button type="submit" class="submit-button" :disabled="isSaving">Add User</button>
                </div>
            </form>
        </div>

        <div
            v-if="editingUser"
            class="dialog-backdrop"
            @click.self="cancelEditUser"
        >
            <form class="management-dialog" @submit.prevent="saveUserEdit">
                <h2>Edit Credentials</h2>
                <label>
                    Name
                    <input v-model="editDraft.name" required />
                </label>
                <label>
                    Email
                    <input v-model="editDraft.email" type="email" disabled />
                </label>
                <label>
                    Assigned Section
                    <select v-model="editDraft.section">
                        <option v-for="section in sectionOptions" :key="section" :value="section">
                            {{ section }}
                        </option>
                    </select>
                </label>
                <label>
                    User Role
                    <select v-model="editDraft.role">
                        <option v-for="role in roleOptions" :key="role.value" :value="role.value">
                            {{ role.label }}
                        </option>
                    </select>
                </label>
                <label>
                    New Password <span class="field-note">leave blank to keep the current one</span>
                    <input v-model="editDraft.newPassword" type="password" autocomplete="new-password" />
                </label>
                <label>
                    Confirm Password
                    <input v-model="editDraft.confirmPassword" type="password" autocomplete="new-password" />
                </label>
                <p v-if="passwordMismatch" class="form-error">Passwords do not match.</p>
                <p v-if="serverError" class="form-error">{{ serverError }}</p>
                <div class="dialog-actions">
                    <button type="button" class="cancel-button" @click="cancelEditUser">
                        Cancel
                    </button>
                    <button type="submit" class="submit-button" :disabled="isSaving">Save Changes</button>
                </div>
            </form>
        </div>

        <div
            v-if="userToDelete"
            class="dialog-backdrop"
            @click.self="userToDelete = null"
        >
            <section class="delete-confirm-dialog" role="alertdialog" aria-modal="true">
                <span class="delete-icon" aria-hidden="true">
                    <i class="pi pi-trash"></i>
                </span>
                <h2>Delete user?</h2>
                <p>
                    This will permanently remove <strong>{{ userToDelete.name }}</strong> from
                    this list.
                </p>
                <div class="dialog-actions">
                    <button type="button" class="cancel-button" @click="userToDelete = null">
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="delete-button"
                        :disabled="isSaving"
                        @click="confirmDeleteUser"
                    >
                        Delete User
                    </button>
                </div>
            </section>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { searchableText, useDebounced } from '../../support/useDebounced.js';
import {
    createUser,
    deleteUser,
    setUserPassword,
    subscribeUsers,
    updateUser,
} from '../../data/users.js';

const search = ref('');
const page = ref(1);
const pageSize = ref(25);
const openUserActions = ref(null);
const filterOpen = ref(false);
const filterControl = ref(null);
const sectionOptions = ['CIS', 'DRMD'];
const roleOptions = [
    { value: 'MANCOM', label: 'MANCOM' },
    { value: 'RDV Focal', label: 'RDV Focal' },
    { value: 'ADMIN', label: 'Admin' },
];
const draftSections = ref([]);
const draftRoles = ref([]);
const selectedSections = ref([]);
const selectedRoles = ref([]);
const showAddUserDialog = ref(false);
const editingUser = ref(null);
const userToDelete = ref(null);
const passwordMismatch = ref(false);
const newUser = ref({
    name: '',
    email: '',
    section: 'CIS',
    role: 'MANCOM',
    password: '',
    confirmPassword: '',
});
const editDraft = ref({});
const users = ref([]);
const isLoading = ref(true);
const searchQuery = useDebounced(search, 150);
const isSaving = ref(false);
const serverError = ref('');

let unsubscribeUsers = () => {};

const filteredUsers = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    return users.value.filter((user) => {
        const matchesQuery = !query || user.searchText.includes(query);
        const matchesSection =
            selectedSections.value.length === 0 || selectedSections.value.includes(user.section);
        const matchesRole =
            selectedRoles.value.length === 0 || selectedRoles.value.includes(user.role);
        return matchesQuery && matchesSection && matchesRole;
    });
});

const pageCount = computed(() =>
    Math.max(1, Math.ceil(filteredUsers.value.length / pageSize.value)),
);
const visibleUsers = computed(() => {
    const start = (page.value - 1) * pageSize.value;
    return filteredUsers.value.slice(start, start + pageSize.value);
});

const addUser = async () => {
    if (newUser.value.password !== newUser.value.confirmPassword) {
        passwordMismatch.value = true;
        return;
    }

    isSaving.value = true;
    serverError.value = '';

    const result = await createUser({
        name: newUser.value.name,
        email: newUser.value.email,
        section: newUser.value.section,
        role: newUser.value.role,
        status: 'Active',
        password: newUser.value.password,
    });

    isSaving.value = false;

    if (!result.ok) {
        serverError.value = result.message;
        return;
    }

    newUser.value = {
        name: '',
        email: '',
        section: 'CIS',
        role: 'MANCOM',
        password: '',
        confirmPassword: '',
    };
    passwordMismatch.value = false;
    showAddUserDialog.value = false;
};

const openAddUserDialog = () => {
    passwordMismatch.value = false;
    serverError.value = '';
    showAddUserDialog.value = true;
};

watch([draftSections, draftRoles], () => {
    selectedSections.value = [...draftSections.value];
    selectedRoles.value = [...draftRoles.value];
    page.value = 1;
}, { deep: true });

const applyFilters = () => {
    filterOpen.value = false;
};

const clearFilters = () => {
    draftSections.value = [];
    draftRoles.value = [];
    selectedSections.value = [];
    selectedRoles.value = [];
    page.value = 1;
};

const beginEditUser = (user) => {
    editingUser.value = user;
    editDraft.value = {
        name: user.name,
        email: user.email,
        section: user.section,
        role: user.role,
        newPassword: '',
        confirmPassword: '',
    };
    passwordMismatch.value = false;
    serverError.value = '';
    openUserActions.value = null;
};

const cancelEditUser = () => {
    editingUser.value = null;
    passwordMismatch.value = false;
};

const saveUserEdit = async () => {
    if (editDraft.value.newPassword !== editDraft.value.confirmPassword) {
        passwordMismatch.value = true;
        return;
    }

    isSaving.value = true;
    serverError.value = '';

    const { uid, status } = editingUser.value;
    const result = await updateUser(uid, {
        name: editDraft.value.name,
        section: editDraft.value.section,
        role: editDraft.value.role,
        status,
    });

    if (result.ok && editDraft.value.newPassword) {
        const passwordResult = await setUserPassword(uid, editDraft.value.newPassword);

        if (!passwordResult.ok) {
            isSaving.value = false;
            serverError.value = passwordResult.message;
            return;
        }
    }

    isSaving.value = false;

    if (!result.ok) {
        serverError.value = result.message;
        return;
    }

    cancelEditUser();
};

const requestDeleteUser = (user) => {
    userToDelete.value = user;
    openUserActions.value = null;
};

const confirmDeleteUser = async () => {
    isSaving.value = true;
    serverError.value = '';

    const result = await deleteUser(userToDelete.value.uid);

    isSaving.value = false;

    if (!result.ok) {
        serverError.value = result.message;
        return;
    }

    userToDelete.value = null;
    page.value = Math.min(page.value, pageCount.value);
};

const toggleUserStatus = async (user) => {
    openUserActions.value = null;
    serverError.value = '';

    const result = await updateUser(user.uid, {
        name: user.name,
        section: user.section,
        role: user.role,
        status: user.status === 'Active' ? 'Inactive' : 'Active',
    });

    if (!result.ok) {
        serverError.value = result.message;
    }
};

const toggleUserActions = (userId) => {
    openUserActions.value = openUserActions.value === userId ? null : userId;
};

const closeFilterOnOutsidePointer = (event) => {
    if (filterOpen.value && !filterControl.value?.contains(event.target)) {
        filterOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('pointerdown', closeFilterOnOutsidePointer);
    unsubscribeUsers = subscribeUsers((list) => {
        users.value = list.map((user) => ({
            ...user,
            searchText: searchableText(user.name, user.email, user.section, user.role, user.status),
        }));
        isLoading.value = false;
    });
});

onUnmounted(() => {
    document.removeEventListener('pointerdown', closeFilterOnOutsidePointer);
    unsubscribeUsers();
});

watch(
    [search, pageSize],
    () => {
        page.value = 1;
    },
);
</script>

<style scoped>
* {
    box-sizing: border-box;
}

.admin-workspace {
    position: relative;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    padding: 22px 28px 0;
    background: #f4f7fb;
}

.admin-page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    min-height: 64px;
    margin-bottom: 14px;
}

.user-heading {
    display: grid;
    justify-items: start;
    gap: 10px;
}

.admin-page-header h1 {
    margin: 0;
    color: #20242c;
    font-size: 24px;
    font-weight: 700;
}

.admin-tools {
    display: flex;
    align-items: center;
    gap: 8px;
}

.search-control,
.filter-control {
    display: flex;
    align-items: center;
    gap: 6px;
    height: 30px;
    padding: 0 8px;
    border: 1px solid #dce3ed;
    border-radius: 4px;
    background: #f9fbfe;
    color: #718096;
}

.search-control {
    width: 190px;
}

.search-control input {
    width: 100%;
    min-width: 0;
    border: 0;
    outline: 0;
    background: transparent;
    color: #253143;
    font: inherit;
    font-size: 14px;
}

.search-control input::placeholder {
    color: #9aa7b8;
}

.search-control i,
.filter-control i {
    font-size: 14px;
}

.filter-control select,
.table-pagination select {
    border: 0;
    outline: 0;
    background: transparent;
    color: #516074;
    font: inherit;
    font-size: 14px;
}

.filter-control select {
    width: 58px;
    cursor: pointer;
}

.filter-control {
    position: relative;
    height: auto;
    padding: 0;
    overflow: visible;
    border: 0;
    background: transparent;
}

.filter-trigger {
    display: flex;
    align-items: center;
    gap: 7px;
    height: 30px;
    padding: 0 9px;
    border: 1px solid #dce3ed;
    border-radius: 4px;
    background: #f9fbfe;
    color: #718096;
    cursor: pointer;
    font: inherit;
    font-size: 14px;
}

.filter-trigger .pi-chevron-down {
    margin-left: 3px;
    font-size: 10px;
}

.filter-panel {
    position: absolute;
    top: calc(100% + 7px);
    right: 0;
    z-index: 30;
    width: 250px;
    padding: 14px;
    border: 1px solid #dce3ed;
    border-radius: 5px;
    background: #fff;
    box-shadow: 0 8px 24px rgb(20 35 60 / 16%);
}

.filter-group {
    display: grid;
    gap: 8px;
    padding: 0 0 12px;
}

.filter-group + .filter-group {
    padding-top: 12px;
    border-top: 1px solid #e7ebf1;
}

.filter-group h2 {
    margin: 0;
    color: #39465a;
    font-size: 12px;
    font-weight: 700;
}

.filter-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #273244;
    cursor: pointer;
    font-size: 12px;
    font-weight: 400;
}

.filter-group input {
    width: 14px;
    height: 14px;
    margin: 0;
    accent-color: #302b9c;
}

.filter-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 10px;
    border-top: 1px solid #e7ebf1;
}

.filter-actions button {
    min-height: 28px;
    padding: 0 10px;
    border: 0;
    border-radius: 4px;
    cursor: pointer;
    font: inherit;
    font-size: 12px;
}

.clear-filters {
    background: transparent;
    color: #302b9c;
}

.apply-filters {
    background: #302b9c;
    color: #fff;
}

.add-user-button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 28px;
    padding: 0 10px;
    border: 0;
    border-radius: 4px;
    background: #302b9c;
    color: #fff;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
}

.table-wrap {
    min-height: 700px;
    overflow: auto;
    border: 1px solid #dce3ed;
    background: #fff;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    color: #111827;
    font-size: 14px;
    text-align: left;
    white-space: nowrap;
}

.admin-table th {
    height: 32px;
    padding: 0 14px;
    background: #f8faff;
    color: #354768;
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
}

.admin-table td {
    height: 50px;
    padding: 0 14px;
    border-top: 1px solid #e4e8ef;
}

.admin-table tbody tr:hover {
    background: #f9fbff;
}

.users-table th:last-child,
.users-table td:last-child {
    width: 38px;
    padding: 0 8px;
    text-align: center;
}

.status-label {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #18a64a;
}

.status-label.inactive .status-dot {
    background: #e23838;
}

.action-cell {
    position: relative;
}

.row-action {
    width: 28px;
    height: 28px;
    border: 0;
    border-radius: 4px;
    background: transparent;
    color: #293241;
    cursor: pointer;
    font-size: 14px;
}

.row-action:hover {
    background: #edf2f8;
}

.user-action-menu {
    position: absolute;
    top: 30px;
    right: 8px;
    z-index: 20;
    display: grid;
    gap: 2px;
    width: 180px;
    padding: 6px;
    border: 1px solid #dce3ed;
    border-radius: 4px;
    background: #fff;
    box-shadow: 0 4px 12px rgb(13 28 51 / 14%);
}

.user-action-menu button {
    display: block;
    width: 100%;
    min-height: 34px;
    padding: 7px 9px;
    border: 0;
    border-radius: 3px;
    background: transparent;
    color: #293241;
    cursor: pointer;
    font-size: 14px;
    text-align: left;
    white-space: nowrap;
}

.user-action-menu button:hover {
    background: #f1f5f9;
}

.user-action-menu .delete-action {
    color: #c73535;
}

.user-action-menu .delete-action:hover {
    background: #fff1f1;
}

.empty-row {
    height: 100px !important;
    color: #718096;
    text-align: center;
}

.table-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
    min-height: 44px;
    border: 1px solid #dce3ed;
    border-top: 0;
    background: #fff;
}

.page-controls {
    display: flex;
    align-items: center;
    gap: 5px;
}

.page-controls button {
    display: grid;
    width: 24px;
    height: 24px;
    place-items: center;
    padding: 0;
    border: 0;
    border-radius: 50%;
    background: transparent;
    color: #53647e;
    cursor: pointer;
    font-size: 14px;
}

.page-controls button.current {
    background: #302b9c;
    color: #fff;
}

.page-controls button:disabled {
    cursor: default;
    opacity: 0.4;
}

.table-pagination > select {
    padding: 4px 8px;
    border: 1px solid #dce3ed;
    border-radius: 4px;
}

.dialog-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: grid;
    place-items: center;
    padding: 18px;
    background: rgb(10 23 46 / 55%);
}

.add-user-dialog,
.management-dialog {
    display: grid;
    gap: 14px;
    width: min(100%, 420px);
    padding: 22px;
    border: 1px solid #dce3ed;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 16px 48px rgb(13 28 51 / 24%);
}

.add-user-dialog h2,
.management-dialog h2 {
    margin: 0;
    color: #20242c;
    font-size: 20px;
}

.add-user-dialog label,
.management-dialog label {
    display: grid;
    gap: 6px;
    color: #39465a;
    font-size: 14px;
    font-weight: 600;
}

.add-user-dialog input,
.add-user-dialog select,
.management-dialog input,
.management-dialog select {
    min-height: 36px;
    padding: 7px 9px;
    border: 1px solid #cfd8e5;
    border-radius: 4px;
    font: inherit;
}

.dialog-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 4px;
}

.dialog-actions button {
    min-height: 34px;
    padding: 0 12px;
    border: 0;
    border-radius: 4px;
    cursor: pointer;
    font: inherit;
    font-size: 14px;
}

.cancel-button {
    background: #edf1f6;
    color: #334155;
}

.submit-button {
    background: #302b9c;
    color: #fff;
}

.form-error {
    margin: -6px 0 0;
    color: #c73535;
    font-size: 12px;
}

.page-error {
    margin: 0 0 12px;
    padding: 10px 14px;
    border-radius: 8px;
    background: #fdecec;
    font-size: 13px;
}

.field-note {
    color: #6b7280;
    font-size: 11px;
    font-weight: 400;
}

.delete-confirm-dialog {
    display: grid;
    justify-items: center;
    gap: 12px;
    width: min(100%, 390px);
    padding: 26px;
    border: 1px solid #e0e5ed;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 16px 48px rgb(13 28 51 / 24%);
    text-align: center;
}

.delete-icon {
    display: grid;
    width: 46px;
    height: 46px;
    place-items: center;
    border-radius: 50%;
    background: #fff0ef;
    color: #c73535;
    font-size: 18px;
}

.delete-confirm-dialog h2 {
    margin: 0;
    color: #20242c;
    font-size: 20px;
}

.delete-confirm-dialog p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
    line-height: 1.5;
}

.delete-confirm-dialog .dialog-actions {
    width: 100%;
    justify-content: center;
    margin-top: 8px;
}

.delete-button {
    background: #c73535;
    color: #fff;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

@media (max-width: 720px) {
    .admin-workspace {
        padding: 16px 12px 0;
    }

    .admin-page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .admin-tools {
        width: 100%;
    }

    .search-control {
        flex: 1 1 auto;
    }

}

.table-note td {
    padding: 22px 16px;
    color: #6b7280;
    font-size: 13px;
    text-align: center;
}
</style>
