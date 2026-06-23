<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\GeneratePayslipRequest;
use App\Http\Requests\Payroll\MarkPaidRequest;
use App\Http\Resources\PayslipResource;
use App\Models\EmployeeInformation;
use App\Models\Payslip;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PayslipController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Payslip::class);

        $payslips = Payslip::query()
            ->with(['employee', 'items'])
            ->when($request->filled('employee_id'), fn ($query) => $query->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('period'), fn ($query) => $query->where('period', $request->string('period')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return PayslipResource::collection($payslips);
    }

    public function show(Payslip $payslip): PayslipResource
    {
        $this->authorize('view', $payslip);

        return PayslipResource::make($payslip->load(['employee', 'items.payComponent']));
    }

    public function generate(GeneratePayslipRequest $request, PayrollService $payrollService): PayslipResource
    {
        $this->authorize('generate', Payslip::class);

        $employee = EmployeeInformation::query()->findOrFail($request->integer('employee_id'));
        $payslip = $payrollService->generate($employee, $request->string('period')->toString());

        return PayslipResource::make($payslip);
    }

    public function markPaid(
        MarkPaidRequest $request,
        Payslip $payslip,
        PayrollService $payrollService,
    ): PayslipResource {
        $this->authorize('markPaid', $payslip);

        $payslip = $payrollService->markPaid($payslip);

        return PayslipResource::make($payslip);
    }
}
