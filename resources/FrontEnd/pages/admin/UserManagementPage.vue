<template>
    <section class="admin-workspace">
        <p v-if="serverError && !showAddUserDialog && !editingUser && !confirmationAction" class="form-error page-error">
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
                        :class="{ 'filter-active': hasSelectedFilters }"
                        :aria-expanded="filterOpen"
                        @click="filterOpen = !filterOpen"
                    >
                        <i class="pi pi-filter" aria-hidden="true"></i>
                        <span>Filter</span>
                        <i class="pi pi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div v-if="actionNotification" class="action-notification" role="status">
                        <i class="pi pi-check-circle" aria-hidden="true"></i>
                        {{ actionNotification }}
                    </div>
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

        <div
            class="table-wrap"
            role="region"
            aria-label="User management table"
            tabindex="0"
            :style="{ '--visible-row-height': `${visibleUsers.length ? visibleUsers.length * 76 : 100}px` }"
        >
            <table class="admin-table users-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>User Role</th>
                        <th>Assigned Section</th>
                        <th>Status</th>
                        <th class="action-col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="isLoading" class="table-note">
                        <td colspan="6">Loading users…</td>
                    </tr>
                    <tr v-for="user in visibleUsers" :key="user.id">
                        <td>{{ user.name }}</td>
                        <td>{{ user.email }}</td>
                        <td>{{ user.role }}</td>
                        <td>{{ user.section }}</td>
                        <td>
                            <span :class="['status-label', user.status.toLowerCase()]">
                                <span class="status-dot"></span>
                                {{ user.status }}
                            </span>
                        </td>
                        <td class="action-col action-cell">
                            <button
                                type="button"
                                class="edit-user-button"
                                :aria-label="`Edit user ${user.name}`"
                                title="Edit user"
                                @click="beginEditUser(user)"
                            >
                                <i class="pi pi-pencil" aria-hidden="true"></i>
                            </button>
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
                    User Role
                    <select v-model="newUser.role">
                        <option v-for="role in roleOptions" :key="role.value" :value="role.value">
                            {{ role.label }}
                        </option>
                    </select>
                </label>
                <label v-if="isSectionRequired(newUser.role)">
                    Assigned Section
                    <select v-model="newUser.section" :required="isSectionRequired(newUser.role)">
                        <option value="">Select assigned section</option>
                        <option v-for="section in sectionOptions" :key="section" :value="section">
                            {{ section }}
                        </option>
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
                    User Role
                    <select v-model="editDraft.role">
                        <option v-for="role in roleOptions" :key="role.value" :value="role.value">
                            {{ role.label }}
                        </option>
                    </select>
                </label>
                <label v-if="isSectionRequired(editDraft.role)">
                    Assigned Section
                    <select v-model="editDraft.section" :required="isSectionRequired(editDraft.role)">
                        <option value="">Select assigned section</option>
                        <option v-for="section in sectionOptions" :key="section" :value="section">
                            {{ section }}
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
                <fieldset class="status-switch">
                    <legend>Edit Status</legend>
                    <div class="status-choice" role="group" aria-label="Edit user status">
                        <button
                            type="button"
                            class="inactive-choice"
                            :class="{ selected: editDraft.status === 'Inactive' }"
                            :aria-pressed="editDraft.status === 'Inactive'"
                            @click="editDraft.status = 'Inactive'"
                        >
                            Inactive
                        </button>
                        <button
                            type="button"
                            class="active-choice"
                            :class="{ selected: editDraft.status === 'Active' }"
                            :aria-pressed="editDraft.status === 'Active'"
                            @click="editDraft.status = 'Active'"
                        >
                            Active
                        </button>
                    </div>
                </fieldset>
                <p v-if="editPasswordError" class="form-error">{{ editPasswordError }}</p>
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
            v-if="confirmationAction"
            class="dialog-backdrop"
            @click.self="confirmationAction = null"
        >
            <section class="confirmation-dialog" role="alertdialog" aria-modal="true">
                <span :class="['confirmation-icon', confirmationAction.type]" aria-hidden="true">
                    <i :class="confirmationIcon"></i>
                </span>
                <h2>{{ confirmationTitle }}</h2>
                <p>{{ confirmationMessage }}</p>
                <div class="dialog-actions">
                    <button type="button" class="cancel-button" @click="confirmationAction = null">
                        Cancel
                    </button>
                    <button
                        type="button"
                        :class="confirmationAction.type === 'delete' ? 'delete-button' : 'submit-button'"
                        :disabled="isSaving"
                        @click="confirmAction"
                    >
                        {{ confirmationButtonLabel }}
                    </button>
                </div>
            </section>
        </div>
        <AppFooter />
    </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import AppFooter from '../../components/AppFooter.vue';
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
const pageSize = ref(10);
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
const confirmationAction = ref(null);
const actionNotification = ref('');
const passwordMismatch = ref(false);
const editPasswordError = ref('');
let actionNotificationTimer;
const newUser = ref({
    name: '',
    email: '',
    section: '',
    role: 'MANCOM',
    password: '',
    confirmPassword: '',
});
const editDraft = ref({});
const isSectionRequired = (role) => role === 'RDV Focal';
const users = ref([]);
const isLoading = ref(true);
const isSaving = ref(false);
const serverError = ref('');
const searchQuery = useDebounced(search, 150);

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

