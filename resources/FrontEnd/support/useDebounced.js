import { onUnmounted, ref, watch } from 'vue';

/**
 * Follow a value, but only after it stops changing for a moment.
 *
 * Typing stays responsive because the list is filtered once the person pauses,
 * instead of on every keystroke.
 */
export const useDebounced = (source, delay = 150) => {
    const settled = ref(source.value);
    let timer = null;

    watch(source, (value) => {
        if (timer !== null) {
            clearTimeout(timer);
        }

        timer = setTimeout(() => {
            settled.value = value;
            timer = null;
        }, delay);
    });

    onUnmounted(() => {
        if (timer !== null) {
            clearTimeout(timer);
        }
    });

    return settled;
};

/**
 * One lowercase string to search a row by, built once instead of per keystroke.
 */
export const searchableText = (...fields) => fields.filter(Boolean).join(' ').toLowerCase();
