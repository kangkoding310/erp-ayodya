# Arsitektur Teknis – Sistem ERP (Laravel + Inertia + PostgreSQL)

Dokumen ini adalah turunan teknis dari PRD ERP (Approval Matrix, Master Data, Purchase Request → Approval → RFQ, Expense, Accounting Bills). Referensi UI dashboard yang dilampirkan (sidebar module-based + card summary + chart) dipakai sebagai acuan pola layout Inertia di bawah.

---

## 1. Rekomendasi Tech Stack

| Layer | Pilihan | Alasan |
|---|---|---|
| Backend Framework | Laravel 11.x, PHP 8.3 | LTS support, native enum, performa |
| Frontend Bridge | Inertia.js v2 | SPA experience tanpa build API terpisah |
| Frontend Framework | Vue 3 + `<script setup>` + TypeScript | Learning curve lebih landai utk tim yang familiar Laravel; alternatif: React jika tim lebih kuat React |
| Styling | Tailwind CSS + shadcn-vue / Headless UI | Sesuai referensi UI (card, sidebar, badge) |
| Database | PostgreSQL 15+ | Dukungan JSONB (untuk approval matrix config & metadata), full-text search, numeric precision utk finance |
| State Management | Pinia | Store untuk cart-line purchase request, notifikasi realtime |
| Auth | Laravel Breeze (Inertia stack) + Sanctum | SPA auth session-based |
| Role & Permission | spatie/laravel-permission | Approval matrix per role/level |
| Activity Log | spatie/laravel-activitylog | Audit trail approval, edit PR |
| File/Attachment | spatie/laravel-medialibrary | Attachment PR, expense, chat |
| Notifikasi | Laravel Notification (database + mail channel) | Notifikasi approval berjenjang |
| Realtime | Laravel Reverb / Soketi + Laravel Echo | Update status approval & chat PR realtime |
| Queue & Job | Redis + Laravel Horizon | Generate nomor PR, kirim notifikasi, trigger bills async |
| PDF/Export | barryvdh/laravel-dompdf, maatwebsite/excel | Export PR, expense report, bills |
| Numbering Generator | Custom Service class + DB sequence table | Format `PR/MM/YYYY/###` yang bisa diedit manual |
| Testing | Pest + Laravel Dusk (opsional e2e) | |
| Multi-tenant (opsional, sesuai referensi "Switch Company") | spatie/laravel-multitenancy atau kolom `company_id` single-DB | Tergantung skala; single-DB dengan `company_id` scope lebih simpel utk awal |

---

## 2. Struktur Folder

### 2.1 Backend (Laravel) — pendekatan modular per domain

```
app/
├── Enums/
│   ├── PurchaseStatus.php
│   ├── ApprovalStatus.php
│   ├── ExpenseStatus.php
│   └── BillSourceType.php
├── Models/
│   ├── ApprovalMatrix.php
│   ├── ApprovalMatrixLevel.php
│   ├── PurchaseType.php
│   ├── Product.php
│   ├── ProductCategory.php
│   ├── Employee.php
│   ├── Division.php
│   ├── Bank.php
│   ├── Coa.php
│   ├── PurchaseRequest.php
│   ├── PurchaseRequestLine.php
│   ├── PurchaseRequestMessage.php
│   ├── PurchaseApproval.php
│   ├── PurchaseRfq.php
│   ├── ExpenseReport.php
│   ├── ExpenseReportLine.php
│   ├── ExpenseCategory.php
│   └── AccountingBill.php
├── Http/
│   ├── Controllers/
│   │   ├── ApprovalMatrix/ApprovalMatrixController.php
│   │   ├── MasterData/
│   │   │   ├── ProductController.php
│   │   │   ├── ProductCategoryController.php
│   │   │   ├── BankController.php
│   │   │   ├── CoaController.php
│   │   │   ├── EmployeeController.php
│   │   │   ├── DivisionController.php
│   │   │   └── PurchaseTypeController.php
│   │   ├── Purchase/
│   │   │   ├── PurchaseRequestController.php
│   │   │   ├── PurchaseRequestLineController.php
│   │   │   ├── PurchaseRequestMessageController.php
│   │   │   ├── PurchaseApprovalController.php
│   │   │   └── PurchaseRfqController.php
│   │   ├── Expense/
│   │   │   └── ExpenseReportController.php
│   │   └── Accounting/
│   │       ├── AccountingBillController.php
│   │       ├── PurchaseInvoiceController.php   (Faktur Pembelian)
│   │       └── PurchasePaymentController.php   (Pembayaran Pembelian)
│   ├── Requests/            (Form Request validation per modul)
│   ├── Resources/           (Inertia prop transformers, opsional)
│   └── Middleware/
├── Services/
│   ├── PurchaseRequestNumberGenerator.php
│   ├── ApprovalRoutingService.php     (tentukan next approver by matrix)
│   ├── PurchaseToRfqService.php       (trigger create Accounting Bill)
│   └── ExpenseReportService.php
├── Events/
│   ├── PurchaseRequestSubmitted.php
│   ├── PurchaseApproved.php
│   ├── PurchaseRejected.php
│   └── PurchaseSentToRfq.php
├── Listeners/
│   ├── NotifyNextApprover.php
│   └── CreateAccountingBillFromRfq.php
├── Notifications/
│   ├── ApprovalRequestedNotification.php
│   └── PurchaseStatusChangedNotification.php
├── Policies/
│   └── PurchaseRequestPolicy.php
└── Observers/
    └── PurchaseRequestObserver.php   (auto hitung total dari lines)
```