const hasSelectedFilters = computed(
    () => draftSections.value.length > 0 || draftRoles.value.length > 0,
);

const confirmationTitle = computed(() => {
    if (confirmationAction.value?.type === 'edit') return 'Save user changes?';
    if (confirmationAction.value?.type === 'delete') return 'Delete user?';
    return `${confirmationAction.value?.nextStatus} user?`;
});

const confirmationMessage = computed(() => {
    const action = confirmationAction.value;
    if (!action) return '';
    if (action.type === 'edit') return `Save the updated user details for ${action.user.name}?`;
    if (action.type === 'delete') return `This will permanently remove ${action.user.name} from this list.`;
    return `${action.nextStatus} ${action.user.name}'s account?`;
});

const confirmationButtonLabel = computed(() => {
    if (confirmationAction.value?.type === 'edit') return 'Save Changes';
    if (confirmationAction.value?.type === 'delete') return 'Delete User';
    return confirmationAction.value?.nextStatus;
});

const confirmationIcon = computed(() =>
    confirmationAction.value?.type === 'delete' ? 'pi pi-trash' : 'pi pi-exclamation-circle',
);

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
        section: '',
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
        status: user.status,
        newPassword: '',
        confirmPassword: '',
    };
    passwordMismatch.value = false;
    editPasswordError.value = '';
    serverError.value = '';
    openUserActions.value = null;
};

const cancelEditUser = () => {
    editingUser.value = null;
    editPasswordError.value = '';
};

const saveUserEdit = () => {
    const isChangingPassword = [
        editDraft.value.newPassword,
        editDraft.value.confirmPassword,
    ].some(Boolean);

    if (isChangingPassword && editDraft.value.newPassword !== editDraft.value.confirmPassword) {
        editPasswordError.value = 'New password and confirmation do not match.';
        return;
    }

    editPasswordError.value = '';
    serverError.value = '';
    confirmationAction.value = {
        type: 'edit',
        user: editingUser.value,
        draft: { ...editDraft.value },
    };
};

const showActionNotification = (message) => {
    window.clearTimeout(actionNotificationTimer);
    actionNotification.value = message;
    actionNotificationTimer = window.setTimeout(() => {
        actionNotification.value = '';
    }, 3500);
};

