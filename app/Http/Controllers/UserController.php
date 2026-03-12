<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Company;
use App\Models\Module;
use App\Models\Role;
use App\Models\TenantModule;
use App\Models\User;
use App\Support\Audit\Audit;
use App\Support\CompanyContext;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $users = User::query()
            ->with(['defaultCompany:id,name', 'companies:id,name', 'roles:id,name'])
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'default_company' => $user->defaultCompany?->name,
                'companies' => $user->companies->map(fn ($company) => [
                    'id' => $company->id,
                    'name' => $company->name,
                ]),
                'roles' => $user->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ]),
            ]);

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', User::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();
        $filename = 'users-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $users = User::query()
            ->with(['companies:id,name', 'roles:id,name'])
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($users) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Nome',
                'Email',
                'Status',
                'Empresas',
                'Funcoes',
                'Criado em',
            ], ';');

            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->is_active ? 'Ativo' : 'Inativo',
                    $user->companies->pluck('name')->implode(', '),
                    $user->roles->pluck('name')->implode(', '),
                    $user->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $users = User::query()
            ->with(['companies:id,name', 'roles:id,name'])
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->is_active ? 'Ativo' : 'Inativo',
                'companies' => $user->companies->pluck('name')->implode(', '),
                'roles' => $user->roles->pluck('name')->implode(', '),
                'created_at' => $user->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.users-report', [
                'users' => $users,
                'filters' => [
                    'search' => $search,
                ],
                'generatedAt' => now()->format('d/m/Y H:i:s'),
                'tenantName' => $request->user()->tenant?->name ?? 'Tenant',
                'companyName' => CompanyContext::current()?->name ?? 'Empresa',
            ])
            ->setPaper('a4', 'landscape');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans Mono', 'normal');

        $canvas->page_text(
            680,
            560,
            '{PAGE_NUM}/{PAGE_COUNT}',
            $font,
            9,
            [0.42, 0.45, 0.5]
        );

        return response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="users-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('users/Create', $this->formData($request->user()->tenant_id));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId) {
            $data = $request->validated();

            $user = User::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => true,
            ]);

            $user->companies()->sync(
                collect($data['company_ids'])->mapWithKeys(fn ($companyId) => [
                    $companyId => ['tenant_id' => $tenantId],
                ])->toArray()
            );

            $user->roles()->sync($data['role_ids']);
            $user->modules()->sync($data['module_ids'] ?? []);

            Audit::event('users.created', $user, [
                'name' => $user->name,
                'email' => $user->email,
                'company_ids' => $data['company_ids'],
                'role_ids' => $data['role_ids'],
                'module_ids' => $data['module_ids'] ?? [],
                'is_active' => $user->is_active,
            ]);
        });

        return redirect()
            ->route('users.index')
            ->with('alert', Flash::success('Usuário criado', 'O usuário foi criado com sucesso.'));
    }

    public function edit(Request $request, User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('users/Edit', array_merge(
            $this->formData($request->user()->tenant_id),
            [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company_ids' => $user->companies()->pluck('companies.id')->toArray(),
                    'role_ids' => $user->roles()->pluck('roles.id')->toArray(),
                    'module_ids' => $user->modules()->pluck('modules.id')->toArray(),
                    'is_active' => $user->is_active,
                ],
            ]
        ));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user) {
            $data = $request->validated();

            $before = [
                'name' => $user->name,
                'email' => $user->email,
                'company_ids' => $user->companies()->pluck('companies.id')->toArray(),
                'role_ids' => $user->roles()->pluck('roles.id')->toArray(),
                'module_ids' => $user->modules()->pluck('modules.id')->toArray(),
                'is_active' => $user->is_active,
            ];

            $payload = [
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['is_active'],
            ];

            if (! empty($data['password'])) {
                $payload['password'] = $data['password'];
            }

            $user->update($payload);

            $user->companies()->sync(
                collect($data['company_ids'])->mapWithKeys(fn ($companyId) => [
                    $companyId => ['tenant_id' => $user->tenant_id],
                ])->toArray()
            );

            $user->roles()->sync($data['role_ids']);
            $user->modules()->sync($data['module_ids'] ?? []);

            Audit::event('users.updated', $user, [
                'before' => $before,
                'after' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'company_ids' => $data['company_ids'],
                    'role_ids' => $data['role_ids'],
                    'module_ids' => $data['module_ids'] ?? [],
                    'is_active' => $user->is_active,
                ],
            ]);
        });

        return redirect()
            ->route('users.index')
            ->with('alert', Flash::success('Usuário atualizado', 'As alterações foram salvas com sucesso.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        DB::transaction(function () use ($user) {
            $snapshot = [
                'name' => $user->name,
                'email' => $user->email,
                'company_ids' => $user->companies()->pluck('companies.id')->toArray(),
                'role_ids' => $user->roles()->pluck('roles.id')->toArray(),
                'module_ids' => $user->modules()->pluck('modules.id')->toArray(),
                'is_active' => $user->is_active,
            ];

            Audit::event('users.deleted', $user, $snapshot);

            $user->delete();
        });

        return redirect()
            ->route('users.index')
            ->with('alert', Flash::success('Usuário removido', 'O usuário foi removido com sucesso.'));
    }

    protected function formData(int $tenantId): array
    {
        $companies = Company::query()
            ->where('tenant_id', $tenantId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($company) => [
                'id' => $company->id,
                'name' => $company->name,
            ]);

        $roles = Role::query()
            ->where('tenant_id', $tenantId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
            ]);

        $moduleIds = TenantModule::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->pluck('module_id');

        $modules = Module::query()
            ->whereIn('id', $moduleIds)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($module) => [
                'id' => $module->id,
                'name' => $module->name,
            ]);

        return [
            'companies' => $companies,
            'roles' => $roles,
            'modules' => $modules,
        ];
    }
}
