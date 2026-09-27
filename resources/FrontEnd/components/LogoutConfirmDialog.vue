<template>
  <Teleport to="body">
    <div v-if="open" class="logout-overlay" @click.self="emit('cancel')">
      <section
        class="logout-dialog"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="logout-dialog-title"
        aria-describedby="logout-dialog-description"
        @keydown="handleKeydown"
      >
        <div class="logout-dialog-heading">
          <span class="logout-dialog-icon" aria-hidden="true">
            <i class="pi pi-sign-out"></i>
          </span>
          <div>
            <span class="logout-dialog-eyebrow">DSWD Assist Track</span>
            <h2 id="logout-dialog-title">Log out?</h2>
          </div>
        </div>

        <p id="logout-dialog-description">Are you sure you want to log out?</p>

        <div class="logout-dialog-actions">
          <button ref="cancelButton" type="button" class="logout-cancel" @click="emit('cancel')">
            Cancel
          </button>
          <button ref="confirmButton" type="button" class="logout-confirm" @click="emit('confirm')">
            <i class="pi pi-sign-out" aria-hidden="true"></i>
            Log out
          </button>
        </div>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue';

const props = defineProps({
  open: { type: Boolean, default: false },
});

const emit = defineEmits(['cancel', 'confirm']);
const cancelButton = ref(null);
const confirmButton = ref(null);

watch(() => props.open, async (isOpen) => {
  if (isOpen) {
    await nextTick();
    cancelButton.value?.focus();
  }
});

const handleKeydown = (event) => {
  if (event.key === 'Escape') {
    emit('cancel');
    return;
  }

  if (event.key !== 'Tab') return;
  if (event.shiftKey && document.activeElement === cancelButton.value) {
    event.preventDefault();
    confirmButton.value?.focus();
  } else if (!event.shiftKey && document.activeElement === confirmButton.value) {
    event.preventDefault();
    cancelButton.value?.focus();
  }
};
</script>

<style scoped>
.logout-overlay {
  position: fixed;
  inset: 0;
  z-index: 2000;
  display: grid;
  place-items: center;
  padding: 20px;
  background: rgba(10, 23, 46, 0.58);
  backdrop-filter: blur(3px);
  animation: logout-fade-in 150ms ease-out;
}

.logout-dialog,
.logout-dialog * { box-sizing: border-box; }

.logout-dialog {
  width: min(100%, 420px);
  padding: 26px;
  border: 1px solid #d9e3ef;
  border-top: 4px solid #c9473d;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 24px 70px rgba(3, 22, 55, 0.3);
  animation: logout-dialog-in 170ms ease-out;
}

.logout-dialog-heading {
  display: flex;
  align-items: center;
  gap: 14px;
}

.logout-dialog-icon {
  display: grid;
  flex: 0 0 44px;
  width: 44px;
  height: 44px;
  place-items: center;
  border-radius: 50%;
  background: #fff0ee;
  color: #b9382f;
  font-size: 18px;
}

.logout-dialog-eyebrow {
  color: #61748c;
  font-size: 11px;
  font-weight: 700;
}

.logout-dialog h2 {
  margin: 3px 0 0;
  color: #122b50;
  font-size: 22px;
  line-height: 1.2;
}

.logout-dialog p {
  margin: 20px 0 0;
  color: #53657b;
  font-size: 14px;
  line-height: 1.5;
}

.logout-dialog-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 26px;
}

.logout-dialog-actions button {
  display: inline-flex;
  width: auto;
  min-height: 40px;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 15px;
  border: 1px solid transparent;
  border-radius: 7px;
  font: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: background-color 140ms ease, border-color 140ms ease;
}

.logout-cancel {
  border-color: #cbd6e3 !important;
  background: #fff;
  color: #344a63;
}

.logout-cancel:hover { background: #f2f6fa; }

.logout-confirm {
  background: #b9382f;
  color: #fff;
}

.logout-confirm:hover { background: #982d27; }

.logout-dialog-actions button:focus-visible {
  outline: 3px solid #8bcaf0;
  outline-offset: 2px;
}

@keyframes logout-fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes logout-dialog-in {
  from { opacity: 0; transform: translateY(8px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

@media (prefers-reduced-motion: reduce) {
  .logout-overlay,
  .logout-dialog { animation: none; }
}
</style>