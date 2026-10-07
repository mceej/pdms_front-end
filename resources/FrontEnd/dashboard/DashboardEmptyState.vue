<template>
    <section class="empty-state" role="status" aria-live="polite">
        <span class="empty-state-icon" aria-hidden="true">
            <i class="pi pi-clipboard empty-state-clipboard"></i>
            <span class="empty-state-unavailable">
                <i class="pi pi-times"></i>
            </span>
        </span>
        <strong>No payout data available for {{ program }}. Please import a served list.</strong>
    </section>
</template>

<script setup>
defineProps({
    program: { type: String, required: true },
});
</script>

<style scoped>
.empty-state {
    display: flex;
    min-height: 390px;
    padding: 48px 24px;
    border: 1px solid #d5e0ea;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 4px 8px rgba(7, 32, 74, 0.18);
    color: #334155;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 18px;
    text-align: center;
}

.empty-state-icon {
    position: relative;
    display: inline-flex;
    width: 72px;
    height: 72px;
    color: #94a3b8;
    align-items: center;
    justify-content: center;
}

.empty-state-clipboard {
    font-size: 56px;
}

.empty-state-unavailable {
    position: absolute;
    right: 0;
    bottom: 0;
    display: inline-flex;
    width: 32px;
    height: 32px;
    border: 3px solid #94a3b8;
    border-radius: 50%;
    background: #fff;
    color: #94a3b8;
    align-items: center;
    justify-content: center;
}

.empty-state-unavailable i {
    font-size: 16px;
    font-weight: 700;
}

.empty-state strong {
    max-width: none;
    color: #94a3b8;
    font-size: 18px;
    font-weight: 500;
    line-height: 1.5;
    white-space: nowrap;
}
</style>
