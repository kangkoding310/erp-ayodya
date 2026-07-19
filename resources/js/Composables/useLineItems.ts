import { computed, type Ref } from 'vue';

export function useLineItems<T extends object>(lines: Ref<T[]>, emptyLine: () => T, subtotal: (line: T) => number) {
    const add = () => {
        lines.value.push(emptyLine());
    };

    const remove = (index: number) => {
        lines.value.splice(index, 1);
    };

    const total = computed(() => lines.value.reduce((sum, line) => sum + subtotal(line), 0));

    return { add, remove, total };
}
