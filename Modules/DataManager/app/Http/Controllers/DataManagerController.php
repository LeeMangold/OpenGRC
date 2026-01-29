<?php

namespace Modules\DataManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DataManagerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        /** @var view-string $viewName */
        $viewName = 'datamanager::index';

        return view($viewName);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        /** @var view-string $viewName */
        $viewName = 'datamanager::create';

        return view($viewName);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): Response
    {
        return response()->noContent();
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): View
    {
        /** @var view-string $viewName */
        $viewName = 'datamanager::show';

        return view($viewName);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        /** @var view-string $viewName */
        $viewName = 'datamanager::edit';

        return view($viewName);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): Response
    {
        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Response
    {
        return response()->noContent();
    }
}
