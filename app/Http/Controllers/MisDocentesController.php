<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiService;

class MisDocentesController extends Controller
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index()
    {
        $docentes = $this->apiService->getDocentes();
        $docentes = collect($docentes);

        return view('mis_docentes.index', compact('docentes'));
    }
}
