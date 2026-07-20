import { icons } from '@/Constants/icons';
import { computed } from 'vue';

export interface NavChild {
    label: string;
    href: string;
    active: boolean;
}

export interface NavModule {
    key: string;
    label: string;
    icon: string;
    href: string;
    active: boolean;
    children: NavChild[];
}

const isRouteActive = (name: string): boolean => route().current(name) || route().current(`${name}.*`);

/**
 * Central definition of the app's navigation tree. Keeping this in a composable
 * (rather than inline in the layout component) means the module/route structure
 * can be reused, tested, or reconfigured without touching template code.
 */
export function useModuleNav() {
    const modules = computed<NavModule[]>(() => [
        {
            key: 'dashboard',
            label: 'Dashboard',
            icon: icons.dashboard,
            href: route('dashboard'),
            active: isRouteActive('dashboard'),
            children: [],
        },
        {
            key: 'approval-matrix',
            label: 'Approval Matrix',
            icon: icons.approval,
            href: route('approval-matrix.index'),
            active: isRouteActive('approval-matrix'),
            children: [],
        },
        {
            key: 'master-data',
            label: 'Master Data',
            icon: icons.masterData,
            href: route('master-data.products.index'),
            active: isRouteActive('master-data'),
            children: [
                { label: 'Products', href: route('master-data.products.index'), active: isRouteActive('master-data.products') },
                {
                    label: 'Product Categories',
                    href: route('master-data.product-categories.index'),
                    active: isRouteActive('master-data.product-categories'),
                },
                { label: 'Banks', href: route('master-data.banks.index'), active: isRouteActive('master-data.banks') },
                { label: 'Chart of Accounts', href: route('master-data.coa.index'), active: isRouteActive('master-data.coa') },
                { label: 'Employees', href: route('master-data.employees.index'), active: isRouteActive('master-data.employees') },
                { label: 'Divisions', href: route('master-data.divisions.index'), active: isRouteActive('master-data.divisions') },
                { label: 'Purchase Types', href: route('master-data.purchase-types.index'), active: isRouteActive('master-data.purchase-types') },
                { label: 'Projects', href: route('master-data.projects.index'), active: isRouteActive('master-data.projects') },
            ],
        },
        {
            key: 'purchase',
            label: 'Purchase',
            icon: icons.purchase,
            href: route('purchase.requests.index'),
            active: isRouteActive('purchase'),
            children: [
                { label: 'Requests', href: route('purchase.requests.index'), active: isRouteActive('purchase.requests') },
                { label: 'Approvals', href: route('purchase.approvals.index'), active: isRouteActive('purchase.approvals') },
                { label: 'RFQ', href: route('purchase.rfq.index'), active: isRouteActive('purchase.rfq') },
            ],
        },
        {
            key: 'expense',
            label: 'Expense',
            icon: icons.expense,
            href: route('expense.reports.index'),
            active: isRouteActive('expense'),
            children: [
                { label: 'Reports', href: route('expense.reports.index'), active: isRouteActive('expense.reports') },
                { label: 'Approvals', href: route('expense.approvals.index'), active: isRouteActive('expense.approvals') },
            ],
        },
        {
            key: 'accounting',
            label: 'Accounting',
            icon: icons.accounting,
            href: route('accounting.bills.index'),
            active: isRouteActive('accounting'),
            children: [
                { label: 'Bills', href: route('accounting.bills.index'), active: isRouteActive('accounting.bills') },
                {
                    label: 'Purchase Invoices',
                    href: route('accounting.purchase-invoices.index'),
                    active: isRouteActive('accounting.purchase-invoices'),
                },
                {
                    label: 'Purchase Payments',
                    href: route('accounting.purchase-payments.index'),
                    active: isRouteActive('accounting.purchase-payments'),
                },
            ],
        },
    ]);

    const activeModule = computed(() => modules.value.find((mod) => mod.active) ?? null);

    return { modules, activeModule };
}
