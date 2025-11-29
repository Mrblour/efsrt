<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ApiService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        // En un entorno real, estos vendrían de config/services.php o .env
        $this->baseUrl = 'https://api.example.com/v1'; 
        $this->apiKey = 'tu_api_key_aqui';
    }

    /**
     * Obtener lista de estudiantes desde la API externa.
     *
     * @return array
     */
    public function getEstudiantes()
    {
        // TODO: Reemplazar con llamada real a la API
        // $response = Http::withToken($this->apiKey)->get("{$this->baseUrl}/estudiantes");
        // return $response->json();

        // Datos simulados (Mock)
        return [
            [
                'id' => 1,
                'codigo' => '2023001',
                'nombres' => 'Juan',
                'apellidos' => 'Pérez López',
                'email' => 'juan.perez@example.com',
                'programa' => 'Desarrollo de Sistemas',
                'semestre' => 'V',
                'estado' => 'Activo',
                'foto' => null
            ],
            [
                'id' => 2,
                'codigo' => '2023002',
                'nombres' => 'María',
                'apellidos' => 'García Ruiz',
                'email' => 'maria.garcia@example.com',
                'programa' => 'Contabilidad',
                'semestre' => 'III',
                'estado' => 'Activo',
                'foto' => null
            ],
            [
                'id' => 3,
                'codigo' => '2023003',
                'nombres' => 'Carlos',
                'apellidos' => 'Mendoza Torres',
                'email' => 'carlos.mendoza@example.com',
                'programa' => 'Enfermería Técnica',
                'semestre' => 'I',
                'estado' => 'Inactivo',
                'foto' => null
            ],
             [
                'id' => 4,
                'codigo' => '2023004',
                'nombres' => 'Ana',
                'apellidos' => 'Lucía Fernández',
                'email' => 'ana.fernandez@example.com',
                'programa' => 'Desarrollo de Sistemas',
                'semestre' => 'V',
                'estado' => 'Activo',
                'foto' => null
            ],
        ];
    }

    /**
     * Obtener lista de docentes desde la API externa.
     *
     * @return array
     */
    public function getDocentes()
    {
        // TODO: Reemplazar con llamada real a la API
        // $response = Http::withToken($this->apiKey)->get("{$this->baseUrl}/docentes");
        // return $response->json();

        // Datos simulados (Mock)
        return [
            [
                'id' => 101,
                'codigo' => 'DOC-001',
                'nombres' => 'Roberto',
                'apellidos' => 'Sánchez Díaz',
                'email' => 'roberto.sanchez@instituto.edu.pe',
                'especialidad' => 'Ingeniería de Software',
                'tipo_contrato' => 'Nombrado',
                'estado' => 'Activo',
                'foto' => null
            ],
            [
                'id' => 102,
                'codigo' => 'DOC-002',
                'nombres' => 'Elena',
                'apellidos' => 'Vargas Quispe',
                'email' => 'elena.vargas@instituto.edu.pe',
                'especialidad' => 'Contabilidad Financiera',
                'tipo_contrato' => 'Contratado',
                'estado' => 'Activo',
                'foto' => null
            ],
            [
                'id' => 103,
                'codigo' => 'DOC-003',
                'nombres' => 'Miguel',
                'apellidos' => 'Ángel Torres',
                'email' => 'miguel.torres@instituto.edu.pe',
                'especialidad' => 'Salud Pública',
                'tipo_contrato' => 'Contratado',
                'estado' => 'Licencia',
                'foto' => null
            ],
        ];
    }
    /**
     * Consultar datos de una persona por DNI (Simulación RENIEC).
     *
     * @param string $dni
     * @return array|null
     */
    public function consultarDni($dni)
    {
        // TODO: Implementar conexión real a API RENIEC
        // $response = Http::withToken($this->apiKey)->get("{$this->baseUrl}/reniec/dni/{$dni}");
        // return $response->json();

        // Simulación básica para pruebas
        if (strlen($dni) === 8) {
            return [
                'success' => true,
                'data' => [
                    'numero' => $dni,
                    'nombres' => 'NOMBRES SIMULADOS ' . rand(100, 999),
                    'apellido_paterno' => 'APELLIDO PATERNO',
                    'apellido_materno' => 'APELLIDO MATERNO',
                    'nombre_completo' => 'NOMBRES SIMULADOS APELLIDO PATERNO APELLIDO MATERNO'
                ]
            ];
        }

        return ['success' => false, 'message' => 'DNI no encontrado'];
    }
}
