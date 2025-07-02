<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Str;

class AuditController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Audit::all();

        return response()->json($products, JsonResponse::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validated();

        $data['slug'] = Str::slug($request->name);        
        $product = Audit::create($data);

        return response()->json($product, JsonResponse::HTTP_CREATED);
    }

    /**
     * Display the specified audit resource.
     *
     * Returns a single audit. If the `with_details` query parameter is set to true, returns the audit with all related AuditItems (with their Controls, Implementations, DataRequests, and DataRequestResponses), as well as DataRequests (with their DataRequestResponses).
     *
     * @group Audit
     *
     * @urlParam audit int required The ID of the audit. Example: 1
     * @queryParam with_details boolean Return all related audit items, controls, implementations, data requests, and responses. Example: true
     *
     * @response scenario=basic {"id": 1, "title": "Q2 Audit", ...}
     * @response scenario=with_details {"id": 1, "title": "Q2 Audit", "audit_items": [{"id": 10, "control": {...}, "implementation": {...}, "data_requests": [{"id": 100, "responses": [{...}]}]}], "data_request": [{"id": 200, "responses": [{...}]}]}
     */
    public function show(Request $request, Audit $audit)
    {
        if ($request->query('with_details')) {
            $audit->load([
                'auditItems.control',
                'auditItems.implementation',
                'auditItems.dataRequests.responses',
                'dataRequest.responses',
            ]);
        }
        return response()->json($audit, JsonResponse::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Audit $audit)
    {
        $data = $request->validated();

        $data['slug'] = Str::slug($request->name);

        $audit->update($data);

        return response()->json($audit, JsonResponse::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Audit $audit)
    {
        $audit->delete();
        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
