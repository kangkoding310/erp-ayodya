import type { ApprovalStatus, ExpenseStatus, PurchaseStatus } from '@/types/models';

export function useApprovalStatus() {
    const isFinal = (status: PurchaseStatus | ExpenseStatus | ApprovalStatus): boolean =>
        ['approved', 'rejected', 'cancelled', 'sent_to_accounting'].includes(status);

    const isPending = (status: PurchaseStatus | ExpenseStatus | ApprovalStatus): boolean =>
        ['pending', 'draft', 'submitted', 'in_approval'].includes(status);

    return { isFinal, isPending };
}
