# BusinessKit HRMS — Backend Implementation Plan

> Scope: small-to-mid HRMS on the existing **Laravel 13.8 / PHP 8.3** backend.
> Modules: Auth + RBAC, Employees, Departments, Positions, Leave, Attendance,
> Payroll (basics), Inventory, and **POS sales sync** (external POS app pushes
> sales into this website).

---

## 1. Overview & Principles

- **Stack:** Laravel 13.8, PHP 8.3. SQLite for dev; config already supports MySQL/MariaDB/PostgreSQL via `DB_CONNECTION`, so production can switch with no code change.
- **API-only backend.** Returns JSON; a separate SPA/React frontend consumes it. Token auth via **Laravel Sanctum**.
- **Clean layering** — keep controllers thin:

  ```
  Request → Route → Controller → Form Request (validation)
                          → Service (business logic / transactions)
                          → Eloquent Model
                          → API Resource (output shaping)
  ```

  Authorization lives in **Policies + spatie roles/permissions**. Status fields are **PHP 8.3 backed enums**.

- **Conventions**

  | Concern        | Location / pattern |
  |----------------|--------------------|
  | Controllers    | `app/Http/Controllers/Api/{Module}/…`, registered via `Route::apiResource` + route-model binding |
  | Validation     | `app/Http/Requests/{Module}/{Store\|Update}…Request` |
  | Output         | `app/Http/Resources/{Model}Resource` (+ collections where needed) |
  | Business logic | `app/Services/{Module}Service` |
  | Enums          | `app/Enums/` (`EmploymentStatus`, `LeaveStatus`, `StockMovementType`, …) |
  | Authorization  | `app/Policies/{Model}Policy` + route middleware (`can:`, `role:`) |

- JSON error rendering is **already enabled** in `bootstrap/app.php` (`shouldRenderJsonWhen` for `api/*`) — reuse it, don't re-add.

---

## 2. Foundation Fixes (do these FIRST — current code is inconsistent)

The current models and migrations disagree. These must be reconciled before any feature work.

- **Sanctum is half-wired.** `config/sanctum.php` and the `personal_access_tokens` migration exist, but `laravel/sanctum` is missing from `composer.json` and `User` has no `HasApiTokens` trait.
  - Add `laravel/sanctum` (`composer require laravel/sanctum`).
  - Add `use Laravel\Sanctum\HasApiTokens;` to `app/Models/User.php`.

- **`departments` migration creates no columns** (only `id` + timestamps), yet `Department` declares `name` fillable.
  - Add `name` (string, **unique**), `description` (nullable), `is_active` (bool, default `true`).

- **`positions` migration creates no columns**, yet `Position` declares `name` fillable.
  - Add `name` (string, **unique**), `description` (nullable), `is_active` (bool, default `true`).
  - **Keep positions independent of departments** (no `department_id`).

- **`employee_information` has string `position`/`department` columns but no FKs**, yet the model defines `belongsTo(Position)`/`belongsTo(Department)` on `position_id`/`department_id`.
  - Drop the string `position` and `department` columns.
  - Add `department_id` and `position_id`: `foreignId()->nullable()->constrained()->nullOnDelete()`.
  - Add `employment_status` (string, enum-backed), `salary` (decimal, nullable).
  - Update `EmployeeInformation::$fillable` to match (remove the dead `position`/`department` strings).

- **Migration strategy:** the dev DB is SQLite and pre-launch. Prefer **editing the existing migrations** and running `php artisan migrate:fresh --seed`. (Once the schema is live in any shared/production environment, switch to additive patch migrations instead.)

- Add `RoleAndPermissionSeeder` + `HrmsDemoSeeder`, wired into `DatabaseSeeder`.

---

## 3. Authentication (Sanctum)

- `AuthController` (`app/Http/Controllers/Api/Auth/`):
  - `register` — create user, issue token.
  - `login` — validate credentials, issue token.
  - `logout` — revoke the current token.
  - `me` — return the authenticated user (+ employee profile, roles).
- Requests: `RegisterRequest`, `LoginRequest`. Output: `UserResource` + plain-text token.
- Routes:
  - Public: `POST /api/auth/register`, `POST /api/auth/login`.
  - Protected (`auth:sanctum`): `POST /api/auth/logout`, `GET /api/auth/me`.

---

## 4. Authorization (spatie/laravel-permission)

- Install `spatie/laravel-permission`; publish config + migration; add `HasRoles` to `User`.
- **Roles:** `admin`, `hr`, `manager`, `employee`.
- **Permission groups (per module):** e.g. `employees.view`, `employees.manage`, `departments.manage`, `positions.manage`, `leave.request`, `leave.approve`, `attendance.manage`, `payroll.manage`, `inventory.manage`, `pos.sync`, `reports.view`.
- Seed roles + permissions in `RoleAndPermissionSeeder`.
- Enforce via **Policies** (one per model) plus route middleware (`can:`/`role:`).

