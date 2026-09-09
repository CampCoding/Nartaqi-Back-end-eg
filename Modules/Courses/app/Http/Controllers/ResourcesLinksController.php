<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\RoundResourceLinks;

class ResourcesLinksController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getResourceLinks(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required',
        ]);
        $resourceLinks = RoundResourceLinks::where('round_id', $data['round_id'])->get();
        return res_data($resourceLinks, 'success', 200);
    }
    public function storeResourceLinks(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required',
            'telegram_link' => 'required|url',
            'whatsapp_link' => 'required|url',
        ]);
        $resourceLinks = RoundResourceLinks::create($data);
        return res_data($resourceLinks, 'success', 200);
    }
    public function editResourceLinks(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:resource_links,id',
            'telegram_link' => 'required|url',
            'whatsapp_link' => 'required|url',
        ]);
        $resourceLinks = RoundResourceLinks::find($data['id']);
        $resourceLinks->update($data);
        return res_data($resourceLinks, 'success', 200);
    }
    public function deleteResourceLinks(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:resource_links,id',
        ]);
        $resourceLinks = RoundResourceLinks::find($data['id']);
        $resourceLinks->delete();
        return res_data('success', 'success', 200);
    }
    public function index()
    {
        return view('courses::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('courses::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('courses::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('courses::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