const confirmAction = async () => {
    const action = confirmationAction.value;
    if (!action) return;

    isSaving.value = true;
    serverError.value = '';
    let result;

    if (action.type === 'edit') {
        result = await updateUser(action.user.uid, {
            name: action.draft.name,
            section: action.draft.section,
            role: action.draft.role,
            status: action.draft.status,
        });

        if (result.ok && action.draft.newPassword) {
            result = await setUserPassword(action.user.uid, action.draft.newPassword);
        }
    } else if (action.type === 'delete') {
        result = await deleteUser(action.user.uid);
    } else {
        result = await updateUser(action.user.uid, {
            name: action.user.name,
            section: action.user.section,
            role: action.user.role,
            status: action.nextStatus === 'Deactivate' ? 'Inactive' : 'Active',
        });
    }

    isSaving.value = false;

    if (!result.ok) {
        serverError.value = result.message;
        confirmationAction.value = null;
        return;
    }

    if (action.type === 'edit') {
        cancelEditUser();
        showActionNotification('User updated successfully.');
    } else if (action.type === 'delete') {
        page.value = Math.min(page.value, pageCount.value);
        showActionNotification('User deleted successfully.');
    } else {
        showActionNotification(
            `User ${action.nextStatus === 'Deactivate' ? 'deactivated' : 'activated'} successfully.`
        );
    }

    confirmationAction.value = null;
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
    window.clearTimeout(actionNotificationTimer);
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
    width: 100%;
    min-width: 0;
    height: 100vh;
    padding: 22px 28px 24px;
    overflow: hidden;
    background: #f4f7fb;
}

