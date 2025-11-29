<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiService;

class MisEstudiantesController extends Controller
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index()
    {
        $estudiantes = $this->apiService->getEstudiantes();
        // Convertimos el array a una colección para facilitar el manejo en Blade si es necesario
        $estudiantes = collect($estudiantes);
        
        return view('mis_estudiantes.index', compact('estudiantes'));
    }
}
