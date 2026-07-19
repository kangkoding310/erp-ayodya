<?php

namespace Database\Seeders;

use App\Models\ApprovalMatrix;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseRequest;
use App\Models\PurchaseType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $requester = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $approver = User::factory()->create([
            'name' => 'Finance Manager',
            'email' => 'approver@example.com',
        ]);

        $division = Division::create(['code' => 'GA', 'name' => 'General Affairs']);

        Employee::create([
            'name' => $requester->name,
            'division_id' => $division->id,
            'position' => 'Staff',
            'user_id' => $requester->id,
        ]);

        $category = ProductCategory::create(['code' => 'OFC', 'name' => 'Office Supplies']);

        Product::create([
            'name' => 'Kertas A4',
            'price' => 55000,
            'tax_percentage' => 11,
            'product_category_id' => $category->id,
        ]);

        $purchaseType = PurchaseType::create(['code' => 'GOODS', 'name' => 'Barang']);

        $matrix = ApprovalMatrix::create([
            'name' => 'Default Goods Approval',
            'purchase_type_id' => $purchaseType->id,
            'model_type' => PurchaseRequest::class,
            'is_active' => true,
        ]);

        $matrix->levels()->create([
            'level' => 1,
            'approver_id' => $approver->id,
            'is_required' => true,
        ]);
    }
}