### 2.2 Frontend (Inertia + Vue/TS)

```
resources/js/
├── Layouts/
│   ├── AuthenticatedLayout.vue     (sidebar module + topbar, sesuai referensi)
│   └── GuestLayout.vue
├── Pages/
│   ├── Dashboard/Index.vue
│   ├── ApprovalMatrix/
│   │   ├── Index.vue
│   │   └── Partials/MatrixLevelForm.vue
│   ├── MasterData/
│   │   ├── Product/{Index,Create,Edit}.vue
│   │   ├── ProductCategory/Index.vue
│   │   ├── Bank/Index.vue
│   │   ├── Coa/Index.vue
│   │   ├── Employee/Index.vue
│   │   ├── Division/Index.vue
│   │   └── PurchaseType/Index.vue
│   ├── Purchase/
│   │   ├── Request/
│   │   │   ├── Index.vue
│   │   │   ├── Create.vue
│   │   │   ├── Show.vue            (detail + chat + attachment)
│   │   │   └── Partials/LineItemTable.vue
│   │   ├── Approval/
│   │   │   ├── Index.vue           (list utk approve/reject)
│   │   │   └── Show.vue            (detail + tombol approve/reject)
│   │   └── Rfq/Index.vue           (list sudah approved, trigger ke bills)
│   ├── Expense/
│   │   ├── Index.vue
│   │   ├── Create.vue
│   │   └── Show.vue
│   └── Accounting/
│       ├── Bills/Index.vue         (gabungan dari purchase-rfq + expense)
│       ├── PurchaseInvoice/Index.vue
│       └── PurchasePayment/Index.vue
├── Components/
│   ├── ui/          (Card, Badge, Table, Modal, Dropdown — reusable)
│   ├── forms/        (SelectAsync, DatePicker, FileUpload, CurrencyInput)
│   ├── charts/        (ApexCharts/Chart.js wrapper, sesuai referensi Invoices/Sales Forecast)
│   └── approval/      (ApprovalTimeline.vue, StatusBadge.vue)
├── Composables/
│   ├── useApprovalStatus.ts
│   ├── useCurrencyFormat.ts
│   └── useLineItems.ts    (logic tambah/hapus baris + auto subtotal)
├── Stores/            (Pinia — draft PR lines sebelum submit, notifikasi)
└── Types/
    ├── purchase.d.ts
    ├── expense.d.ts
    └── master-data.d.ts
```

### 2.3 Database

```
database/
├── migrations/     (urutan penting: master data → approval_matrix → transaksional)
├── seeders/        (PurchaseTypeSeeder, ProductCategorySeeder, RoleSeeder, dst.)
└── factories/       (untuk testing & dummy data)
```

---

## 3. Skema Database Inti (per modul)

