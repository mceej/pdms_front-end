<<<<<<< HEAD
    <template>
    <aside ref="sidebarRoot" :class="['role-sidebar', { expanded: sidebarExpanded }]">
=======
<template>
    <aside ref="sidebarRoot" :class="['role-sidebar', { expanded: sidebarExpanded }]" @click="toggleOnBlankArea">
>>>>>>> a2d4feaea0397b58e880a9277f05945a2bba0d54
        <div class="sidebar-brand">
            <img src="/logo/dswdsidebarlogo.png" alt="DSWD logo" />
            <span v-if="sidebarExpanded">DSWD Assist Track</span>
        </div>

        <button
            type="button"
            class="sidebar-toggle"
            :aria-label="sidebarExpanded ? 'Collapse sidebar' : 'Expand sidebar'"
            :title="sidebarExpanded ? 'Collapse sidebar' : 'Expand sidebar'"
            :aria-expanded="sidebarExpanded"
            @click="sidebarExpanded = !sidebarExpanded"
        >
            <i :class="sidebarExpanded ? 'pi pi-angle-left' : 'pi pi-angle-right'" aria-hidden="true"></i>
        </button>

        <nav class="sidebar-nav" aria-label="Main navigation">
            <button
                v-for="item in items"
                :key="item.key"
                type="button"
                :class="['sidebar-link', { active: activeItem === item.key }]"
                :title="item.description ? `${item.label}: ${item.description}` : item.label"
                :aria-label="item.label"
                :aria-current="activeItem === item.key ? 'page' : undefined"
                @click="emit('select', item.key)"
            >
                <i :class="item.icon" aria-hidden="true"></i>
                <span v-if="sidebarExpanded" class="sidebar-link-copy">
                    <span>{{ item.label }}</span>
                    <small v-if="item.description">{{ item.description }}</small>
                </span>
            </button>
        </nav>

        <button
            type="button"
            class="sidebar-link sidebar-logout"
            title="Log out"
            aria-label="Log out"
            @click="emit('logout')"
        >
            <i class="pi pi-sign-out" aria-hidden="true"></i>
            <span v-if="sidebarExpanded">Log out</span>
        </button>
    </aside>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

defineProps({
    items: { type: Array, required: true },
    activeItem: { type: String, required: true },
});

const emit = defineEmits(['select', 'logout']);
const sidebarRoot = ref(null);
const sidebarExpanded = ref(false);

const toggleOnBlankArea = (event) => {
    if (event.target.closest('button, a, input, select, textarea, .sidebar-brand')) return;
    sidebarExpanded.value = true;
};

const collapseOnOutsidePointer = (event) => {
    if (sidebarExpanded.value && !sidebarRoot.value?.contains(event.target)) {
        sidebarExpanded.value = false;
    }
};

onMounted(() => document.addEventListener('pointerdown', collapseOnOutsidePointer));
onUnmounted(() => document.removeEventListener('pointerdown', collapseOnOutsidePointer));
</script>

<style scoped>
.role-sidebar {
    position: sticky;
    top: 0;
    z-index: 20;
    display: flex;
    flex: 0 0 60px;
    flex-direction: column;
    width: 60px;
    height: 100vh;
    padding: 12px 7px;
    overflow: visible;
<<<<<<< HEAD
    background: #2e3192;
=======
    background: #2E3192;
>>>>>>> a2d4feaea0397b58e880a9277f05945a2bba0d54
    color: #fff;
    transition: width 180ms ease, flex-basis 180ms ease;
}

.role-sidebar.expanded {
    flex-basis: 232px;
    width: 232px;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 46px;
    gap: 10px;
    margin-bottom: 36px;
    overflow: hidden;
    color: #fff;
    font-size: 13px;
    white-space: nowrap;
}

.sidebar-brand img {
    display: block;
    flex: 0 0 38px;
    width: 38px;
    height: 38px;
    object-fit: contain;
}

.role-sidebar.expanded .sidebar-brand {
    justify-content: flex-start;
    padding: 0 8px;
}

.sidebar-toggle {
    position: absolute;
    top: 62px;
    right: -10px;
    z-index: 2;
    display: grid;
    width: 20px;
    height: 20px;
    place-items: center;
    padding: 0;
    border: 1px solid #cbd8e8;
    border-radius: 6px;
    background: #fff;
    color: #17477f;
    box-shadow: 1px 2px 5px rgb(14 39 71 / 18%);
    cursor: pointer;
    font-size: 12px;
}

.sidebar-toggle:hover { background: #edf5ff; }
.sidebar-toggle:focus-visible { outline: 3px solid #8bcaf0; outline-offset: 2px; }

.sidebar-nav {
    display: grid;
    gap: 10px;
}

.sidebar-link {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    gap: 12px;
    min-width: 0;
    min-height: 42px;
    padding: 0;
    border: 0;
    border-radius: 4px;
    background: transparent;
    color: #fff;
    cursor: pointer;
    font: inherit;
    font-size: 12px;
    font-weight: 600;
    text-align: left;
    white-space: nowrap;
}

.sidebar-link:hover { background: rgb(255 255 255 / 14%); }
.sidebar-link.active { background: rgb(255 255 255 / 17%); }
.sidebar-link:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
.role-sidebar.expanded .sidebar-link { justify-content: flex-start; padding: 0 12px; }

.sidebar-link > i {
    flex: 0 0 18px;
    width: 18px;
    font-size: 15px;
    text-align: center;
}

.sidebar-link-copy { display: grid; gap: 3px; }
.sidebar-link-copy small { color: #c8dcfa; font-size: 10px; font-weight: 500; }
.sidebar-logout { margin-top: auto; }

@media (max-width: 480px) {
    .role-sidebar.expanded {
        flex-basis: min(232px, calc(100vw - 48px));
        width: min(232px, calc(100vw - 48px));
    }
}
</style>