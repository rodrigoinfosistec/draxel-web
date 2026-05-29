<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TenantModule;
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

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Role::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $roles = Role::query()
            ->with(['permissions:id,name'])
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Role $role) => [
                'id' => $role->id,
                'slug' => $role->slug,
                'name' => $role->name,
                'description' => $role->description,
                'is_active' => $role->is_active,
                'permissions' => $role->permissions->map(fn ($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ]),
            ]);

        return Inertia::render('roles/Index', [
            'roles' => $roles,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Role::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();
        $filename = 'roles-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $roles = Role::query()
            ->with(['permissions:id,name'])
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($roles) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Slug',
                'Nome',
                'Descricao',
                'Status',
                'Permissoes',
                'Criado em',
            ], ';');

            foreach ($roles as $role) {
                fputcsv($handle, [
                    $role->id,
                    $role->slug,
                    $role->name,
                    $role->description,
                    $role->is_active ? 'Ativa' : 'Inativa',
                    $role->permissions->pluck('name')->implode(', '),
                    $role->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Role::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $roles = Role::query()
            ->with(['permissions:id,name'])
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'slug' => $role->slug,
                'name' => $role->name,
                'description' => $role->description,
                'status' => $role->is_active ? 'Ativa' : 'Inativa',
                'permissions' => $role->permissions->pluck('name')->implode(', '),
                'created_at' => $role->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.roles-report', [
                'roles' => $roles,
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
                'Content-Disposition' => 'attachment; filename="roles-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Role::class);

        return Inertia::render('roles/Create', $this->formData($request->user()->tenant_id));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId) {
            $data = $request->validated();

            $role = Role::create([
                'tenant_id' => $tenantId,
                'slug' => $data['slug'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $role->permissions()->sync($data['permission_ids'] ?? []);

            Audit::event('roles.created', $role, [
                'slug' => $role->slug,
                'name' => $role->name,
                'description' => $role->description,
                'permission_ids' => $data['permission_ids'] ?? [],
                'is_active' => $role->is_active,
            ]);
        });

        return redirect()
            ->route('roles.index')
            ->with('alert', Flash::success('Função criada', 'A função foi criada com sucesso.'));
    }

    public function edit(Request $request, Role $role): Response
    {
        $this->authorize('update', $role);

        return Inertia::render('roles/Edit', array_merge(
            $this->formData($request->user()->tenant_id),
            [
                'role' => [
                    'id' => $role->id,
                    'slug' => $role->slug,
                    'name' => $role->name,
                    'description' => $role->description,
                    'permission_ids' => $role->permissions()->pluck('permissions.id')->toArray(),
                    'is_active' => $role->is_active,
                ],
            ]
        ));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        DB::transaction(function () use ($request, $role) {
            $data = $request->validated();

            $before = [
                'slug' => $role->slug,
                'name' => $role->name,
                'description' => $role->description,
                'permission_ids' => $role->permissions()->pluck('permissions.id')->toArray(),
                'is_active' => $role->is_active,
            ];

            $role->update([
                'slug' => $data['slug'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? false,
            ]);

            $role->permissions()->sync($data['permission_ids'] ?? []);

            Audit::event('roles.updated', $role, [
                'before' => $before,
                'after' => [
                    'slug' => $role->slug,
                    'name' => $role->name,
                    'description' => $role->description,
                    'permission_ids' => $data['permission_ids'] ?? [],
                    'is_active' => $role->is_active,
                ],
            ]);
        });

        return redirect()
            ->route('roles.index')
            ->with('alert', Flash::success('Função atualizada', 'As alterações foram salvas com sucesso.'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        DB::transaction(function () use ($role) {
            $snapshot = [
                'slug' => $role->slug,
                'name' => $role->name,
                'description' => $role->description,
                'permission_ids' => $role->permissions()->pluck('permissions.id')->toArray(),
                'is_active' => $role->is_active,
            ];

            Audit::event('roles.deleted', $role, $snapshot);

            $role->delete();
        });

        return redirect()
            ->route('roles.index')
            ->with('alert', Flash::success('Função removida', 'A função foi removida com sucesso.'));
    }

    protected function formData(int $tenantId): array
    {
        $moduleIds = TenantModule::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->pluck('module_id');

        $permissions = Permission::query()
            ->with('module:id,name')
            ->whereIn('module_id', $moduleIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($permission) => $permission->module?->name ?? 'Sem módulo')
            ->map(fn ($group) => $group->map(fn ($permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'slug' => $permission->slug,
            ])->values())
            ->toArray();

        return [
            'permissions' => $permissions,
        ];
    }
}