.admin-page-header {
    flex: 0 0 72px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    height: 72px;
    min-height: 72px;
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

.filter-control select {
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

.filter-trigger.filter-active {
    border-color: #9fc9ed;
    background: #e5f3ff;
    color: #256da8;
}

.action-notification {
    position: absolute;
    right: 0;
    bottom: calc(100% + 10px);
    z-index: 40;
    display: flex;
    align-items: center;
    gap: 7px;
    width: max-content;
    max-width: 290px;
    padding: 10px 12px;
    border: 1px solid #a8dfba;
    border-radius: 5px;
    background: #f0fdf4;
    box-shadow: 0 6px 18px rgb(13 28 51 / 15%);
    color: #166534;
    font-size: 13px;
    font-weight: 600;
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
    display: block;
    flex: 1 1 auto;
    width: min(100%, 2000px);
    max-width: 100%;
    min-height: 0;
    min-width: 0;
    overflow-x: hidden;
    overflow-y: auto;
    scrollbar-gutter: stable;
    border: 1px solid #dce3ed;
    background-color: #fff;
    background-image:
        linear-gradient(to bottom, #f8faff 0 44px, #e4e8ef 44px 45px, transparent 45px),
        repeating-linear-gradient(to bottom, #e4e8ef 0 1px, transparent 1px 76px);
    background-position: left top, left 45px;
    background-size: 100% 45px, 100% var(--visible-row-height);
    background-repeat: no-repeat;
    scrollbar-color: #9aa6b2 transparent;
    scrollbar-width: thin;
}

.table-wrap::-webkit-scrollbar {
    width: 12px;
}

.table-wrap::-webkit-scrollbar-track,
.table-wrap::-webkit-scrollbar-button {
    background: transparent;
}

.table-wrap::-webkit-scrollbar-button {
    display: none;
}

.table-wrap::-webkit-scrollbar-thumb {
    border: 3px solid transparent;
    border-radius: 8px;
    background-color: #9aa6b2;
    background-clip: content-box;
}

.admin-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    background: #fff;
    color: #111827;
    font-size: 14px;
    text-align: left;
    white-space: nowrap;
}

.users-table th:nth-child(1) { width: 18%; }
.users-table th:nth-child(2) { width: 24%; }
.users-table th:nth-child(3) { width: 15%; }
.users-table th:nth-child(4) { width: 21%; }
.users-table th:nth-child(5) { width: 12%; }

.admin-table th {
    position: sticky;
    top: 0;
    z-index: 1;
    height: 45px;
    padding: 0 14px;
    background: #f8faff;
    color: #354768;
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
}

.admin-table td {
    height: 76px;
    padding: 0 14px;
    border-top: 1px solid #e4e8ef;
}

.admin-table tbody tr:hover {
    background: #f9fbff;
}

.users-table .action-col {
    width: 150px;
    padding: 0 8px;
    text-align: center;
}

.users-table th:nth-child(4),
.users-table td:nth-child(4) {
    padding-left: 30px;
    padding-right: 0;
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

.edit-user-button {
    display: center;
    width: 28px;
    height: 28px;
    place-items: center;
    border: 0;
    border-radius: 4px;
    background: transparent;
    color: #273b83;
    cursor: pointer;
    font-size: 14px;
}

.edit-user-button:hover {
    background: #eaf0ff;
}

.empty-row {
    height: 100px !important;
    color: #718096;
    text-align: center;
}

.table-pagination {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
    min-height: 44px;
    margin-bottom: 16px;
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

.form-field-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.form-field-row label {
    min-width: 0;
}

.form-field-row select {
    width: 100%;
    max-width: 100%;
    justify-self: stretch;
    padding-right: 38px;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8'%3E%3Cpath d='m1 1 5 5 5-5' fill='none' stroke='%23516074' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5'/%3E%3C/svg%3E");
    background-position: right 18px center;
    background-repeat: no-repeat;
    background-size: 10px 7px;
}

.status-switch {
    margin: 0;
    padding: 0;
    border: 0;
}

.status-switch legend {
    margin-bottom: 6px;
    color: #39465a;
    font-size: 14px;
    font-weight: 600;
}

.status-choice {
    display: inline-flex;
    overflow: hidden;
    padding: 3px;
    border: 0;
    border-radius: 30px;
    background: #e1e3e7;
    box-shadow: 0 2px 5px rgb(15 23 42 / 10%);
}

.status-choice button {
    min-width: 106px;
    min-height: 38px;
    padding: 0 18px;
    border: 0;
    border-radius: 25px;
    background: transparent;
    cursor: pointer;
    font: inherit;
    font-size: 14px;
    font-weight: 700;
    transition: background-color 180ms ease, color 180ms ease, transform 160ms ease;
}

.status-choice .inactive-choice {
    color: #a10e0e;
}

.status-choice .active-choice {
    color: #137238;
}

.status-choice button:active {
    transform: scale(0.95);
}

.status-choice .inactive-choice.selected {
    background: #942020;
    color: #fff;
    animation: status-choice-pop 220ms ease-out;
}

.status-choice .active-choice.selected {
    background: #13863b;
    color: #fff;
    animation: status-choice-pop 220ms ease-out;
}

.status-choice button:focus-visible {
    position: relative;
    z-index: 1;
    outline: 3px solid rgb(48 43 156 / 32%);
    outline-offset: -3px;
}

@keyframes status-choice-pop {
    0% { filter: brightness(1.25); }
    100% { filter: brightness(1); }
}

@media (prefers-reduced-motion: reduce) {
    .status-choice,
    .status-choice button {
        transition: none;
    }

    .status-choice .inactive-choice.selected,
    .status-choice .active-choice.selected {
        animation: none;
    }
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

.confirmation-dialog {
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

.confirmation-icon {
    display: grid;
    width: 46px;
    height: 46px;
    place-items: center;
    border-radius: 50%;
    background: #eeedff;
    color: #302b9c;
    font-size: 18px;
}

.confirmation-icon.delete {
    background: #fff0ef;
    color: #c73535;
}

.confirmation-dialog h2 {
    margin: 0;
    color: #20242c;
    font-size: 20px;
}

.confirmation-dialog p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
    line-height: 1.5;
}

.confirmation-dialog .dialog-actions {
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
        padding: 16px 12px 16px;
    }

    .admin-page-header {
        flex: 0 0 auto;
        align-items: flex-start;
        flex-direction: column;
        height: auto;
        min-height: 0;
    }

    .admin-tools {
        width: 100%;
    }

    .search-control {
        flex: 1 1 auto;
    }

    .form-field-row {
        grid-template-columns: 1fr;
    }

}

.table-note td {
    padding: 22px 16px;
    color: #6b7280;
    font-size: 13px;
    text-align: center;
}
</style>
