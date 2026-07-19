export interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface Division {
    id: number;
    code: string;
    name: string;
}

export interface ProductCategory {
    id: number;
    code: string;
    name: string;
}

export interface Product {
    id: number;
    name: string;
    price: string;
    tax_percentage: string;
    type: string | null;
    product_category_id: number;
    category?: ProductCategory;
}

export interface UserOption {
    id: number;
    name: string;
    email?: string;
}

export interface Employee {
    id: number;
    name: string;
    division_id: number;
    position: string | null;
    user_id: number | null;
    division?: Division;
    user?: UserOption | null;
}

export interface Bank {
    id: number;
    bank_name: string;
    account_number: string;
    account_name: string;
}

export interface Coa {
    id: number;
    code: string;
    name: string;
    product_id: number | null;
    type: string | null;
    product?: Product | null;
}

export interface PurchaseType {
    id: number;
    code: string;
    name: string;
}

export interface ApprovalMatrixLevel {
    id: number;
    approval_matrix_id: number;
    level: number;
    approver_id: number;
    is_required: boolean;
    approver?: UserOption;
}

export interface ApprovalMatrix {
    id: number;
    name: string;
    purchase_type_id: number | null;
    model_type: string;
    is_active: boolean;
    purchase_type?: PurchaseType;
    levels?: ApprovalMatrixLevel[];
}

export type PurchaseStatus = 'draft' | 'submitted' | 'in_approval' | 'approved' | 'rejected' | 'cancelled' | 'in_rfq' | 'sent_to_accounting';

export type ApprovalStatus = 'pending' | 'approved' | 'rejected';

export type ExpenseStatus = 'draft' | 'submitted' | 'approved' | 'rejected' | 'sent_to_accounting';

export interface PurchaseRequestLine {
    id: number;
    purchase_request_id: number;
    product_id: number;
    description: string | null;
    qty: string;
    price_estimate: string;
    subtotal: string;
    product?: Product;
}

export interface PurchaseRequestMessage {
    id: number;
    purchase_request_id: number;
    user_id: number;
    message: string;
    created_at: string;
    user?: UserOption;
}

export interface PurchaseApproval {
    id: number;
    purchase_request_id: number;
    approval_matrix_level_id: number;
    approver_id: number;
    status: ApprovalStatus;
    remarks: string | null;
    approved_at: string | null;
    approver?: UserOption;
    approval_matrix_level?: ApprovalMatrixLevel;
    purchase_request?: PurchaseRequest;
}

export interface PurchaseRfq {
    id: number;
    purchase_request_id: number;
    status: string;
    sent_to_accounting_at: string | null;
    purchase_request?: PurchaseRequest;
}

export interface PurchaseRequest {
    id: number;
    code: string;
    purchase_type_id: number;
    project_name: string | null;
    currency: string;
    employee_id: number;
    expected_date: string | null;
    requested_by: number;
    division_id: number;
    status: PurchaseStatus;
    total_amount: string;
    created_at: string;
    purchase_type?: PurchaseType;
    employee?: Employee;
    requested_by_user?: UserOption;
    division?: Division;
    lines?: PurchaseRequestLine[];
    messages?: PurchaseRequestMessage[];
    approvals?: PurchaseApproval[];
    rfq?: PurchaseRfq | null;
}

export interface ExpenseCategory {
    id: number;
    code: string;
    name: string;
}

export interface ExpenseReportLine {
    id: number;
    expense_report_id: number;
    expense_date: string;
    expense_category_id: number;
    description: string | null;
    total: string;
    expense_category?: ExpenseCategory;
}

export interface ExpenseReport {
    id: number;
    code: string;
    employee_id: number;
    summary: string | null;
    total_expense: string;
    status: ExpenseStatus;
    created_at: string;
    employee?: Employee;
    lines?: ExpenseReportLine[];
}

export interface AccountingBill {
    id: number;
    source_type: 'purchase' | 'expense';
    source_id: number;
    bill_number: string | null;
    amount: string;
    status: string;
    due_date: string | null;
    created_at: string;
    source?: PurchaseRfq | ExpenseReport;
}

export interface PurchaseInvoice {
    id: number;
    purchase_rfq_id: number;
    invoice_number: string;
    invoice_date: string;
    amount: string;
    purchase_rfq?: PurchaseRfq;
    payments?: PurchasePayment[];
}

export interface PurchasePayment {
    id: number;
    purchase_invoice_id: number;
    bank_id: number;
    payment_date: string;
    amount: string;
    purchase_invoice?: PurchaseInvoice;
    bank?: Bank;
}
