<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\EmployeeInformation;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayComponent;
use App\Models\Payslip;
use App\Models\Position;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Policies\AttendancePolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\LeaveRequestPolicy;
use App\Policies\LeaveTypePolicy;
use App\Policies\PayComponentPolicy;
use App\Policies\PayslipPolicy;
use App\Policies\PositionPolicy;
use App\Policies\ProductCategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SalePolicy;
use App\Policies\StockMovementPolicy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Position::class, PositionPolicy::class);
        Gate::policy(EmployeeInformation::class, EmployeePolicy::class);
        Gate::policy(LeaveType::class, LeaveTypePolicy::class);
        Gate::policy(LeaveRequest::class, LeaveRequestPolicy::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(PayComponent::class, PayComponentPolicy::class);
        Gate::policy(Payslip::class, PayslipPolicy::class);
        Gate::policy(ProductCategory::class, ProductCategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(StockMovement::class, StockMovementPolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);
    }
}