### Role → permission matrix (starting point)

| Permission         | admin | hr | manager | employee |
|--------------------|:---:|:---:|:------:|:--------:|
| employees.view     | ✅ | ✅ | ✅ (team) | ✅ (self) |
| employees.manage   | ✅ | ✅ | — | — |
| departments.manage | ✅ | ✅ | — | — |
| positions.manage   | ✅ | ✅ | — | — |
| leave.request      | ✅ | ✅ | ✅ | ✅ |
| leave.approve      | ✅ | ✅ | ✅ (team) | — |
| attendance.manage  | ✅ | ✅ | ✅ (team) | ✅ (self clock) |
| payroll.manage     | ✅ | ✅ | — | — |
| inventory.manage   | ✅ | — | — | — |
| pos.sync           | ✅ | — | — | — |
| reports.view       | ✅ | ✅ | ✅ | — |

---

## 5. Core Modules — CRUD

For **Departments**, **Positions**, and **Employees**, each gets: model (relations/casts), an `apiResource` controller, Store/Update Form Requests, an API Resource, and a Policy.

- Controllers expose: `index` (paginated + filterable), `show`, `store`, `update`, `destroy`.
- **Employees** (`EmployeeInformation`):
  - Relations: `belongsTo(User)`, `belongsTo(Department)`, `belongsTo(Position)`.
  - Index eager-loads `user`, `department`, `position`; filter by `department_id`, `position_id`, `employment_status`; search by name / `employee_id`.
  - Representative endpoints:
    - `GET /api/employees`, `POST /api/employees`
    - `GET /api/employees/{employee}`, `PUT /api/employees/{employee}`, `DELETE /api/employees/{employee}`
    - Same shape for `/api/departments` and `/api/positions`.

---

## 6. Leave Management

- **Tables**
  - `leave_types` — `name`, `default_days`, `is_paid` (bool).
  - `leave_requests` — `employee_id`, `leave_type_id`, `start_date`, `end_date`, `days`, `reason`, `status` (enum), `approver_id` (nullable), `decided_at` (nullable).
  - `leave_balances` — `employee_id`, `leave_type_id`, `year`, `entitled`, `used`.
- `LeaveStatus` enum: `pending`, `approved`, `rejected`, `cancelled`.
- `LeaveService` — compute `days`, validate against balance, handle approval transitions (deduct balance on approve, restore on cancel).
- **Endpoints:** employee submits/cancels own requests; `manager`/`hr` `approve`/`reject` (policy-gated). List with filters by employee/status/date range.

---

## 7. Attendance

- **Table:** `attendances` — `employee_id`, `date`, `clock_in`, `clock_out`, `status` (`present`/`late`/`absent`/`leave`), `hours_worked`, `notes`. **Unique** `(employee_id, date)`.
- `AttendanceService` computes `hours_worked` and late flag (configurable start time).
- **Endpoints:** `POST /api/attendance/clock-in`, `POST /api/attendance/clock-out`, `GET /api/attendance` (filter by employee + date range), `GET /api/attendance/summary` (daily/monthly).

---

## 8. Payroll (basics)

- **Tables**
  - `pay_components` — `name`, `type` (`earning`/`deduction`), `amount` or `percentage`.
  - `employee_pay_components` — pivot linking employees to components.
  - `payslips` — `employee_id`, `period` (e.g. `YYYY-MM`), `gross`, `deductions`, `net`, `status`.
  - `payslip_items` — `payslip_id`, `pay_component_id`, `label`, `amount`.
- `PayrollService` — generate a payslip for an employee + period from base `salary` + assigned components. Optional hooks into attendance/leave later.
- **Endpoints:** generate, list, show, mark-paid.
- **Scope is intentionally basic:** no tax engine; flat earning/deduction components. Tax brackets / statutory deductions are a documented extension point.

---

## 9. Inventory

- **Tables**
  - `product_categories` — `name`, `description`.
  - `products` — `sku` (**unique**), `name`, `category_id`, `price`, `cost`, `quantity_on_hand`, `reorder_level`, `is_active`.
  - `stock_movements` — `product_id`, `type` (enum), `quantity`, `reference`, `source` (`manual`/`pos`), `created_by`.
- `StockMovementType` enum: `in`, `out`, `adjustment`.
- `InventoryService` — every movement adjusts `quantity_on_hand` **inside a DB transaction** (single source of truth for stock).
- **Endpoints:** product + category CRUD, `POST /api/inventory/stock-adjust`, `GET /api/inventory/low-stock` (where `quantity_on_hand <= reorder_level`).

---

## 10. POS Sales Sync (external app → this website)