**Approval Matrix**
- `approval_matrices` (id, name, purchase_type_id FK, model_type enum, is_active)
- `approval_matrix_levels` (id, approval_matrix_id FK, level int, approver_id FK→users, is_required bool)
- `purchase_types` (id, code, name) — master data

**Master Barang**
- `product_categories` (id, code, name)
- `products` (id, name, price numeric(15,2), tax_percentage numeric(5,2), type, product_category_id FK)

**Master Employee & Divisi**
- `divisions` (id, code, name)
- `employees` (id, name, division_id FK, position, user_id FK nullable)

**Master Bank**
- `banks` (id, bank_name, account_number, account_name)

**Master COA**
- `coa` (id, code, name, product_id FK nullable, type)

**Purchase Request**
- `purchase_requests` (id, code varchar unik-editable, purchase_type_id FK, project_name, currency, employee_id FK, expected_date, requested_by FK, division_id FK, status enum, total_amount numeric)
- `purchase_request_lines` (id, purchase_request_id FK, product_id FK, description, qty numeric, price_estimate numeric, subtotal numeric)
- `purchase_request_messages` (id, purchase_request_id FK, user_id FK, message text)
- media attachment via `media` table (spatie medialibrary)

**Purchase Approval**
- `purchase_approvals` (id, purchase_request_id FK, approval_matrix_level_id FK, approver_id FK, status enum[pending,approved,rejected], remarks, approved_at)

**Purchase → RFQ**
- `purchase_rfqs` (id, purchase_request_id FK, status, sent_to_accounting_at)

**Expense**
- `expense_categories` (id, code, name)
- `expense_reports` (id, code, employee_id FK, summary, total_expense numeric, status)
- `expense_report_lines` (id, expense_report_id FK, expense_date, expense_category_id FK, description, total numeric) + media attachment per baris

**Accounting**
- `accounting_bills` (id, source_type enum[purchase,expense], source_id, bill_number, amount, status, due_date)
- `purchase_invoices` (Faktur Pembelian) (id, purchase_rfq_id FK, invoice_number, invoice_date, amount)
- `purchase_payments` (Pembayaran Pembelian) (id, purchase_invoice_id FK, bank_id FK, payment_date, amount, proof media)

---

## 4. Daftar Komponen Teknis Ringkas

**Backend**
- Form Request validation per modul (mis. `StorePurchaseRequestRequest`)
- Service class terpisah untuk: generate nomor PR, routing approval, trigger RFQ→Bills
- Observer untuk auto-hitung total PR/Expense dari baris
- Event + Listener untuk notifikasi approval berjenjang
- Policy per role (requester, approver, finance)

**Frontend**
- Layout sidebar modular (Dashboard, Approval Matrix, Master Data, Purchase, Expense, Accounting) mengikuti pola referensi
- Komponen tabel baris dinamis (tambah/hapus line item, auto subtotal & grand total) dipakai ulang di PR dan Expense
- Komponen chat/message + attachment di detail PR
- Komponen approval timeline (menunjukkan level & status tiap approver)
- Chart ringkas di Dashboard (opsional, mengikuti referensi Invoices & Sales Forecast)

**Database**
- Semua tabel transaksi pakai `status` enum + `soft delete` untuk audit
- Index pada `code`, `status`, `purchase_type_id`, foreign key relasi
- Precision numeric(15,2) untuk semua kolom uang

---

## 5. Alur Kunci

1. **Generate nomor PR**: `PurchaseRequestNumberGenerator` service — format `PR/{MM}/{YYYY}/{sequence}`, sequence dihitung dari tabel counter per bulan; field `code` tetap editable manual di form.
2. **Approval routing**: saat PR disubmit → `ApprovalRoutingService` cari `approval_matrix` yang match `purchase_type_id`, buat baris `purchase_approvals` untuk tiap level → notifikasi ke approver level 1 → setelah approve, lanjut level berikutnya.
3. **Purchase → RFQ → Bills**: saat semua level approve → PR pindah ke daftar RFQ → aksi "kirim ke accounting" memicu `PurchaseToRfqService` membuat baris di `accounting_bills` (source_type = purchase).
4. **Expense → Bills**: expense report yang disubmit juga bisa membuat baris `accounting_bills` (source_type = expense).
5. **Accounting Bills** menampilkan gabungan dua sumber di atas dengan detail masing-masing.
