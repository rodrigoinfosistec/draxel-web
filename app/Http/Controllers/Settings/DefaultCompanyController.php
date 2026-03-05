<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DefaultCompanyController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/DefaultCompany', [
            'companies' => $request->user()
                ->companies()
                ->select('companies.id', 'companies.name')
                ->get(),
            'defaultCompanyId' => $request->user()->default_company_id,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        $company = $user->companies()
            ->where('companies.id', $request->company_id)
            ->first();

        if (! $company) {
            abort(403);
        }

        $user->update([
            'default_company_id' => $company->id,
        ]);

        return back();
    }
}