**Purpose:** the external POS application pushes completed sales into this backend; sales decrement inventory and feed reporting.

- **Tables**
  - `sales` — `external_reference` (**unique**, idempotency key), `sold_at`, `cashier`, `subtotal`, `tax`, `total`, `payment_method`, `status`, `synced_at`.
  - `sale_items` — `sale_id`, `product_id` (matched by SKU), `sku`, `quantity`, `unit_price`, `line_total`.
- **Sync endpoint:** `POST /api/pos/sync`
  - Auth: Sanctum token scoped with the `pos.sync` ability/permission — issued to a **dedicated integration user/token** (not a human login).
  - Accepts a **batch** of sales.
  - **Idempotent:** dedupe on `external_reference` (skip/upsert already-synced sales) so retries are safe.
  - Wrapped in a **transaction**; for each sale item, create an `out` `stock_movement` (`source = pos`) via `InventoryService` to decrement stock.
- `PosSyncRequest` validates the batch shape; `PosSyncService` handles SKU matching, idempotency, and inventory decrement.
- **Reporting endpoints:** list synced sales, sales summary (by day / product / payment method).

### Expected sync payload (document for the POS client)

```json
{
  "sales": [
    {
      "external_reference": "POS-2026-0001",
      "sold_at": "2026-06-15T10:32:00Z",
      "cashier": "Jane D.",
      "payment_method": "cash",
      "subtotal": 100.00,
      "tax": 12.00,
      "total": 112.00,
      "items": [
        { "sku": "SKU-001", "quantity": 2, "unit_price": 50.00 }
      ]
    }
  ]
}
```

---

## 11. Cross-Cutting Concerns

- **Pagination & filtering:** standard `?per_page`, `?search`, plus module-specific filters; always return paginated API Resource responses.
- **Validation:** every write goes through a Form Request — no inline `$request->validate()` in controllers.
- **Error handling:** rely on the existing `shouldRenderJsonWhen` config; document the standard error envelope (`message`, `errors`).
- **Migration order:** users → sanctum → spatie → departments / positions → employees → leave → attendance → payroll → inventory → sales.
- **Seeders & factories:** a factory per model; `HrmsDemoSeeder` for realistic local data.
- **Testing (Feature tests, SQLite in-memory):**
  - Auth: register / login / logout / me.
  - CRUD + authorization per core module.
  - Leave approval flow (balance deducted on approve).
  - Attendance clock-in/out + hours calculation.
  - **POS sync: idempotency** (duplicate `external_reference` is a no-op) **+ stock decrement**.

---

## 12. Phased Roadmap (build order)

1. **Foundation** [COMPLETE] — fixes in §2, Sanctum auth (§3), spatie RBAC (§4).
2. **Core CRUD** [COMPLETE] — departments, positions, employees + resources / policies / tests (§5).
3. **Leave + Attendance** [COMPLETE] (§6, §7).
4. **Payroll basics** [COMPLETE] (§8).
5. **Inventory** [COMPLETE] (§9).
6. **POS sync** [COMPLETE] + inventory decrement + reporting (§10).

---

## 13. Target Directory Structure

```
app/
├── Enums/
│   ├── EmploymentStatus.php
│   ├── LeaveStatus.php
│   └── StockMovementType.php
├── Http/
│   ├── Controllers/Api/
│   │   ├── Auth/AuthController.php
│   │   ├── Hr/{Department,Position,Employee}Controller.php
│   │   ├── Leave/{LeaveType,LeaveRequest}Controller.php
│   │   ├── Attendance/AttendanceController.php
│   │   ├── Payroll/{PayComponent,Payslip}Controller.php
│   │   ├── Inventory/{Product,ProductCategory,StockMovement}Controller.php
│   │   └── Pos/PosSyncController.php
│   ├── Requests/{Auth,Hr,Leave,Attendance,Payroll,Inventory,Pos}/…
│   └── Resources/{User,Department,Position,Employee,LeaveRequest,Attendance,Payslip,Product,Sale}Resource.php
├── Models/
│   ├── User.php
│   ├── EmployeeInformation.php  Department.php  Position.php
│   ├── LeaveType.php  LeaveRequest.php  LeaveBalance.php
│   ├── Attendance.php
│   ├── PayComponent.php  Payslip.php  PayslipItem.php
│   ├── ProductCategory.php  Product.php  StockMovement.php
│   └── Sale.php  SaleItem.php
├── Policies/
│   └── {Employee,Department,Position,LeaveRequest,Product,…}Policy.php
└── Services/
    ├── LeaveService.php  AttendanceService.php  PayrollService.php
    ├── InventoryService.php  PosSyncService.php

routes/api.php   # grouped: auth (public) + auth:sanctum group containing
                 # hr, leave, attendance, payroll, inventory, pos.sync
```
